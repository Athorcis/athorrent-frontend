#syntax=docker/dockerfile:1.7-labs

ARG PHP_VERSION=8.4.4
ARG COMPOSER_VERSION=2.8.6
ARG NVM_VERSION=0.40.1
ARG NODEJS_VERSION=22.14.0
ARG NGINX_VERSION=1.27.4

ARG UID=33
ARG GID=33
ARG USER=$UID:$GID

FROM php:${PHP_VERSION}-fpm AS base

RUN set -eux ;\
    docker-php-ext-enable opcache ;\
    curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - | sh -s \
      apcu bcmath intl pcntl pdo_mysql pdo_pgsql pdo_sqlite sockets sysvsem

COPY docker/conf/php/base.ini /usr/local/etc/php/conf.d/

WORKDIR /var/www/athorrent

FROM base AS builder

RUN curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - | sh -s \
      @composer-${COMPOSER_VERSION}

ENV NVM_DIR=/usr/local/nvm
ARG NVM_VERSION

RUN set -eux; \
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

ARG USER
USER $USER
WORKDIR /build

FROM builder AS composer-build

ARG UID
ARG GID

COPY composer.json composer.lock /build/

RUN --mount=type=cache,uid=$UID,gid=$GID,target=/var/www/.composer/ \
    set -ex ;\
    composer validate ;\
    composer install --no-dev --no-scripts

COPY --parents src /build/

RUN composer dump-autoload --classmap-authoritative

FROM builder AS yarn-build

ARG UID
ARG GID
ARG USER

COPY --chown=$USER --parents .yarn .yarnrc.yml package.json yarn.lock /build/

RUN --mount=type=cache,uid=$UID,gid=$GID,target=/var/www/.cache \
    --mount=type=cache,uid=$UID,gid=$GID,target=/var/www/.yarn \
    --mount=type=cache,uid=$UID,gid=$GID,target=/build/.yarn/cache \
    --mount=type=cache,uid=$UID,gid=$GID,target=/build/.yarn/unplugged \
    whoami ;\
    ls -la /var/www ;\
    yarn install --immutable

COPY --chown=$USER --parents assets scripts/subset-font-awesome.mjs .browserlistrc .postcssrc.json .stylelintrc.json eslint.config.mjs tsconfig.json webpack.config.mjs /build/

RUN --mount=type=cache,uid=$UID,gid=$GID,target=/var/www/.cache \
    --mount=type=cache,uid=$UID,gid=$GID,target=/var/www/.yarn \
    --mount=type=cache,uid=$UID,gid=$GID,target=/build/.yarn/cache \
    --mount=type=cache,uid=$UID,gid=$GID,target=/build/.yarn/unplugged \
    yarn build

FROM builder AS full-build

COPY --chown=$USER --parents bin config migrations public src templates translations .env /var/www/athorrent/

COPY --chown=$USER --from=composer-build /build/vendor /var/www/athorrent/vendor
COPY --chown=$USER --from=yarn-build /build/public/build /var/www/athorrent/public/build

RUN set -eux ;\
    cd /var/www/athorrent ;\
    TRUSTED_HOSTS= php bin/console cache:warmup --env=prod

FROM builder AS dev

USER root

RUN curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - | sh -s \
      spx xdebug

WORKDIR /var/www/athorrent

FROM base AS php

RUN --mount=type=cache,target=/var/cache/apt \
    --mount=type=cache,target=/var/lib/apt \
    set -eux ;\
    apt-get update ;\
    apt-get install -y --no-install-recommends \
        libfcgi-bin ;\
    curl -so /usr/local/bin/php-fpm-healthcheck https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/a2d45de918787f761754b96b94a59f4f6acebc25/php-fpm-healthcheck ;\
    chmod +x /usr/local/bin/php-fpm-healthcheck

HEALTHCHECK --interval=5s --timeout=1s \
    CMD FCGI_CONNECT=localhost:9001 php-fpm-healthcheck --listen-queue=10 || exit 1

COPY docker/conf/php/prod.ini /usr/local/etc/php/conf.d/

FROM nginx-base AS nginx
ARG USER
COPY --chown=$USER --from=full-build /var/www/athorrent/ /var/www/athorrent/

VOLUME ["/var/www/athorrent/var/user"]

FROM nginx:${NGINX_VERSION}-alpine AS nginx-base
COPY docker/conf/nginx.conf /etc/nginx/conf.d/default.conf

FROM nginx-base AS nginx
ARG USER
COPY --chown=$USER --from=full-build /var/www/athorrent/public /var/www/athorrent/public
WORKDIR /var/www/athorrent
