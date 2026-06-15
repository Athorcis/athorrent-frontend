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

    if [ "$env" = test ]
    then
        docker compose -f "$composeFilePath" run --rm --build -e APP_INIT=true php php bin/console tests:data:reset -v
    fi

    docker compose -f "$composeFilePath" up --pull always -d --build
}

upEnv "$@"
