#!/bin/bash

if [ -z "$ATHORRENT_PHP_IMAGE" ]
then
    docker/scripts/up-env.sh test
else
    docker/scripts/up-env.sh test ci
fi

cd tests
yarn install --immutable
yarn cypress install
yarn test:e2e
