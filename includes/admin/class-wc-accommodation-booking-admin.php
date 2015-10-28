<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Accommodation booking admin
 */
class WC_Accommodation_Booking_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'product_type_selector' , array( $this, 'product_type_selector' ) );
	}

	/**
	 * Add the accommodation booking product type
	 */
	public function product_type_selector( $types ) {
		$types[ 'accommodation-booking' ] = __( 'Accommodation booking product', 'woocommerce-accommodation-bookings' );
		return $types;
	}

}

new WC_Accommodation_Booking_Admin();
