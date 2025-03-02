ARG PHP_VERSION=8.4.4
ARG COMPOSER_VERSION=2.8.5
ARG NVM_VERSION=0.40.1
ARG NODEJS_VERSION=22.14.0
ARG NGINX_VERSION=1.27.4

FROM php:${PHP_VERSION}-fpm AS base

RUN curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - | sh -s \
      apcu bcmath intl opcache pcntl pdo_pgsql sockets

FROM composer:$COMPOSER_VERSION AS composer

FROM base AS builder

RUN set -ex ;\
    apt-get update ;\
    apt-get install -y \
        unzip ;\
    apt-get clean

COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV NVM_DIR /usr/local/nvm
ARG NVM_VERSION

RUN set -ex; \
    mkdir -p $NVM_DIR ;\
    curl -sSL -o- https://raw.githubusercontent.com/creationix/nvm/v${NVM_VERSION}/install.sh | bash

ARG NODEJS_VERSION

# install node and npm
RUN . $NVM_DIR/nvm.sh \
    && nvm install ${NODEJS_VERSION} \
    && nvm alias default ${NODEJS_VERSION} \
    && nvm use default

# add node and npm to path so the commands are available
ENV NODE_PATH $NVM_DIR/v${NODEJS_VERSION}/lib/node_modules
ENV PATH $NVM_DIR/versions/node/v${NODEJS_VERSION}/bin:$PATH

FROM builder AS composer-build

WORKDIR /build

COPY composer.json composer.lock /build/

RUN --mount=type=cache,target=/root/.composer/ set -ex ;\
    composer validate ;\
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-scripts

COPY src /build/src

RUN composer dump-autoload --classmap-authoritative

FROM builder AS yarn-build

WORKDIR /build

COPY .yarn /build/.yarn
COPY .yarnrc.yml package.json yarn.lock /build/

RUN --mount=type=cache,target=/root/.yarn \
    YARN_CACHE_FOLDER=/root/.yarn yarn install --immutable

COPY assets /build/assets
COPY scripts/subset-font-awesome.mjs / /build/scripts/
COPY .browserlistrc .eslintrc.json .postcssrc.json .stylelintrc.json tsconfig.json webpack.config.mjs /build/

RUN --mount=type=cache,target=/root/.yarn \
    --mount=type=cache,target=/build/node_modules/.cache \
    yarn build

FROM builder AS dev

RUN curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - | sh -s \
      spx xdebug

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
COPY --from=yarn-build /build/public/build /var/www/athorrent/public/build

RUN mkdir -p /var/www/athorrent/var && chown -R www-data:www-data /var/www/athorrent/var

VOLUME ["/var/www/athorrent/var/user"]

FROM nginx:${NGINX_VERSION}-alpine AS nginx-base
COPY ./nginx.conf /etc/nginx/conf.d/default.conf

FROM nginx-base AS nginx
COPY --chown=www-data:www-data --from=php /var/www/athorrent/public /var/www/athorrent/public
