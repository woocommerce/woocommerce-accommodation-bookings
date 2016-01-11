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
		add_filter( 'woocommerce_bookings_get_start_date_with_time', array( $this, 'add_checkin_time_to_booking_start_time' ), 10, 2 );
		add_filter( 'woocommerce_bookings_get_end_date_with_time', array( $this, 'add_checkout_time_to_booking_end_time' ), 10, 2 );
		add_filter( 'get_booking_products_terms', array( $this, 'add_accommodation_to_booking_product_terms' ) );
	}

	/**
	 * Hooks into woocommerce_bookings_product_types and adds our new type
	 * @param array $types Array of WooCommerce Bookings Product Types
	 */
	public function add_product_type( $types ) {
		$types[] = 'accommodation-booking';
		return $types;
	}

	public function add_checkin_time_to_booking_start_time( $date, $booking ) {
		$product = wc_get_product( $booking->product_id );
		if ( empty( $product ) || 'accommodation-booking' !== $product->product_type ) {
			return $date;
		}

		$check_in = get_option( 'woocommerce_accommodation_bookings_check_in', '' );
		$date_format = apply_filters( 'woocommerce_bookings_date_format', wc_date_format() );
		$time_format = apply_filters( 'woocommerce_bookings_time_format', ', ' . wc_time_format() );

		return date_i18n( $date_format, $booking->start ) . date_i18n( $time_format, strtotime( "Today " . $check_in ) );
	}

	public function add_checkout_time_to_booking_end_time( $date, $booking ) {
		$product = wc_get_product( $booking->product_id );
		if ( empty( $product ) || 'accommodation-booking' !== $product->product_type ) {
			return $date;
		}

		$check_out = get_option( 'woocommerce_accommodation_bookings_check_out', '' );
		$date_format = apply_filters( 'woocommerce_bookings_date_format', wc_date_format() );
		$time_format = apply_filters( 'woocommerce_bookings_time_format', ', ' . wc_time_format() );

		return date_i18n( $date_format, $booking->end ) . date_i18n( $time_format, strtotime( "Today " . $check_out ) );
	}

	/**
	 * Adds 'accommodation-booking' to the list of valid product types/terms
	 */
	public function add_accommodation_to_booking_product_terms( $terms ) {
		$terms[] = 'accommodation-booking';
		return $terms;
	}

}

new WC_Accommodation_Booking;
