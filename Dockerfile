ARG PHP_VERSION=8.2.7
ARG COMPOSER_VERSION=2.5.8
ARG NODEJS_VERSION=20.5.0

FROM php:${PHP_VERSION}-fpm AS base

RUN set -ex ;\
    apt-get update ;\
    apt-get install -y --no-install-recommends \
        libicu-dev ;\
    docker-php-ext-install -j $(nproc) bcmath intl opcache pdo_mysql sockets ;\
    apt-get clean

RUN set -ex ;\
    pecl install apcu ;\
    docker-php-ext-enable apcu ;\
    pecl clear-cache

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

RUN set -ex ;\
    composer install --classmap-authoritative ;\
    composer dump-env prod

FROM node:${NODEJS_VERSION}-alpine AS yarn-build

WORKDIR /build

COPY . /build

RUN set -ex ;\
    yarn install --frozen-lockfile ;\
    yarn build

FROM base

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
