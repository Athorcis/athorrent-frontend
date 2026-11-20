#syntax=docker/dockerfile:1.7-labs

ARG NODEJS_VERSION=26.13.1
ARG APP_VERSION=dev
ARG GIT_REVISION=unknown
ARG IMAGE_CREATED=unknown

ARG UID=33
ARG GID=33
ARG RUN_USER=$UID:$GID

ARG BUILD_DIR=/build
ARG RUN_DIR=/var/www/athorrent

ARG SYMFONY_APP_ENV=prod
ARG COMPOSER_ARGS="--no-dev"

FROM docker.io/mlocati/php-extension-installer:2.13.2@sha256:4fd45396c16685266d32bf147a24ecd5e81029d3820295895bf4a90131190ac9 AS pei

FROM docker.io/composer:2.11.2@sha256-12d3bebef9c7c1b0466978288f5f628312786137398101206121e75cb9dd1198 AS composer

FROM docker.io/php:8.5.12-fpm@sha256:baf3ea13a8207fa18e1011bad1016e8b5e59fc974c6966d0efa6f36a16f875f0 AS base

RUN --mount=type=bind,from=pei,source=/usr/bin/install-php-extensions,target=/install-php-extensions \
    /install-php-extensions \
      apcu intl pcntl pdo_mysql pdo_pgsql pdo_sqlite sockets sysvsem uuid

RUN --mount=type=cache,target=/var/cache/apt \
    --mount=type=cache,target=/var/lib/apt \
    apt-get update && apt-get install -y --no-install-recommends zip

COPY docker/conf/php/base.ini /usr/local/etc/php/conf.d/
COPY docker/conf/php/zz-fpm-base.ini /usr/local/etc/php-fpm.d/

ARG RUN_USER
RUN set -xeuo pipefail ;\
    mkdir -p /var/lib/php/session ;\
    chown $RUN_USER /var/lib/php/session
VOLUME ["/var/lib/php/session"]

ARG RUN_DIR
RUN set -xeuo pipefail ;\
    mkdir -p "$RUN_DIR/var/lib" "$RUN_DIR/var/user" ;\
    chown $RUN_USER -R $RUN_DIR
VOLUME ["$RUN_DIR/var/lib", "$RUN_DIR/var/user"]

FROM base AS builder

COPY --from=composer /usr/bin/composer /usr/bin/composer

ARG NODEJS_VERSION

RUN --mount=type=cache,target=/var/cache/apt \
    --mount=type=cache,target=/var/lib/apt \
    set -xeuo pipefail ;\
    curl -sSL -o- https://deb.nodesource.com/setup_${NODEJS_VERSION%%.*}.x | bash ;\
    apt-get install -y --no-install-recommends \
        nodejs=$(apt-cache policy nodejs | grep $NODEJS_VERSION | grep -v Candidate | awk '{ print $1 }') \
    ;\
    curl -s https://repo.yarnpkg.com/install | bash ;\
    mv ~/.yarn/switch/bin/yarn /usr/local/bin/yarn

ARG RUN_USER
USER $RUN_USER

ARG BUILD_DIR
WORKDIR $BUILD_DIR

FROM builder AS composer-build

ARG BUILD_DIR
COPY composer.json composer.lock $BUILD_DIR/

ARG UID
ARG GID
ARG COMPOSER_ARGS
RUN --mount=type=cache,uid=$UID,gid=$GID,target=/var/www/.composer/ \
    set -xeuo pipefail ;\
    composer validate ;\
    composer install ${COMPOSER_ARGS} --no-scripts

COPY --parents src $BUILD_DIR/

RUN composer dump-autoload --classmap-authoritative

FROM builder AS yarn-build

ARG RUN_USER
ARG BUILD_DIR
COPY --chown=$RUN_USER --parents .yarn .yarnrc.yml package.json yarn.lock $BUILD_DIR/

ARG UID
ARG GID
RUN --mount=type=cache,uid=$UID,gid=$GID,target=/var/www/.cache \
    --mount=type=cache,uid=$UID,gid=$GID,target=/var/www/.yarn \
    yarn install --immutable

COPY --chown=$RUN_USER --parents assets .browserlistrc .postcssrc.json .stylelintrc.json eslint.config.ts tsconfig.json webpack.config.mjs $BUILD_DIR/

RUN --mount=type=cache,uid=$UID,gid=$GID,target=/var/www/.cache \
    --mount=type=cache,uid=$UID,gid=$GID,target=/var/www/.yarn \
    yarn build

FROM builder AS lint

ARG RUN_USER
ARG BUILD_DIR
COPY --chown=$RUN_USER composer.json composer.lock $BUILD_DIR/

ARG UID
ARG GID
RUN --mount=type=cache,uid=$UID,gid=$GID,target=/var/www/.composer/ \
    composer install --no-scripts

