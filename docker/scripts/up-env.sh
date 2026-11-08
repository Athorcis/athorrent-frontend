#!/bin/bash

set -euo pipefail

upEnv() {
    local env=${1:-dev}
    local scriptDir
    scriptDir=$(dirname -- "$(readlink -f -- "${BASH_SOURCE[0]}")")
    local repoRoot
    repoRoot=$(dirname -- "$(dirname -- "$scriptDir")")

    export APP_VERSION="${APP_VERSION:-$(git -C "$repoRoot" describe --tags --always --dirty)}"
    export GIT_REVISION="${GIT_REVISION:-$(git -C "$repoRoot" rev-parse HEAD)}"
    export IMAGE_CREATED="${IMAGE_CREATED:-$(date -u +%Y-%m-%dT%H:%M:%SZ)}"

    local composeFilePath
    composeFilePath=$(dirname -- "$scriptDir")/deployment/$env.yml

    if [ ! -f "$composeFilePath" ]
    then
        echo "env $env is not supported" >&2
        exit 1
    fi

    local composeArgs=(-f "$composeFilePath")
    local createArgs=(create)

    if [ "${CI:-}" == true ]
    then
        composeArgs+=("-f" "$(dirname -- "$scriptDir")/deployment/base.ci.yml")
    else
        createArgs+=(--build --pull always)
    fi

    docker compose "${composeArgs[@]}" "${createArgs[@]}"
    local initArgs=()

    if [ "$env" = test ]
    then
        initArgs+=(--reset=full)
    fi

    docker compose "${composeArgs[@]}" run --user www-data --rm php php bin/console app:init "${initArgs[@]}" -v

    docker compose "${composeArgs[@]}" up -d
}

upEnv "$@"
