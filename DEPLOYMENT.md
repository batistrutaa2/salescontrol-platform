# Deploy de produção — SalesControl Platform

O deploy publica uma imagem imutável no GHCR e instala somente os artefatos de
operação em `/root/apps/salescontrol-platform`. Composer e Node executam no CI;
a VPS apenas baixa a imagem pronta, aplica migrations e reinicia os serviços.

## Topologia inicial

- aplicação: `127.0.0.1:8010` (Nginx + PHP-FPM, limite de 640 MB);
- fila padrão/WhatsApp: container próprio, limite de 384 MB;
- scheduler: container próprio, limite de 192 MB;
- Redis: rede Docker privada e volume persistente, limite de 128 MB;
- Reverb: perfil `realtime` habilitado no exemplo, em `127.0.0.1:8087`;
- documentos: perfil opcional `documentos`.

O banco MySQL permanece instalado na VPS. A rede da aplicação é fixada em
`172.25.0.0/24`, sem sobrepor as redes existentes. O usuário do banco aceita
conexões somente dessa rede e não reutiliza credenciais de outros produtos.
Não use o usuário `root` do MySQL na aplicação.

## Preparação única da VPS

1. Crie uma chave SSH exclusiva para o GitHub Actions. A senha do root pode ser
   usada somente nesta preparação inicial; não salve `VPS_PASSWORD` no GitHub.
2. Adicione a chave pública em `/root/.ssh/authorized_keys` e confirme o login
   por chave.
3. Crie somente o database principal e o usuário dedicado. O `people_db` já
   existe na VPS e é compartilhado com a pesquisa ativa; ele não deve ser
   recriado, esvaziado ou restaurado como parte deste deploy:

   ```sql
   CREATE DATABASE salescontrol_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'salescontrol_platform'@'172.25.0.%' IDENTIFIED BY 'SENHA_FORTE';
   GRANT ALL PRIVILEGES ON salescontrol_platform.* TO 'salescontrol_platform'@'172.25.0.%';
   GRANT SELECT ON people_db.* TO 'salescontrol_platform'@'172.25.0.%';
   -- Temporário, somente durante o primeiro migrate:
   GRANT CREATE, ALTER, INDEX, DROP ON people_db.* TO 'salescontrol_platform'@'172.25.0.%';
   FLUSH PRIVILEGES;
   ```

   Depois que o primeiro migrate criar as tabelas `assertiva_*`, revogue o DDL
   e permita escrita exclusivamente nelas:

   ```sql
   REVOKE CREATE, ALTER, INDEX, DROP, REFERENCES ON people_db.* FROM 'salescontrol_platform'@'172.25.0.%';
   GRANT INSERT, UPDATE, DELETE ON people_db.assertiva_pessoas TO 'salescontrol_platform'@'172.25.0.%';
   GRANT INSERT, UPDATE, DELETE ON people_db.assertiva_empresas TO 'salescontrol_platform'@'172.25.0.%';
   GRANT INSERT, UPDATE, DELETE ON people_db.assertiva_telefones TO 'salescontrol_platform'@'172.25.0.%';
   GRANT INSERT, UPDATE, DELETE ON people_db.assertiva_enderecos TO 'salescontrol_platform'@'172.25.0.%';
   GRANT INSERT, UPDATE, DELETE ON people_db.assertiva_emails TO 'salescontrol_platform'@'172.25.0.%';
   FLUSH PRIVILEGES;
   ```

   Uma migration futura que altere `people_db` deverá passar por uma janela
   explícita de revisão e concessão temporária de DDL; o CI falhará fechado fora
   dessa janela.

4. Verifique se o MySQL escuta no gateway Docker, sem expor a porta 3306 na
   internet. O firewall deve continuar bloqueando 3306 externamente.

## Secrets do ambiente `production` no GitHub

- `VPS_HOST`: IP ou hostname da VPS;
- `VPS_PORT`: normalmente `22`;
- `VPS_USER`: inicialmente `root`;
- `VPS_SSH_KEY`: chave privada exclusiva do deploy;
- `VPS_KNOWN_HOSTS`: saída validada de `ssh-keyscan -H <host>`;
- `REVERB_APP_KEY`: valor público usado pelo backend e pelo bundle Vite;
- `PRODUCTION_ENV`: conteúdo completo baseado em `.env.production.example`.

Gere segredos localmente, sem colá-los em terminal compartilhado:

```bash
openssl rand -base64 32
openssl rand -hex 32
```

Use o primeiro valor para `APP_KEY` prefixado com `base64:`. Gere valores
independentes para `REVERB_APP_KEY` e `REVERB_APP_SECRET`. O
`REVERB_APP_KEY` dentro de `PRODUCTION_ENV` deve ser exatamente igual ao Secret
de mesmo nome usado durante o build.

No primeiro deploy, `PLATFORM_ADMIN_PASSWORD` precisa ter pelo menos 12
caracteres. O comando idempotente cria um master sem empresa; no login ele será
direcionado ao cadastro da primeira empresa. Depois, altere a senha pelo sistema
e remova `PLATFORM_ADMIN_PASSWORD` do `PRODUCTION_ENV`. Deploys seguintes não
redefinem a senha existente.

## Publicação e rollback

Pushes em `master` executam testes, constroem `sha-<commit>`, publicam no GHCR,
fazem backup lógico, aplicam migrations, garantem o master e verificam `/up`.
Se o novo container falhar no health check, a imagem anterior volta
automaticamente. Migrations não sofrem rollback automático; o dump anterior
fica no volume `app-storage`, em `storage/app/private/backups`.

Rollback manual de imagem:

```bash
cd /root/apps/salescontrol-platform
./deploy/rollback.sh
```

O exemplo já ativa Reverb com `COMPOSE_PROFILES=realtime`. Para documentos, use
`COMPOSE_PROFILES=realtime,documentos` somente depois de configurar e validar o
SFTP.

O arquivo `deploy/nginx/licas-consultoria.conf.example` fica preparado para a
etapa seguinte de Nginx/Certbot. Antes de habilitá-lo, confirme DNS, certificado,
`nginx -t`, acesso local a `127.0.0.1:8010/up` e o proxy WebSocket `/app/`.
