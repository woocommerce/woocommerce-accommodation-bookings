<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * We need the normal WooCommerce Booking "add to cart" method to fire when our
 * accommodation method does.
 */
class WC_Accommodation_Booking_Cart_Manager {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_accommodation-booking_add_to_cart', array( $this, 'add_to_cart' ), 30 );
	}

	/**
	 * Fire the woocommerce_booking_add_to_cart action
	 */
	function add_to_cart() {
		do_action( 'woocommerce_booking_add_to_cart' );
	}

}

new WC_Accommodation_Booking_Cart_Manager();
