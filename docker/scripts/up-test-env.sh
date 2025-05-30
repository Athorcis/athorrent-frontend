#!/bin/bash

set -eu

upTestEnv() {
    local scriptDir
    scriptDir=$(dirname -- "$(readlink -f -- "${BASH_SOURCE[0]}")")

    "$scriptDir/up-env.sh" test

    docker exec athorrent-test-php-1 php bin/console tests:data:reset -v
    docker exec -u root athorrent-test-php-1 php bin/console backend-manager:run -v
}

upTestEnv "$@"
