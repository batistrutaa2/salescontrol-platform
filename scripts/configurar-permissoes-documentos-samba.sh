#!/usr/bin/env bash

set -euo pipefail

usage() {
  echo "Uso: $0 --root /srv/samba/administrativo/EmAnalise --group GRUPO [--apply]"
}

target=""
shared_group=""
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

if [[ -z "$target" || -z "$shared_group" ]]; then
  usage >&2
  exit 2
fi

for command_name in realpath getent find stat; do
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

directory_count="$(find "$target" -xdev -type d -print | wc -l)"
file_count="$(find "$target" -xdev -type f -print | wc -l)"

if [[ "$apply" != true ]]; then
  echo "Auditoria: $directory_count diretório(s) e $file_count arquivo(s). Nenhuma alteração aplicada."
  exit 0
fi

command -v setfacl >/dev/null || {
  echo "Dependência ausente: setfacl" >&2
  exit 1
}

chgrp -R -- "$shared_group" "$target"
find "$target" -xdev -type d -exec chmod 2770 -- {} +
find "$target" -xdev -type f -exec chmod 0660 -- {} +
find "$target" -xdev -type d -exec setfacl -m \
  "u::rwx,g::rwx,g:${shared_group}:rwx,m::rwx,o::---" \
  -m "d:u::rwx,d:g::rwx,d:g:${shared_group}:rwx,d:m::rwx,d:o::---" -- {} +
find "$target" -xdev -type f -exec setfacl -m \
  "u::rw-,g::rw-,g:${shared_group}:rw-,m::rw-,o::---" -- {} +

echo "Permissões aplicadas: grupo $shared_group, diretórios 2770 e arquivos 0660 sob $target."
