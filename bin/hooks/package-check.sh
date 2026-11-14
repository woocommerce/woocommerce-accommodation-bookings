#!/usr/bin/env bash
# Called by build-upload-zip.yml with the built ZIP: PHP compatibility (phpcs-compat.xml.dist) of
# every PHP file the ZIP ships, generated build files included. `pnpm run phpcompat` runs the same
# check locally after building the ZIP.
set -euo pipefail

cd "$(dirname "$0")/../.."

bash bin/phpcompat.sh "$1"
