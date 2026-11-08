#!/bin/bash

set -euo pipefail

repo_root=$(dirname -- "$(dirname -- "$(dirname -- "$(readlink -f -- "${BASH_SOURCE[0]}")")")")
APP_VERSION="${APP_VERSION:-$(git -C "$repo_root" describe --tags --always --dirty)}"
GIT_REVISION="${GIT_REVISION:-$(git -C "$repo_root" rev-parse HEAD)}"
IMAGE_CREATED="${IMAGE_CREATED:-$(date -u +%Y-%m-%dT%H:%M:%SZ)}"

docker buildx bake --load \
  --file docker/build-bake.hcl \
  --set php.tags=athorrent-php:local \
  --set nginx.tags=athorrent-nginx:local \
  --set "*.args.APP_VERSION=$APP_VERSION" \
  --set "*.args.GIT_REVISION=$GIT_REVISION" \
  --set "*.args.IMAGE_CREATED=$IMAGE_CREATED" \
  "$@"
