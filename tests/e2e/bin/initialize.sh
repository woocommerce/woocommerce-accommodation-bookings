#!/bin/bash
set -euo pipefail

echo "Initializing WooCommerce Accommodation Bookings E2E"

cd "$(dirname "$0")/../../.." || exit 1
# wp-env mounts the checkout under its folder name, so this also works in worktrees.
plugin_dir="wp-content/plugins/${PWD##*/}"

# Enable pretty permalinks.
pnpm --silent run wp-env:test run wordpress chmod -c ugo+w /var/www/html
pnpm --silent run wp-env:test run cli wp rewrite structure '/%postname%/' --hard

# Use storefront theme.
pnpm --silent run wp-env:test run cli wp theme activate storefront
pnpm --silent run wp-env:test run cli wp option update storefront_nux_dismissed 1

# Activate and setup WooCommerce.
pnpm --silent run wp-env:test run cli wp wc tool run install_pages --user=1

pnpm --silent run wp-env:test run cli wp option update woocommerce_currency "USD"
pnpm --silent run wp-env:test run cli wp option update woocommerce_default_country "US:CA"
pnpm --silent run wp-env:test run cli wp wc payment_gateway update cod --enabled=true --user=1
pnpm --silent run wp-env:test run cli wp option update woocommerce_coming_soon "no"

# Re-seeding a site that kept its database finds the customer already there.
if ! pnpm --silent run wp-env:test run cli wp user get customer --field=ID >/dev/null 2>&1; then
    pnpm --silent run wp-env:test run cli wp user create customer customer@bookingstestsuite.com --user_pass=password --role=customer
fi

# Install and activate WooCommerce Bookings and Bookings Availability
for plugin in woocommerce-bookings woocommerce-bookings-availability; do
    if [ ! -f "./${plugin}.zip" ]; then
        echo "Error: ${plugin}.zip not found in the checkout root."
        echo "Run 'pnpm run env:test:e2e:setup' to download it, or download the zip manually from:"
        echo "https://github.com/woocommerce/${plugin}/releases"
        exit 1
    fi
done
pnpm --silent run wp-env:test run cli wp plugin install --force --activate "./${plugin_dir}/woocommerce-bookings.zip"
pnpm --silent run wp-env:test run cli wp plugin activate "${PWD##*/}"
pnpm --silent run wp-env:test run cli wp plugin install --force --activate "./${plugin_dir}/woocommerce-bookings-availability.zip"

# Local regression fixtures capture mail and block external services.
pnpm --silent run wp-env:test run cli mkdir -p /var/www/html/wp-content/mu-plugins
pnpm --silent run wp-env:test run cli cp "/var/www/html/${plugin_dir}/tests/e2e/fixtures/local.php" /var/www/html/wp-content/mu-plugins/accommodation-e2e.php
pnpm --silent run wp-env:test run cli cp "/var/www/html/${plugin_dir}/tests/e2e/mu-plugins/e2e-log-marker.php" /var/www/html/wp-content/mu-plugins/e2e-log-marker.php
