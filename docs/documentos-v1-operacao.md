# Documentos de vendas — arquitetura e operação v1.0

## Objetivo e fluxo

O CRM recebe PDFs e imagens, mantém uma cópia temporária local, verifica cada arquivo no
ClamAV e transfere o lote da venda por SFTP para o compartilhamento administrativo. A
requisição web nunca acessa o SFTP: ela responde assim que arquivo e metadados estão salvos.

Fluxo de estados:

`RECEBIDO → VERIFICANDO → AGUARDANDO_ENVIO → ENVIANDO → DISPONIVEL`

Falhas recuperáveis usam `FALHA`; malware usa `BLOQUEADO`; exclusões remotas passam por
`EXCLUSAO_PENDENTE`. O `client_upload_id` torna reenvios do navegador idempotentes.

## Arquitetura operacional

- `documentos-scan`: dois workers Redis, scan por arquivo, timeout de 180 segundos.
- `documentos-queue`: nome de serviço preservado; dois workers Redis nas filas
  `documentos-transfer,documentos`, timeout de 600 segundos.
- Cada worker de transferência reutiliza sua sessão SFTP. O primeiro handshake pode ser
  lento; operações seguintes usam a conexão quente.
- A transferência é serial por venda, drena até dez arquivos e grava `.part-<id>` antes da
  renomeação atômica. O arquivo remoto não é baixado novamente para hash. Após o rename, o
  worker reaplica explicitamente o modo colaborativo `0660`.
- Redis também fornece os locks por venda. `retry_after` é 900 segundos.
- O catálogo `documento_diretorios` evita conexão SFTP durante ações do painel e é
  sincronizado a cada dez minutos.
- Temporários ficam em `storage/app/private`; o bind mount do projeto é compartilhado pela
  aplicação e pelos workers. Sucesso retém a cópia por 24 horas e falha por sete dias.
- A árvore remota `EmAnalise` usa arquivos `0660`, diretórios `2770` (setgid), grupo POSIX compartilhado
  e ACL padrão. As máscaras do Samba não substituem grupo/ACL para objetos criados via SFTP.

## Pré-requisitos secretos de produção

Estes valores pertencem exclusivamente ao `.env` da VPS e nunca devem ser commitados:

- `DOCUMENTOS_PROCESSAMENTO_ATIVO=true`
- `DOCUMENTOS_DISK=documentos_sftp`
- `DOCUMENTOS_ROOT=EmAnalise`
- host, porta, usuário Linux/SFTP associado à chave, passphrase e fingerprint validados
- `DOCUMENTOS_SFTP_BASE_PATH=/srv/samba/administrativo`
- `DOCUMENTOS_SFTP_PRIVATE_KEY=/var/www/html/storage/app/private/keys/documentos_sftp`
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis` e `REDIS_HOST=redis`
- `REDIS_QUEUE_RETRY_AFTER=900`

Instalar a chave privada na VPS antes do deploy:

```bash
install -d -m 700 storage/app/private/keys
install -m 600 /origem/segura/documentos_sftp storage/app/private/keys/documentos_sftp
```

O usuário que executa os containers precisa conseguir ler a chave e escrever em
`storage/app/private`. `DOCUMENTOS_SFTP_USERNAME` identifica a conta Linux/OpenSSH na qual a
chave pública está autorizada. O nome ou comentário da chave e os usuários Samba são identidades
independentes. Quando houver uma conta SFTP dedicada, ela deve ser limitada ao diretório
administrativo. Não alterar Samba, WireGuard ou os mapeamentos das estações durante a rotação da
credencial.

### Contrato POSIX e Samba

SFTP e Samba acessam os mesmos inodes, mas `create mask`, `force create mode` e a `umask` do
Samba só governam objetos criados pelo Samba. Para os documentos criados via SFTP, a árvore
`/srv/samba/administrativo/EmAnalise` precisa de grupo POSIX compartilhado, setgid e ACL padrão.
O grupo deve conter o usuário SFTP e representar o grupo forçado para qualquer identidade já
autorizada a entrar no share. A autorização continua no Samba; uma vez autenticada, a identidade
opera os arquivos com o grupo forçado e pode ler, criar, editar, sobrescrever, renomear, mover e
excluir em toda a árvore `EmAnalise`.

### Causa raiz confirmada em 2026-08-17

Um arquivo criado pelo fluxo de upload no servidor de documentos foi encontrado como `root:root`,
modo `0660` e sem ACL. Nessas condições, as identidades Samba autorizadas não pertencem ao owner
nem ao grupo do inode e, por isso, não conseguem ler ou alterar o arquivo. O modo `0660`
isoladamente não garante o contrato colaborativo quando grupo e ACL estão incorretos. O nome
original do arquivo não foi persistido neste repositório por conter identificação de cliente.

O upload é realizado por uma identidade Linux/SFTP, enquanto o administrativo acessa por
identidades Samba distintas. O `sshd` cria o inode com o usuário SFTP autenticado; o UID do
processo PHP dentro do container e o usuário Samba não definem esse owner. O `setVisibility()`
posterior aplicava apenas `chmod 0660` e não podia corrigir grupo ou ACL, permitindo que o
preflight terminasse com sucesso apesar do contrato POSIX inválido.

A correção permanente exige conjuntamente:

1. manter `DOCUMENTOS_SFTP_USERNAME` com o usuário Linux/SFTP que realmente aceita a chave e
   incluir essa identidade no grupo POSIX `administrativo`; ela não precisa ter o mesmo nome de
   nenhum usuário Samba;
2. manter `2770`, setgid e ACL padrão em todos os diretórios existentes da árvore, para que arquivos
   futuros herdem o grupo e a ACL mesmo quando forem criados dentro de pastas antigas;
3. manter arquivos em `0660`; a aplicação reaplica esse modo depois da renomeação atômica.

Após a correção, validar um novo upload real com `stat` e `getfacl` e confirmar leitura, alteração,
renomeação e exclusão pela identidade SFTP configurada e por uma estação Samba autorizada.
Arquivos existentes com `root:root` continuam exigindo reparo idempotente; corrigir somente o
processo de criação não altera inodes antigos.

Na VPS de documentos, sem copiar script, valide e normalize o contrato com a raiz exata:

```bash
DOCS_ROOT=/srv/samba/administrativo/EmAnalise
DOCS_GROUP=administrativo
SFTP_USER=root
SAMBA_USER=crm_documentos

