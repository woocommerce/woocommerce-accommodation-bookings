<?php
/**
 * The bootstrap file for PHPUnit tests for the WooCommerce Accommodation Bookings plugin.
 * Starts up WP_Mock and requires the files needed for testing.
 */

$plugin_dir = dirname( dirname( dirname( __FILE__ ) ) ) . '/';
// First we need to load the composer autoloader so we can use WP Mock.
require_once $plugin_dir . '/vendor/autoload.php';
// Now call the bootstrap method of WP Mock.
WP_Mock::bootstrap();

define( 'WC_VERSION', 3.9 );

require __DIR__ . '/../../includes/class-wc-accommodation-booking.php';
