#!/usr/bin/env bash

# Runs PHPCS and reports only the findings that are not in the baseline (SARB).
# Run it from the plugin root. `--tests` checks the tests with phpcs-tests.xml.dist and
# phpcs-tests.baseline; other arguments go to PHPCS (default: the whole plugin).
set -eu

# SARB is a root Composer dependency, or a tools/php-quality one.
SARB=vendor/bin/sarb
[ -f "$SARB" ] || SARB=tools/php-quality/vendor/bin/sarb
BASELINE=phpcs.baseline
if [ "${1:-}" = --tests ]; then
    BASELINE=phpcs-tests.baseline
    shift
    set -- --standard=phpcs-tests.xml.dist "$@"
else
    [ "$#" -gt 0 ] || set -- .
fi

REPORT=$(mktemp)
trap 'rm -f "$REPORT"' EXIT

# PHPCS uses 1-3 for findings. A tool failure must not become a clean baseline.
STATUS=0
php vendor/bin/phpcs -q --report=json "$@" > "$REPORT" || STATUS=$?
case "$STATUS" in
    0|1|2|3) ;;
    *) cat "$REPORT"; exit "$STATUS" ;;
esac

# SARB's diff parser requires Git's standard a/ and b/ prefixes.
GIT_CONFIG_COUNT=${GIT_CONFIG_COUNT:-0}
for SETTING in diff.mnemonicPrefix diff.noprefix; do
    export "GIT_CONFIG_KEY_${GIT_CONFIG_COUNT}=$SETTING"
    export "GIT_CONFIG_VALUE_${GIT_CONFIG_COUNT}=false"
    GIT_CONFIG_COUNT=$((GIT_CONFIG_COUNT + 1))
done
export GIT_CONFIG_COUNT

if [ "${GITHUB_ACTIONS:-false}" = true ]; then
    php "$SARB" remove "$BASELINE" --output-format=github < "$REPORT"
else
    php "$SARB" remove "$BASELINE" < "$REPORT"
fi