sudo test "$(realpath -e -- "$DOCS_ROOT")" = "$DOCS_ROOT"
getent passwd "$SFTP_USER"
getent passwd "$SAMBA_USER"
getent group "$DOCS_GROUP"
sudo usermod -aG "$DOCS_GROUP" "$SAMBA_USER"

sudo find "$DOCS_ROOT" -xdev -exec chgrp -- "$DOCS_GROUP" {} +
sudo find "$DOCS_ROOT" -xdev -type d -exec chmod 2770 -- {} +
sudo find "$DOCS_ROOT" -xdev -type f -exec chmod 0660 -- {} +
sudo find "$DOCS_ROOT" -xdev -type d -exec setfacl -m \
  "u::rwx,g::rwx,g:${DOCS_GROUP}:rwx,m::rwx,o::---" \
  -m "d:u::rwx,d:g::rwx,d:g:${DOCS_GROUP}:rwx,d:m::rwx,d:o::---" -- {} +
sudo find "$DOCS_ROOT" -xdev -type f -exec setfacl -m \
  "u::rw-,g::rw-,g:${DOCS_GROUP}:rw-,m::rw-,o::---" -- {} +
```

Esses comandos não atravessam outros filesystems. A normalização inicial dos diretórios existentes
é necessária porque uma ACL padrão aplicada somente em `EmAnalise` não alcança pastas antigas onde
novos documentos continuarão sendo criados.

Copie somente o script versionado para uma área administrativa do servidor de arquivos. Nesse
servidor, audite e depois aplique o mecanismo idempotente:

```bash
sudo ./scripts/configurar-permissoes-documentos-samba.sh \
  --root /srv/samba/administrativo/EmAnalise \
  --group administrativo --sftp-user root --share administrativo
sudo ./scripts/configurar-permissoes-documentos-samba.sh \
  --root /srv/samba/administrativo/EmAnalise \
  --group administrativo --sftp-user root --share administrativo --apply
