<?php

namespace WooCommerce\AccommodationBookings\Test\Factory;

class Product_Accommodation_Booking {
	/**
	 * Creates an accommodation product.
	 *
	 * @param WC_Global_Availability_Data_Store $global_availability_data_store Class mock.
	 *
	 * @return \WC_Product_Accommodation_Booking
	 */
	public static function create( $props = array() ) {

		$product_accommodation_booking = new \WC_Product_Accommodation_Booking( array() );

		$product_accommodation_booking->set_defaults();
		$product_accommodation_booking->set_props( $props );
		return $product_accommodation_booking;
	}
}
