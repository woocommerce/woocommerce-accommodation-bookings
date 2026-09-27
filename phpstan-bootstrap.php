<?php
/**
 * Declares runtime-defined constants for PHPStan.
 *
 * PHPStan does not see constants defined inside conditional blocks or
 * functions, or by other plugins. Values are placeholders of the runtime
 * type. The plugin never loads this file.
 *
 * @package woocommerce-accommodation-bookings
 */

// Defined in includes/class-wc-accommodation-bookings-plugin.php.
define( 'WC_ACCOMMODATION_BOOKINGS_INCLUDES_PATH', __DIR__ . '/includes/' );
define( 'WC_ACCOMMODATION_BOOKINGS_PLUGIN_URL', 'https://example.org/wp-content/plugins/woocommerce-accommodation-bookings' );
define( 'WC_ACCOMMODATION_BOOKINGS_MAIN_FILE', __DIR__ . '/woocommerce-accommodation-bookings.php' );

// Defined by WooCommerce Bookings.
define( 'WC_BOOKINGS_PLUGIN_URL', 'https://example.org/wp-content/plugins/woocommerce-bookings' );
define( 'WC_BOOKINGS_VERSION', '0.0.0' );
