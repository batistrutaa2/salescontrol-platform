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
- A árvore remota `EmAnalise` usa arquivos `0660`, diretórios `2770`, grupo POSIX compartilhado
  e ACL padrão. As máscaras do Samba não substituem grupo/ACL para objetos criados via SFTP.

## Pré-requisitos secretos de produção

Estes valores pertencem exclusivamente ao `.env` da VPS e nunca devem ser commitados:

- `DOCUMENTOS_PROCESSAMENTO_ATIVO=true`
- `DOCUMENTOS_DISK=documentos_sftp`
- `DOCUMENTOS_ROOT=EmAnalise`
- host, porta, usuário, passphrase e fingerprint SFTP validados
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
`storage/app/private`. A versão inicial pode usar a credencial testada, mas a ação de
pós-deploy obrigatória é rotacionar para um usuário SFTP dedicado, limitado ao diretório
administrativo. Não alterar Samba, WireGuard ou os mapeamentos das estações durante essa
rotação.

### Contrato POSIX e Samba

SFTP e Samba acessam os mesmos inodes, mas `create mask`, `force create mode` e a `umask` do
Samba só governam objetos criados pelo Samba. Para os documentos criados via SFTP, a árvore
`/srv/samba/administrativo/EmAnalise` precisa de grupo POSIX compartilhado, setgid e ACL padrão.
O grupo deve conter somente `crm_documentos` e os usuários autorizados no share.

Copie somente o script versionado para uma área administrativa do servidor de arquivos. Nesse
servidor, audite e depois aplique o mecanismo idempotente:

```bash
sudo ./scripts/configurar-permissoes-documentos-samba.sh \
  --root /srv/samba/administrativo/EmAnalise --group administrativo
sudo ./scripts/configurar-permissoes-documentos-samba.sh \
  --root /srv/samba/administrativo/EmAnalise --group administrativo --apply
```

O script recusa raízes amplas e links simbólicos, não cruza outros filesystems e corrige arquivos
existentes para `0660`, diretórios para `2770`, ownership de grupo e ACLs atuais/padrão. No share,
preserve `valid users = @administrativo`, `force group = administrativo`, máscaras `0660/0770`,
`inherit acls = yes` e `map acl inherit = yes`. Valide com `testparm -s` antes de recarregar o Samba.
Não conceda acesso a `Everyone`/`other` e não use `0777`.

## Deploy de final de semana

O workflow executa automaticamente, nesta ordem:

1. valida o Compose e o Dockerfile versionado;
2. instala as dependências do `composer.lock` antes do build;
3. constrói o runtime PHP com `openssl`, `sodium` e `gmp`;
4. executa o preflight de Redis e ClamAV antes dos serviços dependentes; se encontrar o
   ClamAV `unhealthy`, registra health/logs e recria somente o container uma vez, preservando
   o volume `clamav-data`;
5. sobe a aplicação com os workers documentais em escala zero;
6. instala/valida dependências dentro do runtime e executa migrations;
7. limpa caches e executa `documentos:validar-configuracao --production`;
8. valida escrita, leitura, renomeação e exclusão SFTP;
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

Esse comando reaplica `0660` via SFTP nos arquivos. Para corrigir também ownership, diretórios e
ACLs existentes, execute no servidor de arquivos o script descrito no contrato POSIX/Samba acima.
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
