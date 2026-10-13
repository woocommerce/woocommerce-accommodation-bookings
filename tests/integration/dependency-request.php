<?php
/**
 * One WordPress request with this plugin active and WooCommerce Bookings not loaded.
 *
 * Bookings stays loaded for the whole PHPUnit process, so test-dependencies.php runs this
 * file in a separate PHP process and reads the JSON it prints on shutdown.
 *
 * Usage: php dependency-request.php <wp-tests-config.php> <front|admin> <inactive|files-missing>
 *
 * "files-missing" lists Bookings in the active_plugins option although its files are not in the
 * plugins directory. The request runs in a transaction it rolls back, so it leaves the test
 * database as it found it.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

if ( 'cli' !== PHP_SAPI || 4 !== $argc ) {
	exit( 1 );
}

$accommodation_test_files_missing = 'files-missing' === $argv[3];
$accommodation_test_plugin_dir    = dirname( __DIR__, 2 );

// The table prefix must be a global, exactly as wp-config.php sets it.
require $argv[1];
require getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

if ( 'admin' === $argv[2] ) {
	define( 'WP_ADMIN', true );
}
$_SERVER['HTTP_HOST']       = WP_TESTS_DOMAIN;
$_SERVER['SERVER_NAME']     = WP_TESTS_DOMAIN;
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['REQUEST_URI']     = '/';

tests_add_filter(
	'muplugins_loaded',
	function () use ( $accommodation_test_files_missing, $accommodation_test_plugin_dir ) {
		global $wpdb, $wc_accom_plugin, $accommodation_test_version_before;

		// Nothing this request writes may reach the tables the test suite is using.
		$wpdb->query( 'START TRANSACTION' );
		$accommodation_test_version_before = get_option( 'wc_accommodation_bookings_version' );

		add_filter(
			'pre_http_request',
			function () {
				return new WP_Error( 'integration_test_http', 'External HTTP disabled during integration tests.' );
			}
		);
		if ( $accommodation_test_files_missing ) {
			update_option( 'active_plugins', array( 'woocommerce-bookings/woocommerce-bookings.php' ) );
		}

		require getenv( 'WC_DIR' ) . '/woocommerce.php';
		require $accommodation_test_plugin_dir . '/woocommerce-accommodation-bookings.php';
	}
);
tests_add_filter(
	'shutdown',
	function () {
		global $wpdb, $wc_accom_plugin, $accommodation_test_version_before;

		ob_start();
		$wc_accom_plugin->dependency_notice();
		$notice = ob_get_clean();
		$result = $wc_accom_plugin->dependencies_check_result;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON for the parent test process.
		echo "\n" . wp_json_encode(
			array(
				'result'          => is_wp_error( $result ) ? $result->get_error_code() : $result,
				'notice'          => trim( wp_strip_all_tags( $notice ) ),
				'notice_hooked'   => false !== has_action( 'admin_notices', array( $wc_accom_plugin, 'dependency_notice' ) ),
				'wp_loaded'       => did_action( 'wp_loaded' ),
				'is_admin'        => is_admin(),
				'active_plugins'  => get_option( 'active_plugins' ),
				'bookings_file'   => file_exists( WP_PLUGIN_DIR . '/woocommerce-bookings/woocommerce-bookings.php' ),
				'bookings_loaded' => class_exists( 'WC_Bookings', false ),
				'product_classes' => class_exists( 'WC_Product_Accommodation_Booking', false ) || class_exists( 'WC_Product_Accommodation_Booking_Resource', false ),
				'admin_classes'   => class_exists( 'WC_Accommodation_Booking_Admin_Panels', false ),
				'version_before'  => $accommodation_test_version_before,
				'version_after'   => get_option( 'wc_accommodation_bookings_version' ),
			)
		);
		$wpdb->query( 'ROLLBACK' );
	},
	PHP_INT_MAX
);

require ABSPATH . 'wp-settings.php';
