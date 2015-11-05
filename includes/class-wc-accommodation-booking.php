<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Accommodation_Booking {

	public function __construct() {
		add_filter( 'woocommerce_bookings_product_types', array( $this, 'add_product_type' ) );
	}

	public function add_product_type( $types ) {
		$types[] = 'accommodation-booking';
		return $types;
	}

}

new WC_Accommodation_Booking;
