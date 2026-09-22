#!/usr/bin/env bash
set -euo pipefail

ROOTDIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOTDIR"
if [ "$#" -gt 1 ]; then
    echo "Usage: npm run phpcompat -- [production.zip]" >&2
    exit 1
fi

package_name="$(node -p "require('./package.json').name")"
if [ "$#" -eq 0 ]; then
    npm run build
fi
archive="${1:-$ROOTDIR/$package_name.zip}"

# Keep the checker outside root vendor, which the release build removes.
composer --working-dir=tools/php-quality install --no-interaction --prefer-dist --no-progress
production_dir="$(mktemp -d)"
trap 'rm -rf "$production_dir"' EXIT
unzip -q "$archive" -d "$production_dir"
php tools/php-quality/vendor/bin/phpcs "$production_dir/$package_name" \
    --standard="$ROOTDIR/phpcs-compat.xml.dist" -ps --basepath="$production_dir/$package_name"
