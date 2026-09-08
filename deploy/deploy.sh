#!/usr/bin/env bash
set -Eeuo pipefail

APP_IMAGE="${1:?Uso: deploy.sh <imagem> <tag>}"
IMAGE_TAG="${2:?Uso: deploy.sh <imagem> <tag>}"
DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="$DEPLOY_DIR/compose.production.yaml"
ENV_FILE="$DEPLOY_DIR/.env"
CURRENT_RELEASE="$DEPLOY_DIR/.current-release"
PREVIOUS_RELEASE="$DEPLOY_DIR/.previous-release"

cd "$DEPLOY_DIR"
exec 9>"$DEPLOY_DIR/.deploy.lock"
flock -n 9 || { echo "Outro deploy está em andamento." >&2; exit 1; }

if [[ ! -s "$ENV_FILE" ]]; then
  echo "Arquivo $ENV_FILE ausente ou vazio." >&2
  exit 1
fi

chmod 600 "$ENV_FILE"
export APP_IMAGE IMAGE_TAG

compose() {
  docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" "$@"
}

rollback() {
  local failed_status=$?

  if [[ -s "$DEPLOY_DIR/.env.previous" ]]; then
    cp "$DEPLOY_DIR/.env.previous" "$ENV_FILE"
    chmod 600 "$ENV_FILE"
    echo "Configuração anterior restaurada." >&2
  fi

  if [[ -s "$CURRENT_RELEASE" ]]; then
    read -r APP_IMAGE IMAGE_TAG < "$CURRENT_RELEASE"
    export APP_IMAGE IMAGE_TAG
    echo "Deploy falhou; restaurando a imagem anterior ${APP_IMAGE}:${IMAGE_TAG}." >&2
    compose up -d --remove-orphans
  else
    echo "Primeiro deploy falhou e não há imagem anterior para restaurar." >&2
  fi

  exit "$failed_status"
}
trap rollback ERR

compose config --quiet
compose pull

echo "Criando backup lógico antes das migrations..."
compose run --rm --no-deps --user www-data --entrypoint sh app -ec '
  if [ "${DB_BACKUP_ENABLED:-true}" != "true" ]; then
    echo "Backup desabilitado por DB_BACKUP_ENABLED."
    exit 0
  fi

  backup_dir="storage/app/private/backups"
  mkdir -p "$backup_dir"
  backup_file="$backup_dir/${DB_DATABASE}_$(date +%Y%m%d_%H%M%S).sql.gz"
  MYSQL_PWD="$DB_PASSWORD" mariadb-dump \
    --host="$DB_HOST" \
    --port="${DB_PORT:-3306}" \
    --user="$DB_USERNAME" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    "$DB_DATABASE" | gzip -9 > "$backup_file"
  test -s "$backup_file"

  if [ -n "${DB_PERSONAL_DATABASE:-}" ] && [ "$DB_PERSONAL_DATABASE" != "$DB_DATABASE" ]; then
    personal_backup="$backup_dir/${DB_PERSONAL_DATABASE}_$(date +%Y%m%d_%H%M%S).sql.gz"
    MYSQL_PWD="$DB_PERSONAL_PASSWORD" mariadb-dump \
      --host="$DB_PERSONAL_HOST" \
      --port="${DB_PERSONAL_PORT:-3306}" \
      --user="$DB_PERSONAL_USERNAME" \
      --single-transaction \
      --quick \
      --skip-triggers \
      "$DB_PERSONAL_DATABASE" | gzip -9 > "$personal_backup"
    test -s "$personal_backup"
  fi

  find "$backup_dir" -type f -name "*.sql.gz" -mtime "+${DB_BACKUP_RETENTION:-14}" -delete
  echo "Backups criados em $backup_dir."
'

echo "Aplicando migrations com a nova imagem..."
compose run --rm app php artisan migrate --force --no-interaction

echo "Garantindo o administrador master inicial..."
compose run --rm app php artisan platform:bootstrap-admin --no-interaction

echo "Atualizando os serviços..."
compose up -d --remove-orphans

container_id="$(compose ps -q app)"
if [[ -z "$container_id" ]]; then
  echo "Container app não foi criado." >&2
  false
fi

for attempt in {1..24}; do
  health="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container_id")"
  if [[ "$health" == "healthy" ]]; then
    break
  fi
  if [[ "$health" == "unhealthy" || "$health" == "exited" || "$health" == "dead" ]]; then
    compose logs --tail=150 app >&2
    false
  fi
  if [[ "$attempt" == 24 ]]; then
    compose logs --tail=150 app >&2
    echo "Timeout aguardando health check." >&2
    false
  fi
  sleep 5
done

curl --fail --silent --show-error --max-time 10 "http://127.0.0.1:${APP_HTTP_PORT:-8010}/up" >/dev/null

if [[ -s "$CURRENT_RELEASE" ]]; then
  cp "$CURRENT_RELEASE" "$PREVIOUS_RELEASE"
fi
printf '%s %s\n' "$APP_IMAGE" "$IMAGE_TAG" > "$CURRENT_RELEASE"

trap - ERR
compose ps
docker image prune -f --filter "until=168h" >/dev/null
echo "Deploy concluído: ${APP_IMAGE}:${IMAGE_TAG}"
