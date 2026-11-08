#!/bin/bash

set -euo pipefail

docker buildx bake --load \
  --file docker/build-bake.hcl \
  --set php.tags=athorrent-php:local \
  --set nginx.tags=athorrent-nginx:local \
  "$@"
