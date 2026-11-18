#!/usr/bin/env bash

validator="$(dirname "$0")/../vendor/bin/check_changelog.sh"
if [[ ! -x "$validator" ]]; then
    echo "Run 'composer install' to install the shared extension checks." >&2
    exit 1
fi

exec "$validator" "$@"
