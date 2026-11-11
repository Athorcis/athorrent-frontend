#!/bin/bash

set -euo pipefail

REGISTRY="${REGISTRY:-docker.io}"
REGISTRY_NAMESPACE="${REGISTRY_NAMESPACE:-}"
REGISTRY_NAMESPACE="${REGISTRY_NAMESPACE,,}"
PUBLISH_BRANCH="${PUBLISH_BRANCH:-master}"
SOURCE_REVISION="${SOURCE_REVISION:-$(git rev-parse HEAD)}"
IMAGE_PREFIX="${REGISTRY}/${REGISTRY_NAMESPACE:+${REGISTRY_NAMESPACE}/}athorrent-frontend"
ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
images=(php nginx)
source_images=("athorrent-php:local" "athorrent-nginx:local")
SBOM_DIR="${ROOT_DIR}/sbom"
project_sbom="${SBOM_DIR}/project.cdx.json"

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

for index in "${!images[@]}"
do
    image_ref="${IMAGE_PREFIX}-${images[$index]}"
    source_image="${source_images[$index]}"
    sbom_file="${SBOM_DIR}/${source_image//:/-}.cdx.json"

    docker_tags=("${SOURCE_REVISION}" "$PUBLISH_BRANCH" "${valid_tags[@]}")

    for tag in "${docker_tags[@]}"
    do
        docker image tag "$source_image" "${image_ref}:${tag}"
        docker push "${image_ref}:${tag}"
    done

    if [ "${CI:-}" = "true" ]; then
        digest="$(oras manifest fetch --descriptor "${image_ref}:${SOURCE_REVISION}" | jq -er '.digest')"
        oras attach \
            --artifact-type application/vnd.cyclonedx+json \
            "${image_ref}@${digest}" \
            "${sbom_file}:application/vnd.cyclonedx+json"
        echo "Attached CycloneDX SBOM to ${image_ref}@${digest}"

        oras attach \
            --artifact-type application/vnd.cyclonedx+json \
            "${image_ref}@${digest}" \
            "${project_sbom}:application/vnd.cyclonedx+json"
        echo "Attached project CycloneDX SBOM to ${image_ref}@${digest}"
    fi
done
