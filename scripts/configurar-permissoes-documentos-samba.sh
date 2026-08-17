#!/usr/bin/env bash

set -euo pipefail

usage() {
  echo "Uso: $0 --root /srv/samba/administrativo/EmAnalise --group GRUPO --sftp-user USUARIO [--share SHARE] [--add-sftp-user-to-group] [--apply]"
}

target=""
shared_group=""
sftp_user=""
samba_share=""
add_sftp_user_to_group=false
apply=false

while (($# > 0)); do
  case "$1" in
    --root)
      target="${2:-}"
      shift 2
      ;;
    --group)
      shared_group="${2:-}"
      shift 2
      ;;
    --sftp-user)
      sftp_user="${2:-}"
      shift 2
      ;;
    --share)
      samba_share="${2:-}"
      shift 2
      ;;
    --add-sftp-user-to-group)
      add_sftp_user_to_group=true
      shift
      ;;
    --apply)
      apply=true
      shift
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      usage >&2
      exit 2
      ;;
  esac
done

if [[ -z "$target" || -z "$shared_group" || -z "$sftp_user" ]]; then
  usage >&2
  exit 2
fi

for command_name in realpath getent find stat id; do
  command -v "$command_name" >/dev/null || {
    echo "Dependência ausente: $command_name" >&2
    exit 1
  }
done

if [[ ! -d "$target" || -L "$target" ]]; then
  echo "A raiz precisa existir e não pode ser um link simbólico." >&2
  exit 1
fi

target="$(realpath -e -- "$target")"
if [[ "$target" != "/srv/samba/administrativo/EmAnalise" ]]; then
  echo "Raiz recusada; este script atua somente em /srv/samba/administrativo/EmAnalise." >&2
  exit 1
fi

if ! getent group "$shared_group" >/dev/null; then
  echo "O grupo POSIX informado não existe." >&2
  exit 1
fi

if ! getent passwd "$sftp_user" >/dev/null; then
  echo "O usuário SFTP informado não existe neste servidor." >&2
  exit 1
fi

# root é a identidade SFTP já existente neste ambiente. Sob diretórios com
# setgid ele herda o grupo do pai, sem precisar ser membro suplementar.
sftp_has_group=false
if [[ "$(id -u "$sftp_user")" -eq 0 ]] \
  || id -nG "$sftp_user" | tr ' ' '\n' | grep -Fx -- "$shared_group" >/dev/null; then
  sftp_has_group=true
fi

samba_check="não solicitado"
if [[ -n "$samba_share" ]]; then
  command -v testparm >/dev/null || {
    echo "Dependência ausente: testparm" >&2
    exit 1
  }

  share_path="$(testparm -s --parameter-name=path --section-name="$samba_share" 2>/dev/null || true)"
  share_read_only="$(testparm -s --parameter-name='read only' --section-name="$samba_share" 2>/dev/null || true)"
  share_force_group="$(testparm -s --parameter-name='force group' --section-name="$samba_share" 2>/dev/null || true)"

  if [[ -z "$share_path" ]]; then
    echo "O share Samba '$samba_share' não existe ou não possui path configurado." >&2
    exit 1
  fi
  share_path="$(realpath -e -- "$share_path")"
  if [[ "$target" != "$share_path" && "$target" != "$share_path"/* ]]; then
    echo "A raiz documental não pertence ao path do share Samba '$samba_share'." >&2
    exit 1
  fi
  if [[ "${share_read_only,,}" != "no" ]]; then
    echo "O share Samba '$samba_share' precisa de 'read only = no'." >&2
    exit 1
  fi
  if [[ "${share_force_group#+}" != "$shared_group" ]]; then
    echo "O share Samba '$samba_share' precisa de 'force group = $shared_group'." >&2
    exit 1
  fi
  if [[ "$share_force_group" == +* ]]; then
    echo "Use 'force group = $shared_group' sem o prefixo + para abranger toda identidade autorizada no share." >&2
    exit 1
  fi
  samba_check="válido (read only = no; force group = $shared_group)"
fi

directory_count="$(find "$target" -xdev -type d -print | wc -l)"
file_count="$(find "$target" -xdev -type f -print | wc -l)"

if [[ "$apply" != true ]]; then
  echo "Auditoria: $directory_count diretório(s), $file_count arquivo(s), identidade SFTP compatível: $sftp_has_group, Samba: $samba_check. Nenhuma alteração aplicada."
  exit 0
fi

if [[ -z "$samba_share" ]]; then
  echo "Informe --share ao aplicar, para validar que o Samba força o mesmo grupo com escrita habilitada." >&2
  exit 1
fi

command -v setfacl >/dev/null || {
  echo "Dependência ausente: setfacl" >&2
  exit 1
}

if [[ "$sftp_has_group" != true && "$add_sftp_user_to_group" != true ]]; then
  echo "O usuário SFTP não pertence ao grupo $shared_group." >&2
  echo "Audite e execute novamente com --add-sftp-user-to-group para autorizar essa alteração explícita." >&2
  exit 1
fi

if [[ "$sftp_has_group" != true ]]; then
  command -v usermod >/dev/null || {
    echo "Dependência ausente: usermod" >&2
    exit 1
  }
  usermod -aG "$shared_group" "$sftp_user"
fi

find "$target" -xdev -exec chgrp -- "$shared_group" {} +
find "$target" -xdev -type d -exec chmod 2770 -- {} +
find "$target" -xdev -type f -exec chmod 0660 -- {} +
find "$target" -xdev -type d -exec setfacl -m \
  "u::rwx,g::rwx,g:${shared_group}:rwx,m::rwx,o::---" \
  -m "d:u::rwx,d:g::rwx,d:g:${shared_group}:rwx,d:m::rwx,d:o::---" -- {} +
find "$target" -xdev -type f -exec setfacl -m \
  "u::rw-,g::rw-,g:${shared_group}:rw-,m::rw-,o::---" -- {} +

echo "Permissões aplicadas: identidade SFTP $sftp_user, grupo $shared_group, diretórios 2770, arquivos 0660 e ACLs colaborativas sob $target."
echo "Reinicie os workers documentais para encerrar as sessões SFTP persistentes."
