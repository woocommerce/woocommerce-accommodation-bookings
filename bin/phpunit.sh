#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f vendor/autoload.php ]]; then
    echo "Install the locked PHP dependencies with composer install first." >&2
    exit 1
fi

install_path=$(pnpm --silent run wp-env:test status --json |
    node -e 'const { installPath } = JSON.parse(require("node:fs").readFileSync(0, "utf8")); if (!installPath) process.exit(1); console.log(installPath);')
compose=(docker compose -f "$install_path/docker-compose.yml")
if [[ -z "$("${compose[@]}" ps -q --status running cli)" ]]; then
    echo "Start the tests site with pnpm run env:test:start first." >&2
    exit 1
fi

# Pass PHPUnit its own options and exit status directly, without wp-env parsing them.
exec "${compose[@]}" exec -T -u "$(id -u):$(id -g)" -w "/var/www/html/wp-content/plugins/${PWD##*/}" \
    cli php vendor/bin/phpunit -c phpunit.xml --fail-on-empty-test-suite "$@"