COPY --chown=$RUN_USER --parents assets/icons bin config public src templates translations .env .php-cs-fixer.dist.php .twig-cs-fixer.dist.php phpstan.dist.neon phpstan-baseline.neon $BUILD_DIR/

RUN set -xeuo pipefail ;\
    APP_ENV=dev php bin/console cache:warmup -v ;\
    APP_ENV=dev composer lint ;\
    touch $BUILD_DIR/.lint-ok

FROM builder AS full-build

ARG RUN_USER
USER $RUN_USER

ARG RUN_DIR
WORKDIR $RUN_DIR

COPY --chown=$RUN_USER --parents assets/icons bin config public src templates translations .env $RUN_DIR/

ARG BUILD_DIR
COPY --chown=$RUN_USER --from=composer-build $BUILD_DIR/vendor $RUN_DIR/vendor
COPY --chown=$RUN_USER --from=yarn-build $BUILD_DIR/public/build $RUN_DIR/public/build
COPY --from=lint $BUILD_DIR/.lint-ok $RUN_DIR/.lint-ok

ARG SYMFONY_APP_ENV
RUN set -xeuo pipefail ;\
    rm -f .lint-ok ;\
    # If we don't create the database first warmup doesn't create all metadata cache \
    APP_SECRET=dummy php bin/console doctrine:schema:create --env=$SYMFONY_APP_ENV -v ;\
    php bin/console cache:warmup --env=$SYMFONY_APP_ENV -v;\
    rm -rf "var/lib/"* "var/log/"*

FROM builder AS dev

USER 0

RUN --mount=type=bind,from=pei,source=/usr/bin/install-php-extensions,target=/install-php-extensions \
    /install-php-extensions spx xdebug

RUN apt-get update && apt-get install -y --no-install-recommends git ca-certificates

RUN cat <<EOF > /usr/local/etc/php/conf.d/xdebug.ini
xdebug.mode=develop,debug
xdebug.idekey=PHPSTORM
xdebug.start_with_request=trigger
xdebug.log=/tmp/xdebug.log
xdebug.log_level=3
xdebug.client_port=9003
xdebug.client_host=172.27.0.1
EOF

# Allow root to write xdebug.log
RUN touch /tmp/xdebug.log && chmod 0666 /tmp/xdebug.log

ARG RUN_DIR
WORKDIR $RUN_DIR

FROM base AS php

ARG APP_VERSION
ARG GIT_REVISION
ARG IMAGE_CREATED
LABEL org.opencontainers.image.title="Athorrent PHP" \
      org.opencontainers.image.description="PHP application runtime for Athorrent" \
      org.opencontainers.image.source="https://github.com/athorcis/athorrent-frontend" \
      org.opencontainers.image.vendor="Athorcis" \
      org.opencontainers.image.licenses="Apache-2.0" \
      org.opencontainers.image.version="${APP_VERSION}" \
      org.opencontainers.image.revision="${GIT_REVISION}" \
      org.opencontainers.image.base.name="docker.io/php:8.5.12-fpm" \
      org.opencontainers.image.created="${IMAGE_CREATED}"

COPY docker/conf/php/prod.ini /usr/local/etc/php/conf.d/
COPY docker/conf/php/zz-fpm-prod.ini /usr/local/etc/php-fpm.d/

ARG RUN_USER
USER $RUN_USER

ARG RUN_DIR
WORKDIR $RUN_DIR

COPY --chown=$RUN_USER --from=full-build $RUN_DIR $RUN_DIR

VOLUME ["$RUN_DIR/var/user"]

FROM docker.io/nginx:1.31.9-alpine-slim@sha256:5bfced78d24d644270bedb0a9968697042e38d5c03fb6354603df025279c1cbe AS nginx-base
COPY docker/conf/nginx/nginx.conf /etc/nginx/conf.d/default.conf
RUN mkdir -p /var/cache/nginx/qb_assets && chown nginx:nginx /var/cache/nginx/qb_assets
VOLUME ["/var/cache/nginx/qb_assets"]

FROM nginx-base AS nginx

ARG APP_VERSION
ARG GIT_REVISION
ARG IMAGE_CREATED
LABEL org.opencontainers.image.title="Athorrent Nginx" \
      org.opencontainers.image.description="Nginx web server for Athorrent" \
      org.opencontainers.image.source="https://github.com/athorcis/athorrent-frontend" \
      org.opencontainers.image.vendor="Athorcis" \
      org.opencontainers.image.licenses="Apache-2.0" \
      org.opencontainers.image.version="${APP_VERSION}" \
      org.opencontainers.image.revision="${GIT_REVISION}" \
      org.opencontainers.image.base.name="docker.io/nginx:1.31.9-alpine-slim" \
      org.opencontainers.image.created="${IMAGE_CREATED}"

ARG RUN_DIR
WORKDIR $RUN_DIR

ARG RUN_USER
COPY --chown=$RUN_USER --from=full-build $RUN_DIR/public $RUN_DIR/public
