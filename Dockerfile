# syntax=docker/dockerfile:1.7

FROM php:8.3-fpm-alpine AS php-base

ADD https://github.com/phpredis/phpredis/archive/refs/tags/6.3.0.tar.gz /tmp/phpredis.tar.gz
RUN set -eux; \
    apk add --no-cache \
      curl \
      freetype \
      gmp \
      icu-libs \
      libjpeg-turbo \
      libpng \
      libzip \
      mariadb-client \
      nginx \
      supervisor \
      su-exec \
      tzdata; \
    apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS \
      freetype-dev \
      gmp-dev \
      icu-dev \
      libjpeg-turbo-dev \
      libpng-dev \
      libzip-dev \
      linux-headers; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
      bcmath \
      exif \
      gd \
      gmp \
      intl \
      pcntl \
      pdo_mysql \
      sockets \
      zip; \
    mkdir -p /usr/src/php/ext/redis; \
    tar -xzf /tmp/phpredis.tar.gz --strip-components=1 -C /usr/src/php/ext/redis; \
    docker-php-ext-install -j"$(nproc)" redis; \
    apk del --no-network .build-deps; \
    rm -rf /tmp/* /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

FROM php-base AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM php-base AS vendor-test

WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM node:20.19-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm config set fetch-retries 5 \
    && npm config set fetch-retry-mintimeout 20000 \
    && npm config set fetch-retry-maxtimeout 120000 \
    && npm ci --no-audit --no-fund \
    && test -x node_modules/.bin/vite

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

ARG REALTIME_PUBLIC_KEY
ARG VITE_REVERB_HOST=licas-consultoria.brsolution.tech
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
ENV NODE_OPTIONS=--max-old-space-size=2048
RUN VITE_REVERB_APP_KEY="$REALTIME_PUBLIC_KEY" \
    VITE_REVERB_HOST="$VITE_REVERB_HOST" \
    VITE_REVERB_PORT="$VITE_REVERB_PORT" \
    VITE_REVERB_SCHEME="$VITE_REVERB_SCHEME" \
    npm run build

FROM php-base AS test

COPY docker/production/php.ini /usr/local/etc/php/conf.d/zz-test.ini
COPY . .
COPY --from=vendor-test /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
RUN touch .env \
    && mkdir -p bootstrap/cache storage/framework/{cache/data,sessions,testing,views} storage/logs \
    && php artisan package:discover --ansi

FROM php-base AS production

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

COPY docker/production/php.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/production/php-fpm.conf /usr/local/etc/php-fpm.d/zz-production.conf
COPY docker/production/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/production/supervisord.conf /etc/supervisord.conf
COPY docker/production/entrypoint.sh /usr/local/bin/production-entrypoint

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN composer check-platform-reqs --no-dev \
    && composer dump-autoload --no-dev --classmap-authoritative --no-interaction \
    && mkdir -p \
      bootstrap/cache \
      storage/app/private \
      storage/app/public \
      storage/framework/cache/data \
      storage/framework/sessions \
      storage/framework/views \
      storage/logs \
      /run/nginx \
    && ln -sfn ../storage/app/public public/storage \
    && chown -R www-data:www-data bootstrap/cache storage /run/nginx \
    && chmod +x /usr/local/bin/production-entrypoint \
    && rm -rf tests

EXPOSE 8080 8081
ENTRYPOINT ["production-entrypoint"]
CMD ["web"]

HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=4 \
    CMD wget -qO- http://127.0.0.1:8080/up >/dev/null || exit 1
