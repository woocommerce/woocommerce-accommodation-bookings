<?php
/**
 * Tests for accommodation calendar availability cleanup.
 *
 * @package woocommerce-accommodation-bookings
 */

use PHPUnit\Framework\TestCase;

/**
 * Partially booked days must not remain fully booked.
 */
class TestAccommodationDatePicker extends TestCase {

	/**
	 * Keep cleanup scoped to every affected date and resource.
	 */
	public function test_cleanup_preserves_other_dates_and_resources() {
		WP_Mock::setUp();
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', __DIR__ . '/' );
		}
		if ( ! defined( 'WC_BOOKINGS_VERSION' ) ) {
			define( 'WC_BOOKINGS_VERSION', '3.4.0' );
		}
		require_once __DIR__ . '/../../includes/class-wc-accommodation-booking-date-picker.php';
		$product = Mockery::mock( 'overload:WC_Product_Accommodation_Booking' );
		$product->shouldReceive( 'get_duration_unit' )->andReturn( 'night' );
		$product->shouldReceive( 'get_id' )->andReturn( 123 );
		$product->shouldReceive( 'get_check_times' )->andReturn( '14:00' );
		$product->shouldReceive( 'is_resource_assignment_type' )->andReturn( false );
		$product->shouldReceive( 'get_blocks_in_range_for_day' )->andReturn( array() );
		$bookings = array();
		foreach ( array( array( 11, '2030-1-1', '2030-1-2' ), array( 22, '2030-1-3', '2030-1-4' ) ) as $stay ) {
			$booking        = Mockery::mock();
			$booking->start = strtotime( $stay[1] . ' 14:00' );
			$booking->end   = strtotime( $stay[2] . ' 11:00' );
			$booking->shouldReceive( 'get_resource_id' )->andReturn( $stay[0] );
			$bookings[] = $booking;
		}
		$store = Mockery::mock( 'alias:WC_Booking_Data_Store' );
		$store->shouldReceive( 'get_all_existing_bookings' )->andReturn( $bookings );
		WP_Mock::userFunction(
			'wc_bookings_get_time_slots',
			array(
				'return' => static function ( $product, $blocks, $intervals, $resource_id, $from ) {
					return array( $from => array( 'available' => 1 ) );
				},
			)
		);

		$days = array(
			'fully_booked_days' => array(
				'2030-1-1' => array(
					11 => 1,
					22 => 1,
				),
				'2030-1-2' => array( 11 => 1 ),
				'2030-1-3' => array( 22 => 1 ),
				'2030-1-4' => array(
					22 => 1,
					11 => 1,
				),
				'2030-1-5' => array( 11 => 1 ),
			),
		);
		try {
			$result = ( new WC_Accommodation_Booking_Date_Picker() )->update_fully_booked_dates( $days, $product );
			$this->assertSame(
				array(
					'2030-1-1' => array( 22 => 1 ),
					'2030-1-4' => array( 11 => 1 ),
					'2030-1-5' => array( 11 => 1 ),
				),
				$result['fully_booked_days']
			);
			$this->assertSame(
				array(
					'2030-1-1' => array( 11 => 1 ),
					'2030-1-3' => array( 22 => 1 ),
					'2030-1-2' => array( 11 => 1 ),
					'2030-1-4' => array( 22 => 1 ),
				),
				$result['partially_booked_days']
			);
		} finally {
			WP_Mock::tearDown();
		}
	}
}
