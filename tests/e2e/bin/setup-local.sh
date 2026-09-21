#!/usr/bin/env bash
# Provision a dedicated browser store using the repository's wp-env runner.
set -euo pipefail
cd "$(dirname "$0")/../../.."
: "${BOOKINGS_DIR:?Set BOOKINGS_DIR to an extracted WooCommerce Bookings release}"
[ -f "$BOOKINGS_DIR/woocommerce-bookings.php" ] || { echo 'BOOKINGS_DIR must contain woocommerce-bookings.php' >&2; exit 1; }
if [ -e .wp-env.override.json ]; then echo 'Refusing to overwrite .wp-env.override.json; see tests/e2e/README.md for existing stores.' >&2; exit 1; fi
node - "$BOOKINGS_DIR" <<'JS'
const fs = require('fs');
const path = require('path');
fs.writeFileSync('.wp-env.override.json', JSON.stringify({ port: 9020, phpVersion: '8.4', plugins: ['https://downloads.wordpress.org/plugin/woocommerce.zip'], mappings: { 'wp-content/plugins/woocommerce-bookings': path.resolve(process.argv[2]), 'wp-content/plugins/woocommerce-accommodation-bookings': '.' }, themes: ['https://downloads.wordpress.org/theme/storefront.zip'], config: { ACCOM_E2E: true, DISABLE_WP_CRON: true, WP_DEBUG_DISPLAY: false }, env: { tests: { port: 9021 } } }, null, 2));
JS
npm run build:webpack
npx wp-env start
npx wp-env run tests-cli wp plugin activate woocommerce woocommerce-bookings woocommerce-accommodation-bookings
npx wp-env run tests-cli wp theme activate storefront
npx wp-env run tests-cli wp rewrite structure '/%postname%/'
npx wp-env run tests-cli mkdir -p /var/www/html/wp-content/mu-plugins
npx wp-env run tests-cli cp /var/www/html/wp-content/plugins/woocommerce-accommodation-bookings/tests/e2e/fixtures/local.php /var/www/html/wp-content/mu-plugins/accommodation-e2e.php
npm run test:e2e:reset
PLAYWRIGHT_SKIP_BROWSER_GC=1 npx playwright install chromium
