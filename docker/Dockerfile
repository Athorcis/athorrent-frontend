#syntax=docker/dockerfile:1.7-labs

ARG PHP_VERSION=8.4.4
ARG COMPOSER_VERSION=2.8.6
ARG NVM_VERSION=0.40.1
ARG NODEJS_VERSION=22.14.0
ARG NGINX_VERSION=1.27.4

FROM php:${PHP_VERSION}-fpm AS base

RUN curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - | sh -s \
      apcu bcmath intl pcntl pdo_pgsql sockets sysvsem

COPY docker/conf/php/base.ini /usr/local/etc/php/conf.d/

WORKDIR /var/www/athorrent

FROM base AS builder

RUN curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - | sh -s \
      @composer-${COMPOSER_VERSION}

ENV NVM_DIR=/usr/local/nvm
ARG NVM_VERSION

RUN set -ex; \
    mkdir -p $NVM_DIR ;\
    curl -sSL -o- https://raw.githubusercontent.com/creationix/nvm/v${NVM_VERSION}/install.sh | bash

ARG NODEJS_VERSION

# install node and npm
RUN set -ex ;\
    . $NVM_DIR/nvm.sh ;\
    nvm install ${NODEJS_VERSION} ;\
    nvm alias default ${NODEJS_VERSION} ;\
    nvm use default ;\
    corepack enable

# add node and npm to path so the commands are available
ENV NODE_PATH=$NVM_DIR/v${NODEJS_VERSION}/lib/node_modules
ENV PATH=$NVM_DIR/versions/node/v${NODEJS_VERSION}/bin:$PATH

WORKDIR /build

FROM builder AS composer-build

COPY composer.json composer.lock /build/

RUN --mount=type=cache,target=/root/.composer/ set -ex ;\
    composer validate ;\
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-scripts

COPY --parents src /build/

RUN composer dump-autoload --classmap-authoritative

FROM builder AS yarn-build

COPY --parents .yarn .yarnrc.yml package.json yarn.lock /build/

RUN --mount=type=cache,target=/root/.yarn \
    YARN_CACHE_FOLDER=/root/.yarn yarn install --immutable

COPY --parents assets scripts/subset-font-awesome.mjs .browserlistrc .postcssrc.json .stylelintrc.json eslint.config.mjs tsconfig.json webpack.config.mjs /build/

RUN --mount=type=cache,target=/root/.yarn \
    --mount=type=cache,target=/build/node_modules/.cache \
    yarn build

FROM builder AS full-build

COPY --parents bin config migrations public src templates translations .env /var/www/athorrent/

COPY --from=composer-build /build/vendor /var/www/athorrent/vendor
COPY --from=yarn-build /build/public/build /var/www/athorrent/public/build

RUN mkdir -p /var/www/athorrent/var

FROM builder AS dev

RUN curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - | sh -s \
      spx xdebug

WORKDIR /var/www/athorrent

FROM base AS php

RUN --mount=type=cache,target=/var/cache/apt \
    --mount=type=cache,target=/var/lib/apt \
    set -ex ;\
    apt-get update ;\
    apt-get install -y --no-install-recommends \
        libfcgi-bin ;\
    curl -so /usr/local/bin/php-fpm-healthcheck https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/a2d45de918787f761754b96b94a59f4f6acebc25/php-fpm-healthcheck ;\
    chmod +x /usr/local/bin/php-fpm-healthcheck

HEALTHCHECK --interval=5s --timeout=1s \
    CMD FCGI_CONNECT=localhost:9001 php-fpm-healthcheck --listen-queue=10 || exit 1

COPY docker/conf/php/prod.ini /usr/local/etc/php/conf.d/

COPY --chown=www-data:www-data --from=full-build /var/www/athorrent/ /var/www/athorrent/

VOLUME ["/var/www/athorrent/var/user"]

FROM nginx:${NGINX_VERSION}-alpine AS nginx-base
COPY docker/conf/nginx.conf /etc/nginx/conf.d/default.conf

FROM nginx-base AS nginx
COPY --chown=www-data:www-data --from=full-build /var/www/athorrent/public /var/www/athorrent/public
WORKDIR /var/www/athorrent
