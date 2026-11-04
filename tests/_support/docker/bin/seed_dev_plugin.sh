#!/usr/bin/env bash
#
# Plugin-specific part of the development site seed: WooCommerce Bookings, when its zip
# is in the checkout, and one accommodation product. Called by seed_dev.sh after the store is set up.
#
set -euo pipefail

# wp-env mounts the checkout under its folder name, so look it up by the main file.
plugin_dir="$(wp plugin path)/$(dirname "$(wp plugin list --field=file | grep '/woocommerce-accommodation-bookings\.php$')")"

if [ ! -f "${plugin_dir}/woocommerce-bookings.zip" ]; then
	echo "woocommerce-bookings.zip not found: no accommodation product added. Run npm run env:test:e2e:setup to download it, then npm run env:dev:reset."
	exit 0
fi
echo "Installing WooCommerce Bookings..."
wp plugin install --activate "${plugin_dir}/woocommerce-bookings.zip"

echo "Adding an accommodation product..."
# The room factory the integration and E2E fixtures use: nightly price 100, one room.
product_id=$(wp eval "require '${plugin_dir}/tests/integration/fixtures.php'; echo accommodation_test_room( array( 'name' => 'Sea View Room' ) )->get_id();")
echo "Accommodation product: $(wp eval "echo get_permalink( ${product_id} );")"
