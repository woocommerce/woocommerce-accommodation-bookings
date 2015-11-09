<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Accommodation_Booking_Date_Picker {

	public function __construct() {
		add_filter( 'woocommerce_bookings_date_picker_start_label', array( $this, 'start_label' ) );
		add_filter( 'woocommerce_bookings_date_picker_end_label', array( $this, 'end_label' ) );
	}

	public function start_label( $label ) {
		return __( 'Check-in', 'woocommerce-accommodation-bookings' );
	}

	public function end_label( $label ) {
		return __( 'Check-out', 'woocommerce-accommodation-bookings' );
	}

}

new WC_Accommodation_Booking_Date_Picker;
