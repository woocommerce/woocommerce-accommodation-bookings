#!/usr/bin/env bash
# Each PHPUnit bootstrap reinstalls the dedicated test tables; each case rolls back its fixtures.
set -euo pipefail
if [ "$#" -lt 1 ]; then echo "Usage: $0 <test-directory> [PHPUnit arguments]" >&2; exit 1; fi
integration_root="$(cd "$1" && pwd)"
shift
export WP_TESTS_DIR="$integration_root/wordpress-tests-lib"
export WC_DIR="$integration_root/dependencies/woocommerce"
export BOOKINGS_DIR="$integration_root/dependencies/woocommerce-bookings"
export ADDONS_DIR="$integration_root/dependencies/woocommerce-product-addons"
cd "$(dirname "$0")/../.."
exec php vendor/bin/phpunit -c phpunit-integration.xml "$@"
