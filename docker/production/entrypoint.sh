#!/bin/sh
set -eu

mkdir -p \
  bootstrap/cache \
  storage/app/private \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs

chown www-data:www-data \
  bootstrap/cache \
  storage \
  storage/app \
  storage/app/private \
  storage/app/public \
  storage/framework \
  storage/framework/cache \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs

# Cada container recebe as variáveis somente no runtime. Gerar estes caches no
# start mantém a imagem reutilizável entre instalações e acelera o Laravel.
su-exec www-data php artisan config:cache --no-interaction
su-exec www-data php artisan view:cache --no-interaction

case "${1:-web}" in
  web)
    exec /usr/bin/supervisord -c /etc/supervisord.conf
    ;;
  *)
    exec su-exec www-data "$@"
    ;;
esac
