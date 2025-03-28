#!/bin/bash

set -eu

upEnv() {
    local env=$1
    local scriptDir
    scriptDir=$(dirname -- "$(readlink -f -- "${BASH_SOURCE[0]}")")

    local composeFilePath
    composeFilePath=$(dirname -- "$scriptDir")/deployment/$env.yml

    if [ ! -f "$composeFilePath" ]
    then
        echo "env $env is not supported"
        exit 1
    fi

    docker compose -f "$composeFilePath" up -d --build
}

upEnv "$@"