```

Se a auditoria informar que o usuário SFTP ainda não pertence ao grupo, confirme a identidade
e repita a aplicação acrescentando a autorização explícita `--add-sftp-user-to-group`. O script
não altera grupos de usuários implicitamente.

O script recusa raízes amplas e links simbólicos, não cruza outros filesystems e corrige arquivos
existentes para `0660`, diretórios para `2770`, ownership de grupo e ACLs atuais/padrão. Ele também
garante que o usuário SFTP pertença ao grupo e recusa a aplicação sem `--share`, se o share não
estiver gravável ou se ele não forçar o mesmo grupo. No share, preserve
`valid users = @administrativo`, `read only = no`, `force group = administrativo`, máscaras
`0660/0770`, `force create mode = 0660`, `force directory mode = 0770`, `inherit acls = yes` e
`map acl inherit = yes`. Valide com `testparm -s` antes de recarregar o Samba.
Não conceda acesso a `Everyone`/`other` e não use `0777`.

Depois da aplicação, encerre as sessões SFTP persistentes para que o processo carregue o novo grupo.
Na VPS da aplicação, isso é feito sem derrubar containers ou volumes:

```bash
docker compose -f docker-compose.yml exec -T laravel.test php artisan queue:restart
```

## Deploy de final de semana

O workflow executa automaticamente, nesta ordem:

1. valida o Compose e o Dockerfile versionado;
2. instala as dependências do `composer.lock` antes do build;
3. constrói o runtime PHP com `openssl`, `sodium` e `gmp`;
4. executa o preflight de Redis e ClamAV antes dos serviços dependentes; se encontrar o
   ClamAV `unhealthy`, registra health/logs com coleta limitada a 15 segundos por consulta e
   recria somente o container uma vez, preservando o volume `clamav-data`; falha ou bloqueio
   na coleta diagnóstica não impede a tentativa de recuperação;
5. sobe a aplicação com os workers documentais em escala zero;
6. instala/valida dependências dentro do runtime e executa migrations;
7. limpa caches e executa `documentos:validar-configuracao --production`;
8. valida escrita, leitura, renomeação e exclusão pela identidade SFTP (isso não valida Samba);
9. sincroniza as pastas, sobe dois workers de cada tipo e reconcilia pendências;
10. reinicia workers e valida a saúde HTTP da aplicação.

Antes do push que dispara o deploy, confirmar na VPS que `.env` e chave estão prontos. Após
o deploy, conferir:

```bash
docker compose -f docker-compose.yml ps
docker compose -f docker-compose.yml logs --tail=100 documentos-scan documentos-queue clamav
docker compose -f docker-compose.yml exec -T laravel.test php artisan queue:failed
```

Fazer uma venda controlada com PDF pequeno e confirmar `DISPONIVEL` tanto no CRM quanto no
diretório `Y:\EmAnalise\<Operadora>\<Empresa>`. Na estação Windows autorizada, abrir, editar,
salvar e renomear o arquivo; no servidor, conferir grupo e ACL com `stat` e `getfacl`.

### Correção idempotente de permissões existentes

O comando abaixo é restrito aos arquivos ativos catalogados em `venda_documentos` e cujos caminhos
estão sob `DOCUMENTOS_ROOT`. Sem `--apply`, ele apenas informa quantos registros seriam processados:

```bash
docker compose -f docker-compose.yml exec -T laravel.test \
  php artisan documentos:reparar-permissoes
docker compose -f docker-compose.yml exec -T laravel.test \
  php artisan documentos:reparar-permissoes --apply
```

Esse comando reaplica `0660` via SFTP somente nos arquivos catalogados. O adaptador SFTP não deve
usar `setVisibility()` em diretórios, pois essa operação aplica modo de arquivo e pode retirar a
permissão de travessia. Para corrigir ownership, diretórios e ACLs existentes, execute no servidor
de arquivos o script descrito no contrato POSIX/Samba acima.
Nunca aplique a correção no workspace nem na raiz inteira do share.

## Contingência e rollback

Se o servidor documental estiver indisponível, não reverta migrations nem apague filas.
Defina `DOCUMENTOS_PROCESSAMENTO_ATIVO=false`, limpe o cache e deixe os documentos recebidos
no armazenamento temporário. Pare apenas os workers documentais:

```bash
docker compose -f docker-compose.yml up -d --scale documentos-scan=0 --scale documentos-queue=0
docker compose -f docker-compose.yml exec -T laravel.test php artisan optimize:clear
```

Após corrigir a causa, reative a variável, suba os workers e execute:

```bash
docker compose -f docker-compose.yml exec -T laravel.test php artisan documentos:testar-conexao
docker compose -f docker-compose.yml exec -T laravel.test php artisan documentos:sincronizar-diretorios
docker compose -f docker-compose.yml exec -T laravel.test php artisan documentos:processar-pendentes --limit=5000
```

Nunca sobrescrever arquivos remotos, excluir `.part` em massa ou mover pastas do Samba sem
auditar banco e servidor. Os testes antigos gravados em `/srv/samba/comercial` não fazem
parte da raiz v1 e devem ser tratados separadamente.
