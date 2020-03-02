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
}
