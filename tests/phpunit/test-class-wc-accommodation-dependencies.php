<?php
/**
 * Tests for the WooCommerce Bookings dependency check.
 *
 * @package woocommerce-accommodation-bookings
 */

use PHPUnit\Framework\TestCase;

/**
 * Accommodation Bookings must refuse to load on a Bookings release older than its minimum.
 *
 * @version 1.3.16
 */
class TestAccommodationDependencies extends TestCase {

	/**
	 * Run the check with Bookings active at the given stored version.
	 *
	 * @param string $bookings_version Value of the wc_bookings_version option.
	 * @return string The exception message, or an empty string when the check passes.
	 */
	private function check_with_bookings_version( $bookings_version ) {
		WP_Mock::setUp();
		WP_Mock::userFunction( 'is_multisite' )->andReturn( false );
		WP_Mock::userFunction( 'get_option' )
			->with( 'active_plugins', array() )
			->andReturn( array( 'woocommerce-bookings/woocommerce-bookings.php' ) );
		WP_Mock::userFunction( 'get_option' )
			->with( 'wc_bookings_version' )
			->andReturn( $bookings_version );
		require_once __DIR__ . '/../../includes/class-wc-accommodation-dependencies.php';

		try {
			WC_Accommodation_Dependencies::check_dependencies();
			return '';
		} catch ( Exception $e ) {
			return $e->getMessage();
		} finally {
			WP_Mock::tearDown();
		}
	}

	/**
	 * Bookings below 3.10.0 is too old.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider too_old_versions
	 *
	 * @param string $bookings_version Stored Bookings version.
	 */
	public function test_rejects_bookings_older_than_3_10_0( $bookings_version ) {
		$this->assertSame( 'BOOKINGS_VERSION_TOO_OLD', $this->check_with_bookings_version( $bookings_version ) );
	}

	/**
	 * Versions below the minimum, including the previous 1.9.0 floor.
	 *
	 * @return array<string, array{string}>
	 */
	public function too_old_versions() {
		return array(
			'previous floor' => array( '1.9.0' ),
			'last 3.9.x'     => array( '3.9.0' ),
		);
	}

	/**
	 * Bookings 3.10.0 and later pass.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider supported_versions
	 *
	 * @param string $bookings_version Stored Bookings version.
	 */
	public function test_accepts_bookings_3_10_0_and_later( $bookings_version ) {
		$this->assertSame( '', $this->check_with_bookings_version( $bookings_version ) );
	}

	/**
	 * The minimum itself and a later release.
	 *
	 * @return array<string, array{string}>
	 */
	public function supported_versions() {
		return array(
			'minimum' => array( '3.10.0' ),
			'later'   => array( '3.12.1' ),
		);
	}
}
