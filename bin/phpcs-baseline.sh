#!/usr/bin/env bash
set -euo pipefail

ROOTDIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOTDIR"

report="$(mktemp)"
trap 'rm -f "$report"' EXIT
status=0
php vendor/bin/phpcs -q --report=json > "$report" || status=$?
case "$status" in
    0|1|2|3) ;;
    *) cat "$report"; exit "$status" ;;
esac

php tools/php-quality/vendor/bin/sarb create --input-format=phpcodesniffer-json phpcs.baseline "$@" < "$report"
