#!/bin/bash

echo "Initializing WooCommerce Accommodation Bookings E2E"

# Enable pretty permalinks.
wp-env run tests-wordpress chmod -c ugo+w /var/www/html
wp-env run tests-cli wp rewrite structure '/%postname%/' --hard

# Use storefront theme.
wp-env run tests-cli wp theme activate storefront
wp-env run tests-cli wp option update storefront_nux_dismissed 1

# Activate and setup WooCommerce.
wp-env run tests-cli wp wc tool run install_pages --user=1

wp-env run tests-cli wp option update woocommerce_currency "USD"
wp-env run tests-cli wp option update woocommerce_default_country "US:CA"
wp-env run tests-cli wp wc payment_gateway update cod --enabled=true --user=1

wp-env run tests-cli wp user create customer customer@bookingstestsuite.com --user_pass=password --role=customer

# Install and activate WooCommerce Bookings
wp-env run tests-cli wp plugin install --activate ./wp-content/plugins/woocommerce-accommodation-bookings/woocommerce-bookings.zip
wp-env run tests-cli wp plugin activate woocommerce-accommodation-bookings