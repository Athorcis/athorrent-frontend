#!/bin/bash

set -eu

upEnv() {
    local env=$1
    local addon=$2
    local scriptDir
    scriptDir=$(dirname -- "$(readlink -f -- "${BASH_SOURCE[0]}")")

    local composeFilePath
    composeFilePath=$(dirname -- "$scriptDir")/deployment/$env.yml

    if [ ! -f "$composeFilePath" ]
    then
        echo "env $env is not supported" >&2
        exit 1
    fi

    local composeArgs=(-f "$composeFilePath")

    if [ -n "$addon" ]
    then
        local addonPath=$(dirname -- "$scriptDir")/deployment/base.$addon.yml

        if [ ! -f  "$addonPath" ]
        then
            echo "addon $addon is not supported" >&2
            exit 1
        fi

        composeArgs+=("-f" "$addonPath")
    fi

    local buildArgs=(--build --pull always)

    if [ "$env" = test ]
    then
        docker compose "${composeArgs[@]}" run --rm "${buildArgs[@]}" -e APP_INIT=true php php bin/console tests:data:reset -v
    fi

    docker compose "${composeArgs[@]}" up "${buildArgs[@]}" -d
}

upEnv "$@"
