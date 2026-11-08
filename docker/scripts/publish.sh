#!/bin/bash

set -euo pipefail

REGISTRY="${REGISTRY:-docker.io}"
PUBLISH_BRANCH="${PUBLISH_BRANCH:-master}"
SOURCE_REVISION="${SOURCE_REVISION:-$(git rev-parse HEAD)}"
IMAGE_PREFIX="${REGISTRY}/athorrent-frontend"

mapfile -t tags < <(git tag --points-at "$SOURCE_REVISION")
valid_tags=()
for tag in "${tags[@]}"
do
    if [[ ! "$tag" =~ ^[A-Za-z0-9_][A-Za-z0-9_.-]{0,127}$ ]]
    then
        echo "Skipping Git tag '$tag': not a valid Docker image tag" >&2
        continue
    fi

    valid_tags+=("$tag")
done

if [ "${#valid_tags[@]}" -eq 0 ]
then
    echo "Commit $SOURCE_REVISION has no valid Git tags; skipping publish"
    exit 0
fi

if [ -n "${REGISTRY_USERNAME:-}" ] && [ -n "${REGISTRY_PASSWORD:-}" ]
then
    printf '%s' "$REGISTRY_PASSWORD" | docker login "$REGISTRY" \
        --username "$REGISTRY_USERNAME" \
        --password-stdin
elif [ -n "${REGISTRY_USERNAME:-}" ] || [ -n "${REGISTRY_PASSWORD:-}" ]
then
    echo "Both REGISTRY_USERNAME and REGISTRY_PASSWORD must be set to log in" >&2
    exit 1
fi

images=(php nginx)
source_images=("athorrent-php:local" "athorrent-nginx:local")

for index in "${!images[@]}"
do
    image_ref="${IMAGE_PREFIX}-${images[$index]}"
    source_image="${source_images[$index]}"

    docker_tags=("${SOURCE_REVISION}" "$PUBLISH_BRANCH" "${valid_tags[@]}")

    for tag in "${docker_tags[@]}"
    do
        docker image tag "$source_image" "${image_ref}:${tag}"
        docker push "${image_ref}:${tag}"
    done
done
