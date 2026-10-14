<?php
/**
 * Plugin start-up when WooCommerce Bookings is inactive or its files are missing.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

/**
 * Run real requests without Bookings and check what they leave behind.
 *
 * @version x.x.x
 */
class Accommodation_Dependencies_Test extends WP_UnitTestCase {
	// phpcs:disable Squiz.Commenting.FunctionComment -- PHPUnit method names describe the contract under test.

	/**
	 * Without Bookings the plugin must explain itself and leave the site alone:
	 * running install() here would stamp the current version and skip pending migrations for good.
	 *
	 * @dataProvider request_contexts
	 */
	public function test_inactive_bookings_shows_notice_and_leaves_site_untouched( $context ) {
		$request = $this->request_without_bookings( $context, 'inactive' );
		$this->assertSame( array(), $request['active_plugins'] );
		$this->assertSame( 'BOOKINGS_NOT_ACTIVATED', $request['result'] );
		$this->assertTrue( $request['notice_hooked'] );
		$this->assertSame( 'Accommodation Bookings requires WooCommerce Bookings plugin activated.', $request['notice'] );
		$this->assertFalse( $request['product_classes'] );
		$this->assertFalse( $request['admin_classes'] );
		$this->assertFalse( $request['version_before'] );
		$this->assertFalse( $request['version_after'] );
	}

	/**
	 * WordPress loads this plugin before Bookings, so the active-plugins list is all it can check.
	 * When Bookings is listed there but its files are gone, the request must still complete.
	 *
	 * @dataProvider request_contexts
	 */
	public function test_active_bookings_with_missing_files_does_not_break_the_request( $context ) {
		$request = $this->request_without_bookings( $context, 'files-missing' );
		$this->assertSame( array( 'woocommerce-bookings/woocommerce-bookings.php' ), $request['active_plugins'] );
		$this->assertFalse( $request['bookings_file'] );
		$this->assertTrue( $request['result'] );
		$this->assertFalse( $request['notice_hooked'] );
		$this->assertSame( '', $request['notice'] );
		// The plugin went on to load its own files, except the product types that extend Bookings.
		$this->assertFalse( $request['product_classes'] );
		$this->assertSame( 'admin' === $context, $request['admin_classes'] );
	}

	public function request_contexts() {
		return array(
			'storefront' => array( 'front' ),
			'wp-admin'   => array( 'admin' ),
		);
	}

	/** Run one request in a fresh PHP process and return what it reported on shutdown. */
	private function request_without_bookings( $context, $site ) {
		// The configuration this process booted from, wherever the test library keeps it.
		$config  = current( preg_grep( '#[\\\\/]wp-tests-config\.php$#', get_included_files() ) );
		$process = proc_open( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Bookings cannot be unloaded from this process.
			array( PHP_BINARY, __DIR__ . '/dependency-request.php', $config, $context, $site ),
			array(
				1 => array( 'pipe', 'w' ),
				// One stream, so a noisy request cannot fill a pipe nobody is reading.
				2 => array( 'redirect', 1 ),
			),
			$pipes
		);
		$this->assertIsResource( $process );
		$output = stream_get_contents( $pipes[1] );
		$status = proc_close( $process );
		$lines  = explode( "\n", trim( $output ) );
		$report = json_decode( end( $lines ), true );
		$this->assertSame( 0, $status, $output );
		$this->assertIsArray( $report, $output );
		// The request ran WordPress to the end, in the intended context, and really without Bookings.
		$this->assertSame( 1, $report['wp_loaded'] );
		$this->assertSame( 'admin' === $context, $report['is_admin'] );
		$this->assertFalse( $report['bookings_loaded'] );
		return $report;
	}
}
