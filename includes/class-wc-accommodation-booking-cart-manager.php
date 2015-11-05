<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * WC_Accommodation_Booking_Cart_Manager class.
 */
class WC_Accommodation_Booking_Cart_Manager {

	public function __construct() {
		add_action( 'woocommerce_accommodation-booking_add_to_cart', array( $this, 'add_to_cart' ), 30 );
	}

	function add_to_cart() {
		do_action( 'woocommerce_booking_add_to_cart' );
	}

}

new WC_Accommodation_Booking_Cart_Manager();
