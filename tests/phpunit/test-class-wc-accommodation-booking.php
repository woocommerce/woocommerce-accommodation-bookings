<?php

use PHPUnit\Framework\TestCase;

/**
 * The TestWCAccommodationBookin class tests the functions on file class-wc-accommodation-booking.php.
 */
class TestWCAccommodationBooking extends TestCase {
	/**
	 * Set up required options before each test.
	 *
	 * @since 1.1.15
	 *
	 * @return void
	 */
	public function setUp():void {
		\WP_Mock::setUp();
	}
	/**
	 * Remove required options after each test.
	 *
	 * @since 1.1.15
	 *
	 * @return void
	 */
	public function tearDown():void {
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
	 * @param array $product        Test input data.
	 * @param array $expected    Test expected result.
	 *
	 * @since 1.1.15
	 */
	public function testFilterResourceDurationDisplayString( $product, $expected ) {
		\WP_Mock::userFunction(
			'__',
			array(
				'args'   => array( 'night', 'woocommerce-accommodation-bookings' ),
				'return' => 'nightTranslationString',
			)
		);
		$accommodation_booking = new \WC_Accommodation_Booking();
		$duration_display      = $accommodation_booking->filter_resource_duration_display_string( $product->get_duration_unit(), $product );
		$this->assertEquals( $expected, $duration_display );
	}

	/**
	 * @return array
	 */
	public function FilterResourceDurationDisplayStringProvider() {
		$mock_product_accommodation_booking = \Mockery::mock( 'overload:WC_Product_Accommodation_Booking' );
		$mock_product_accommodation_booking
			->shouldReceive( 'get_duration_unit' )
			->andReturn( 'night' );
		$mock_product_booking = \Mockery::mock( 'overload:WC_Product_Booking' );
		$mock_product_booking
			->shouldReceive( 'get_duration_unit' )
			->andReturn( 'durationString' );
		return [
			[
				$mock_product_accommodation_booking,
				'nightTranslationString',
			],
			[
				$mock_product_booking,
				'durationString',
			],
		];
	}
}
