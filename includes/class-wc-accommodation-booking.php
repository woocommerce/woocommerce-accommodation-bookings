<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds our new product type to the list of WooCommerce product types.
 */
class WC_Accommodation_Booking {

	/**
	 * Hook into bookings..
	 */
	public function __construct() {
		add_filter( 'woocommerce_bookings_product_types', array( $this, 'add_product_type' ) );
	}

	/**
	 * Hooks into woocommerce_bookings_product_types and adds our new type
	 * @param array $types Array of WooCommerce Bookings Product Types
	 */
	public function add_product_type( $types ) {
		$types[] = 'accommodation-booking';
		return $types;
	}

}

new WC_Accommodation_Booking;
