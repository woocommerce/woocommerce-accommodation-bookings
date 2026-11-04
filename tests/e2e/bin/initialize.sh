#!/bin/bash
set -euo pipefail

echo "Initializing WooCommerce Accommodation Bookings E2E"

cd "$(dirname "$0")/../../.." || exit 1
# wp-env mounts the checkout under its folder name, so this also works in worktrees.
plugin_dir="wp-content/plugins/${PWD##*/}"

# Enable pretty permalinks.
npm run --silent wp-env:test -- run wordpress chmod -c ugo+w /var/www/html
npm run --silent wp-env:test -- run cli wp rewrite structure '/%postname%/' --hard

# Use storefront theme.
npm run --silent wp-env:test -- run cli wp theme activate storefront
npm run --silent wp-env:test -- run cli wp option update storefront_nux_dismissed 1

# Activate and setup WooCommerce.
npm run --silent wp-env:test -- run cli wp wc tool run install_pages --user=1

npm run --silent wp-env:test -- run cli wp option update woocommerce_currency "USD"
npm run --silent wp-env:test -- run cli wp option update woocommerce_default_country "US:CA"
npm run --silent wp-env:test -- run cli wp wc payment_gateway update cod --enabled=true --user=1
npm run --silent wp-env:test -- run cli wp option update woocommerce_coming_soon "no"

npm run --silent wp-env:test -- run cli wp user create customer customer@bookingstestsuite.com --user_pass=password --role=customer

# Install and activate WooCommerce Bookings
if [ ! -f "./woocommerce-bookings.zip" ]; then
    echo "Error: woocommerce-bookings.zip not found in the checkout root."
    echo "Run 'npm run env:test:e2e:setup' to download it, or download the zip manually from:"
    echo "https://github.com/woocommerce/woocommerce-bookings/releases"
    exit 1
fi
npm run --silent wp-env:test -- run cli wp plugin install --force --activate "./${plugin_dir}/woocommerce-bookings.zip"
npm run --silent wp-env:test -- run cli wp plugin activate "${PWD##*/}"

# Local regression fixtures capture mail and block external services.
npm run --silent wp-env:test -- run cli mkdir -p /var/www/html/wp-content/mu-plugins
npm run --silent wp-env:test -- run cli cp "/var/www/html/${plugin_dir}/tests/e2e/fixtures/local.php" /var/www/html/wp-content/mu-plugins/accommodation-e2e.php
npm run --silent wp-env:test -- run cli cp "/var/www/html/${plugin_dir}/tests/e2e/mu-plugins/e2e-log-marker.php" /var/www/html/wp-content/mu-plugins/e2e-log-marker.php
