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
  renomeação atômica. O arquivo remoto não é baixado novamente para hash.
- Redis também fornece os locks por venda. `retry_after` é 900 segundos.
- O catálogo `documento_diretorios` evita conexão SFTP durante ações do painel e é
  sincronizado a cada dez minutos.
- Temporários ficam em `storage/app/private`; o bind mount do projeto é compartilhado pela
  aplicação e pelos workers. Sucesso retém a cópia por 24 horas e falha por sete dias.

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

## Deploy de final de semana

O workflow executa automaticamente, nesta ordem:

1. valida o Compose e o Dockerfile versionado;
2. instala as dependências do `composer.lock` antes do build;
3. constrói o runtime PHP com `openssl`, `sodium` e `gmp`;
4. sobe a aplicação com os workers documentais em escala zero;
5. instala/valida dependências dentro do runtime e executa migrations;
6. limpa caches e executa `documentos:validar-configuracao --production`;
7. valida escrita, leitura, renomeação e exclusão SFTP;
8. sincroniza as pastas, sobe dois workers de cada tipo e reconcilia pendências;
9. reinicia workers e valida a saúde HTTP da aplicação.

Antes do push que dispara o deploy, confirmar na VPS que `.env` e chave estão prontos. Após
o deploy, conferir:

```bash
docker compose -f docker-compose.yml ps
docker compose -f docker-compose.yml logs --tail=100 documentos-scan documentos-queue clamav
docker compose -f docker-compose.yml exec -T laravel.test php artisan queue:failed
```

Fazer uma venda controlada com PDF pequeno e confirmar `DISPONIVEL` tanto no CRM quanto no
diretório `Y:\EmAnalise\<Operadora>\<Empresa>`.

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
