<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Overwrites pieces of the booking details (edit booking/manage booking) page
 */
class WC_Accommodation_Booking_Admin_Meta_Box {
	public function __construct() {
		add_filter( 'woocommerce_booking_admin_start_time', array( $this, 'start_time' ), 10, 2 );
		add_filter( 'woocommerce_booking_admin_end_time', array( $this, 'end_time' ), 10, 2 );
	}

	public function start_time( $time, $post_id ) {
		$product = wc_get_product( get_post_meta( $post_id, '_booking_product_id', true ) );
		if ( 'accommodation-booking' !== $product->product_type ) {
			return $time;
		}
		$check_in = get_option( 'woocommerce_accommodation_bookings_check_in', '' );
		return date_i18n( 'G:i', strtotime( 'Today ' . $check_in ) );
	}

	public function end_time( $time, $post_id ) {
		$product = wc_get_product( get_post_meta( $post_id, '_booking_product_id', true ) );
		if ( 'accommodation-booking' !== $product->product_type ) {
			return $time;
		}
		$check_out = get_option( 'woocommerce_accommodation_bookings_check_out', '' );
		return date_i18n( 'G:i', strtotime( 'Today ' . $check_out ) );
	}
}


new WC_Accommodation_Booking_Admin_Meta_Box;
