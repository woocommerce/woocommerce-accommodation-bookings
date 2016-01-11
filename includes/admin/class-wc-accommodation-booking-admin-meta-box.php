<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Accommodation_Booking_Admin_Meta_Box {

	public function __construct() {
		add_filter( 'woocommerce_booking_admin_start_time', array( $this, 'start_time' ), 10, 2 );
		add_filter( 'woocommerce_booking_admin_end_time', array( $this, 'end_time' ), 10, 2 );
	}

	public function start_time( $time, $product_id ) {
		$check_in = get_option( 'woocommerce_accommodation_bookings_check_in', '' );
		return date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_in ) );
	}

	public function end_time( $time, $product_id ) {
		$check_out = get_option( 'woocommerce_accommodation_bookings_check_out', '' );
		return date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_out ) );
	}
}


new WC_Accommodation_Booking_Admin_Meta_Box;
