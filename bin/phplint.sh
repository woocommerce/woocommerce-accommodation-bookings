#!/usr/bin/env bash
set -euo pipefail

ROOTDIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOTDIR"
php tools/php-quality/vendor/bin/parallel-lint --no-colors --short --exclude vendor --exclude tools/php-quality/vendor --exclude node_modules --exclude build .
