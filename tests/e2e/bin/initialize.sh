#!/bin/bash
set -euo pipefail

echo "Initializing WooCommerce Accommodation Bookings E2E"

cd "$(dirname "$0")/../../.." || exit 1
# The site runs the packaged plugin (bin/e2e.sh) and mounts the Bookings ZIPs that
# bin/hooks/e2e-prepare.sh downloaded (bin/link_plugins.js).
plugin_slug="woocommerce-accommodation-bookings"
zip_dir="wp-content/e2e-packages"
fixtures_dir="wp-content/e2e-fixtures/e2e"

# Enable pretty permalinks.
pnpm --silent run wp-env:e2e run wordpress chmod -c ugo+w /var/www/html
pnpm --silent run wp-env:e2e run cli wp rewrite structure '/%postname%/' --hard

# Use storefront theme.
pnpm --silent run wp-env:e2e run cli wp theme activate storefront
pnpm --silent run wp-env:e2e run cli wp option update storefront_nux_dismissed 1

# Activate and setup WooCommerce.
pnpm --silent run wp-env:e2e run cli wp wc tool run install_pages --user=1

pnpm --silent run wp-env:e2e run cli wp option update woocommerce_currency "USD"
pnpm --silent run wp-env:e2e run cli wp option update woocommerce_default_country "US:CA"
pnpm --silent run wp-env:e2e run cli wp wc payment_gateway update cod --enabled=true --user=1
pnpm --silent run wp-env:e2e run cli wp option update woocommerce_coming_soon "no"

# Re-seeding a site that kept its database finds the customer already there.
if ! pnpm --silent run wp-env:e2e run cli wp user get customer --field=ID >/dev/null 2>&1; then
    pnpm --silent run wp-env:e2e run cli wp user create customer customer@bookingstestsuite.com --user_pass=password --role=customer
fi

# Install and activate WooCommerce Bookings and Bookings Availability
for plugin in woocommerce-bookings woocommerce-bookings-availability; do
    if [ ! -f "./deploy/e2e-dependencies/${plugin}.zip" ]; then
        echo "Error: deploy/e2e-dependencies/${plugin}.zip not found."
        echo "Run 'pnpm run test:e2e:setup' to download it, or download the zip manually from:"
        echo "https://github.com/woocommerce/${plugin}/releases"
        exit 1
    fi
done
pnpm --silent run wp-env:e2e run cli wp plugin install --force --activate "./${zip_dir}/woocommerce-bookings.zip"
pnpm --silent run wp-env:e2e run cli wp plugin activate "$plugin_slug"
pnpm --silent run wp-env:e2e run cli wp plugin install --force --activate "./${zip_dir}/woocommerce-bookings-availability.zip"

# Local regression fixtures capture mail and block external services.
pnpm --silent run wp-env:e2e run cli mkdir -p /var/www/html/wp-content/mu-plugins
pnpm --silent run wp-env:e2e run cli cp "/var/www/html/${fixtures_dir}/fixtures/local.php" /var/www/html/wp-content/mu-plugins/accommodation-e2e.php
pnpm --silent run wp-env:e2e run cli cp "/var/www/html/${fixtures_dir}/mu-plugins/e2e-log-marker.php" /var/www/html/wp-content/mu-plugins/e2e-log-marker.php
