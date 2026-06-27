#!/bin/bash

set -eu

upEnv() {
    local env=${1:-dev}
    local addon=${2:-}
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
        local addonPath
        addonPath=$(dirname -- "$scriptDir")/deployment/base.$addon.yml

        if [ ! -f  "$addonPath" ]
        then
            echo "addon $addon is not supported" >&2
            exit 1
        fi

        composeArgs+=("-f" "$addonPath")
    fi

    docker compose "${composeArgs[@]}" create --build --pull always
    local initArgs=()

    if [ "$env" = test ]
    then
        initArgs+=(--reset=full)
    fi

    docker compose "${composeArgs[@]}" run --user www-data --rm php php bin/console app:init "${initArgs[@]}" -v

    docker compose "${composeArgs[@]}" up -d
}

upEnv "$@"
