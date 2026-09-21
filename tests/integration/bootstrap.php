<?php
/**
 * Real WordPress, WooCommerce and Bookings integration environment.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

$plugin_dir = dirname( __DIR__, 2 );
require $plugin_dir . '/vendor/autoload.php';

$tests_dir = getenv( 'WP_TESTS_DIR' );
$wc_dir    = getenv( 'WC_DIR' );
$bookings  = getenv( 'BOOKINGS_DIR' );
$addons    = getenv( 'ADDONS_DIR' );
foreach ( array( "$tests_dir/includes/functions.php", "$wc_dir/woocommerce.php", "$bookings/woocommerce-bookings.php", "$addons/woocommerce-product-addons.php" ) as $required ) {
	if ( ! is_file( $required ) ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI startup diagnostic.
		throw new RuntimeException( 'Missing dependency: ' . $required . '. See tests/integration/README.md.' );
	}
}

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $plugin_dir . '/vendor/yoast/phpunit-polyfills' );
require $tests_dir . '/includes/functions.php';
tests_add_filter(
	'muplugins_loaded',
	function () use ( $plugin_dir, $wc_dir, $bookings, $addons ) {
		require $wc_dir . '/woocommerce.php';
		require $addons . '/woocommerce-product-addons.php';
		require $bookings . '/woocommerce-bookings.php';
		// The database is fresh; expose the installed dependency version during plugin load.
		update_option( 'wc_bookings_version', WC_BOOKINGS_VERSION );
		global $wc_accom_plugin;
		require $plugin_dir . '/woocommerce-accommodation-bookings.php';
		// Fixtures never need remote services, and unexpected requests fail closed.
		add_filter(
			'pre_http_request',
			function () {
				return new WP_Error( 'integration_test_http', 'External HTTP disabled during integration tests.' );
			}
		);
	}
);
tests_add_filter(
	'setup_theme',
	function () use ( $wc_dir ) {
		// Match WooCommerce's test bootstrap: clear custom tables as well as WP rows.
		define( 'WP_UNINSTALL_PLUGIN', true );
		define( 'WC_REMOVE_ALL_DATA', true );
		include $wc_dir . '/uninstall.php';
		WC_Install::install();
		$GLOBALS['wp_roles'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reload freshly installed roles.
		wp_roles();
	}
);
require $tests_dir . '/includes/bootstrap.php';
// Reinitialize the installer after the temporary dependency-version check above.
delete_option( 'wc_bookings_version' );
WC_Bookings_Install::init();
// Activation is the public install path across supported Bookings versions.
if ( method_exists( WC_Bookings::instance(), 'activate' ) ) {
	WC_Bookings::instance()->activate();
} else {
	woocommerce_bookings_activate();
}
require_once $plugin_dir . '/includes/admin/class-wc-accommodation-booking-admin-product-settings.php';
require_once __DIR__ . '/fixtures.php';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI version diagnostic.
printf( "Testing PHP %s, WordPress %s, WooCommerce %s, Bookings %s, Add-ons %s.\n", PHP_VERSION, $GLOBALS['wp_version'], WC_VERSION, WC_BOOKINGS_VERSION, WC_PRODUCT_ADDONS_VERSION );
