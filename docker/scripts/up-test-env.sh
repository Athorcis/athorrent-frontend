#!/bin/bash

set -eu

upTestEnv() {
    local scriptDir
    scriptDir=$(dirname -- "$(readlink -f -- "${BASH_SOURCE[0]}")")

    "$scriptDir/up-env.sh" test

    docker exec athorrent-test-php-1 php bin/console tests:data:reset -v

    if [ "$GITHUB_ACTIONS" = true ]
    then
        nohup docker exec -u root athorrent-test-php-1 php bin/console backend-manager:run -v > backend-manager.log 2>&1 &
    else
        docker exec -u root athorrent-test-php-1 php bin/console backend-manager:run -v
    fi
}

upTestEnv "$@"
