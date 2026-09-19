<?php

use PHPUnit\Framework\TestCase;

/**
 * The TestWCAccommodationBookin class tests the functions on file class-wc-accommodation-booking.php.
 */
class TestWCAccommodationBooking extends TestCase {
	private $timezone;

	/**
	 * Set up required options before each test.
	 *
	 * @since 1.1.15
	 *
	 * @return void
	 */
	public function setUp():void {
		\WP_Mock::setUp();
		$this->timezone = date_default_timezone_get();
		date_default_timezone_set( 'UTC' );
	}
	/**
	 * Remove required options after each test.
	 *
	 * @since 1.1.15
	 *
	 * @return void
	 */
	public function tearDown():void {
		date_default_timezone_set( $this->timezone );
		\WP_Mock::tearDown();
	}

	/** @test - Test that instantiation of the class is working.
	 **/
	public function testIsAnInstanceOfWCAccommodationBooking() {

		$accommodation_booking = new WC_Accommodation_Booking();
		$this->assertInstanceOf( 'WC_Accommodation_Booking', $accommodation_booking );
	}
	/**
	 * @test Test function changes duration display for accommodation bookings and duration 'night'.
	 * Also tests there are no changes on duration display for other product types
	 *
	 * @dataProvider FilterResourceDurationDisplayStringProvider
	 * @param string $product_class Product class to mock.
	 * @param string $unit Duration unit.
	 * @param string $expected Expected label.
	 *
	 * @since 1.1.15
	 */
	public function testFilterResourceDurationDisplayString( $product_class, $unit, $expected ) {
		$product = \Mockery::mock( $product_class );
		$product->shouldReceive( 'get_duration_unit' )->andReturn( $unit );
		\WP_Mock::userFunction(
			'__',
			array(
				'args'   => array( 'night', 'woocommerce-accommodation-bookings' ),
				'return' => 'nightTranslationString',
			)
		);
		$accommodation_booking = new \WC_Accommodation_Booking();
		$duration_display      = $accommodation_booking->filter_resource_duration_display_string( $unit, $product );
		$this->assertSame( $expected, $duration_display );
	}

	/**
	 * @return array
	 */
	public function FilterResourceDurationDisplayStringProvider() {
		return [
			'accommodation night' => [ 'WC_Product_Accommodation_Booking', 'night', 'nightTranslationString' ],
			'accommodation other unit' => [ 'WC_Product_Accommodation_Booking', 'day', 'day' ],
			'ordinary booking night' => [ 'WC_Product_Booking', 'night', 'night' ],
			'ordinary booking label' => [ 'WC_Product_Booking', 'durationString', 'durationString' ],
		];
	}

	/** @dataProvider timestampProvider */
	public function testTimestampKeepsTheDate( $datetime, $time, $expected ) {
		$booking = new class extends WC_Accommodation_Booking {
			public function timestamp( $datetime, $time ) {
				return $this->_get_updated_timestamp_time( $datetime, $time );
			}
		};
		$this->assertSame( $expected, $booking->timestamp( $datetime, $time ) );
	}

	public function timestampProvider() {
		return [
			'no override' => [ '20261231235959', '', '20261231235959' ],
			'missing override' => [ '20260101091500', null, '20260101091500' ],
			'midnight at year end' => [ '20261231235959', '00:00', '20261231000000' ],
			'new year check in' => [ '20270101000000', '14:30', '20270101143000' ],
			'leap day check out' => [ '20280229000000', '09:05', '20280229090500' ],
			'month boundary' => [ '20260401000000', '11:00', '20260401110000' ],
			'time including seconds' => [ '20260918000000', '14:30:45', '20260918143045' ],
		];
	}

	/** @dataProvider overlappingRatesProvider */
	public function testOverlappingRates( $product_class, $enabled, $expected ) {
		$this->assertSame(
			$expected,
			( new WC_Accommodation_Booking() )->disable_overlapping_rates( $enabled, \Mockery::mock( $product_class ) )
		);
	}

	public function overlappingRatesProvider() {
		return [
			'accommodation disables overlap' => [ 'WC_Product_Accommodation_Booking', true, false ],
			'accommodation keeps disabled' => [ 'WC_Product_Accommodation_Booking', false, false ],
			'ordinary booking keeps enabled' => [ 'WC_Product_Booking', true, true ],
			'ordinary booking keeps disabled' => [ 'WC_Product_Booking', false, false ],
		];
	}

