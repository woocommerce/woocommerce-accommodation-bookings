#!/usr/bin/env bash
set -euo pipefail

cd -P "$(dirname "$0")/.."

if [[ ! -f vendor/autoload.php ]]; then
    echo "Install the locked PHP dependencies with composer install first." >&2
    exit 1
fi

exec pnpm run wp-env:test run cli --env-cwd="wp-content/plugins/${PWD##*/}" -- \
    php vendor/bin/phpunit -c phpunit.xml --fail-on-empty-test-suite "$@"
