#!/bin/bash

set -euo pipefail

docker/scripts/up-env.sh test

cd tests
yarn install --immutable
yarn lint
yarn cypress install
yarn test:e2e
