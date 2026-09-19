#!/usr/bin/env bash
set -euo pipefail

ROOTDIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOTDIR"

report="$(mktemp)"
trap 'rm -f "$report"' EXIT
status=0
php vendor/bin/phpcs -q --report=json "$@" > "$report" || status=$?
case "$status" in
    0|1|2|3) ;;
    *) cat "$report"; exit "$status" ;;
esac

# SARB's diff parser requires Git's standard a/ and b/ prefixes.
GIT_CONFIG_COUNT=${GIT_CONFIG_COUNT:-0}
for SETTING in diff.mnemonicPrefix diff.noprefix; do
    export "GIT_CONFIG_KEY_${GIT_CONFIG_COUNT}=$SETTING"
    export "GIT_CONFIG_VALUE_${GIT_CONFIG_COUNT}=false"
    GIT_CONFIG_COUNT=$((GIT_CONFIG_COUNT + 1))
done
export GIT_CONFIG_COUNT

php tools/php-quality/vendor/bin/sarb remove phpcs.baseline < "$report"
