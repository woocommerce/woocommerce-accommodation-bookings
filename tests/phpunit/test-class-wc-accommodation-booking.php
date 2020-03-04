<?php
namespace WooCommerce\AccommodationBookings\Test;

use WooCommerce\AccommodationBookings\Test\Factory as Factory;
//use PHPUnit\Framework\TestCase;

/**
 * The TestWCAccommodationBookin class tests the functions on file class-wc-accommodation-booking.php.
 */
class TestWCAccommodationBooking extends \WP_Mock\Tools\TestCase {
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

		$accommodation_booking = new \WC_Accommodation_Booking();
		$this->assertInstanceOf( 'WC_Accommodation_Booking', $accommodation_booking );
	}
	/**
	 * @test Test function changes duration display for accommodation bookings and duration 'night'.
	 *
	 * @since 1.1.15
	 */
	public function test_filter_resource_duration_display_string() {
		$product_accommodation_booking = Factory\Product_Accommodation_Booking::create();
		$expected_duration_display     = 'noche';
		\WP_Mock::userFunction(
			'__',
			array(
				'args'   => array( 'night', 'woocommerce-accommodation-bookings' ),
				'return' => 'noche',
			)
		);
		$accommodation_booking = new \WC_Accommodation_Booking();
		$duration_display      = $accommodation_booking->filter_resource_duration_display_string( $product_accommodation_booking->get_duration_unit(), $product_accommodation_booking );
		$this->assertEquals( $expected_duration_display, $duration_display );

		$product_booking           = new \WC_Product_Booking();
		$expected_duration_display = $product_booking->get_duration_unit();
		$duration_display          = $accommodation_booking->filter_resource_duration_display_string( $product_booking->get_duration_unit(), $product_booking );
		$this->assertEquals( $expected_duration_display, $duration_display );

	}

}
