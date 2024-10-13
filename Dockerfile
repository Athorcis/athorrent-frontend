ARG PHP_VERSION=8.3.11
ARG COMPOSER_VERSION=2.7.9
ARG NODEJS_VERSION=22.8.0
ARG NGINX_VERSION=1.27.2

FROM php:${PHP_VERSION}-fpm AS base

RUN curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - | sh -s \
      apcu bcmath intl opcache pdo_mysql sockets

FROM composer:$COMPOSER_VERSION AS composer

FROM base AS composer-build

WORKDIR /build

RUN set -ex ;\
    apt-get update ;\
    apt-get install -y \
        unzip ;\
    apt-get clean

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . /build

RUN --mount=type=cache,target=/root/.composer/ set -ex ;\
    export COMPOSER_ALLOW_SUPERUSER=1 ;\
    composer install --classmap-authoritative ;\
    composer dump-env prod

FROM node:${NODEJS_VERSION}-alpine AS yarn-build

WORKDIR /build

COPY . /build

RUN --mount=type=cache,target=/root/.yarn \
    --mount=type=cache,target=/build/node_modules/.cache \
    set -ex ;\
    YARN_CACHE_FOLDER=/root/.yarn yarn install --immutable ;\
    yarn build

FROM base AS php

RUN set -ex ;\
    apt-get update ;\
    apt-get install -y --no-install-recommends \
        libfcgi-bin ;\
    curl -so /usr/local/bin/php-fpm-healthcheck https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/a2d45de918787f761754b96b94a59f4f6acebc25/php-fpm-healthcheck ;\
    chmod +x /usr/local/bin/php-fpm-healthcheck ; \
    apt-get clean

HEALTHCHECK --interval=5s --timeout=1s \
    CMD FCGI_CONNECT=localhost:9001 php-fpm-healthcheck --listen-queue=10 || exit 1

COPY . /var/www/athorrent

COPY --from=composer-build /build/vendor /var/www/athorrent/vendor
COPY --from=composer-build /build/.env.local.php /var/www/athorrent/.env.local.php
COPY --from=yarn-build /build/public/build /var/www/athorrent/public/build

RUN mkdir -p /var/www/athorrent/var && chown -R www-data:www-data /var/www/athorrent/var

VOLUME ["/var/www/athorrent/var/user"]

FROM nginx:${NGINX_VERSION}-alpine AS nginx

COPY ./nginx.conf /etc/nginx/sites-enabled/seedbox.athorcis.ovh.conf
COPY --chown=www-data:www-data --from=php /var/www/athorrent/public /var/www/athorrent/public