	/** @dataProvider formattedDatesProvider */
	public function testCheckTimesUseFilteredFormats( $method, $expected ) {
		$product = \Mockery::mock( 'WC_Product_Accommodation_Booking' );
		$product->shouldReceive( 'get_type' )->andReturn( 'accommodation-booking' );
		\WP_Mock::userFunction( 'wc_get_product', [ 'args' => [ 17 ], 'return' => $product ] );
		\WP_Mock::userFunction( 'wc_date_format', [ 'return' => 'Y-m-d' ] );
		\WP_Mock::userFunction( 'wc_time_format', [ 'return' => 'H:i' ] );
		\WP_Mock::onFilter( 'woocommerce_bookings_date_format' )->with( 'Y-m-d' )->reply( 'd/m/Y' );
		\WP_Mock::onFilter( 'woocommerce_bookings_time_format' )->with( ', H:i' )->reply( ' @ H:i' );
		\WP_Mock::userFunction( 'date_i18n', [ 'return' => function ( $format, $timestamp ) {
			return gmdate( $format, $timestamp );
		} ] );
		$booking = (object) [
			'product_id' => 17,
			'start' => strtotime( '2026-12-31 14:30:00 UTC' ),
			'end' => strtotime( '2027-01-01 00:00:00 UTC' ),
		];
		$this->assertSame( $expected, ( new WC_Accommodation_Booking() )->$method( 'original', $booking ) );
	}

	public function formattedDatesProvider() {
		return [
			'check in' => [ 'add_checkin_time_to_booking_start_time', '31/12/2026 @ 14:30' ],
			'check out' => [ 'add_checkout_time_to_booking_end_time', '01/01/2027 @ 00:00' ],
		];
	}

	/** @dataProvider unrelatedDatesProvider */
	public function testUnrelatedDatesArePreserved( $method, $product_type ) {
		$product = false;
		if ( $product_type ) {
			$product = \Mockery::mock( 'WC_Product_Booking' );
			$product->shouldReceive( 'get_type' )->andReturn( $product_type );
		}
		\WP_Mock::userFunction( 'wc_get_product', [ 'return' => $product ] );
		$this->assertSame( 'original date', ( new WC_Accommodation_Booking() )->$method( 'original date', (object) [ 'product_id' => 17 ] ) );
	}

	public function unrelatedDatesProvider() {
		return [
			'missing check in product' => [ 'add_checkin_time_to_booking_start_time', false ],
			'ordinary check in product' => [ 'add_checkin_time_to_booking_start_time', 'booking' ],
			'missing check out product' => [ 'add_checkout_time_to_booking_end_time', false ],
			'ordinary check out product' => [ 'add_checkout_time_to_booking_end_time', 'booking' ],
		];
	}

	public function testDataStoreRegistrationKeepsUnrelatedStores() {
		$booking = new WC_Accommodation_Booking();
		$this->assertSame( [ 'gift-card' => 'GiftCardStore' ], $booking->register_data_stores( [ 'gift-card' => 'GiftCardStore' ] ) );
		$this->assertEquals(
			[ 'product-booking' => 'BookingStore', 'gift-card' => 'GiftCardStore', 'product-accommodation-booking' => 'BookingStore' ],
			$booking->register_data_stores( [ 'product-booking' => 'BookingStore', 'gift-card' => 'GiftCardStore' ] )
		);
	}

	/**
	 * Static Bookings access needs an alias in a fresh process, separate from the
	 * product doubles used by the other cases.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testSavedCheckTimesUseTheMatchingProductSettings() {
		$product = \Mockery::mock( 'alias:WC_Product_Accommodation_Booking' );
		$product->shouldReceive( 'get_check_times' )->once()->with( 'in', 17 )->andReturn( '14:30' );
		$product->shouldReceive( 'get_check_times' )->once()->with( 'out', 17 )->andReturn( '00:00' );
		\WP_Mock::userFunction( 'wc_get_product', [ 'args' => [ 17 ], 'return' => $product ] );
		foreach ( [ '_booking_product_id' => 17, '_booking_start' => '20261231000000', '_booking_end' => '20270101000000' ] as $key => $value ) {
			\WP_Mock::userFunction( 'get_post_meta', [ 'args' => [ 5, $key, true ], 'return' => $value ] );
		}
		$updates = [];
		\WP_Mock::userFunction( 'update_post_meta', [ 'times' => 2, 'return' => function ( $id, $key, $value ) use ( &$updates ) {
			$this->assertSame( 5, $id );
			$updates[ $key ] = $value;
		} ] );
		( new WC_Accommodation_Booking() )->update_start_end_time( 5 );
		$this->assertSame( [ '_booking_start' => '20261231143000', '_booking_end' => '20270101000000' ], $updates );
	}

	/** @dataProvider icsDatesProvider */
	public function testIcsDateFormatting( $product_class, $expected ) {
		$booking = null;
		if ( $product_class ) {
			$booking = \Mockery::mock();
			$booking->shouldReceive( 'get_product' )->andReturn( \Mockery::mock( $product_class ) );
		}
		$this->assertSame(
			$expected,
			( new WC_Accommodation_Booking() )->disable_ics_formatting_for_accommodation(
				'filtered date', strtotime( '2027-01-01 UTC' ), strtotime( '2026-12-31 14:30:00 UTC' ), $booking
			)
		);
	}

	public function icsDatesProvider() {
		return [
			'no booking' => [ null, 'filtered date' ],
			'accommodation retains filtered date' => [ 'WC_Product_Accommodation_Booking', 'filtered date' ],
			'ordinary booking uses original timestamp' => [ 'WC_Product_Booking', '20261231T143000' ],
		];
	}
}
