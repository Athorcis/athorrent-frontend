FROM php:8.1.3-fpm AS base

RUN set -ex ;\
    apt-get update ;\
    apt-get install -y --no-install-recommends \
        libicu-dev ;\
    docker-php-ext-install -j $(nproc) bcmath intl opcache pdo_mysql sockets

RUN set -ex ;\
    pecl install apcu ;\
    docker-php-ext-enable apcu ;\
    pecl clear-cache

FROM base AS composer-build

WORKDIR /build

RUN set -ex ;\
    apt-get update ;\
    apt-get install -y \
        unzip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /build

RUN composer install --classmap-authoritative

FROM node:16-alpine AS yarn-build

WORKDIR /build

COPY . /build

RUN set -ex ;\
    yarn install ;\
    yarn build

FROM base

COPY . /var/www/athorrent

COPY --from=composer-build /build/vendor /var/www/athorrent/vendor
COPY --from=yarn-build /build/public/build /var/www/athorrent/public/build

RUN chown -R www-data:www-data /var/www/athorrent/var

VOLUME ["/var/www/athorrent/var/user"]
