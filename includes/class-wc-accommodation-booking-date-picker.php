<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks into the Bookings Date Picker so we can customize it a bit.
 */
class WC_Accommodation_Booking_Date_Picker {

	/**
	 * Hooks into WooCommerce Bookings...
	 */
	public function __construct() {
		add_filter( 'woocommerce_bookings_date_picker_start_label', array( $this, 'start_label' ) );
		add_filter( 'woocommerce_bookings_date_picker_end_label', array( $this, 'end_label' ) );
		add_filter( 'woocommerce_booking_form_get_posted_data', array( $this, 'add_accommodation_posted_data' ), 10 , 3 );
		add_filter( 'woocommerce_bookings_booked_day_blocks', array( $this, 'add_partially_booked_dates' ), 10 , 3 );
	}

	/**
	 * Add custom start and end dat to booking data
	 * @since 1.0.7
	 *
	 * @param $data
	 * @param $product
	 * @param $total_duration
	 *
	 * @return mixed
	 */
	public function add_accommodation_posted_data( $data, $product, $total_duration ) {

		$check_in = get_option( 'woocommerce_accommodation_bookings_check_in', '' );
		$check_out = get_option( 'woocommerce_accommodation_bookings_check_out', '' );

		if ( 'night' === $product->get_duration_unit() ) {
			$data['_start_date'] = strtotime( "{$data['_year']}-{$data['_month']}-{$data['_day']} $check_in" );
			$data['_end_date']   = strtotime( "+{$total_duration} day $check_out", $data['_start_date'] );
			$data['_all_day']    = 0;
		}

		return $data;
	}

	/**
	 * Changes the start label to "Check-in"
	 * @param  string $label
	 * @return string
	 */
	public function start_label( $label ) {
		return __( 'Check-in', 'woocommerce-accommodation-bookings' );
	}

	/**
	 * Changes the end label to "Check-out"
	 */
	public function end_label( $label ) {
		return __( 'Check-out', 'woocommerce-accommodation-bookings' );
	}

	/**
	 * Add partially booked accomodation bookings
	 * @param array $booked_data_array
	 */
	public function add_partially_booked_dates( $booked_data_array, $product ) {

		$partially_booked_days = array();
		$booked_day_counts =  array();
		if ( 'night' !== $product->get_duration_unit() ) {
			return $booked_data_array;
		}

		$existing_bookings = WC_Bookings_Controller::get_bookings_for_objects( array( $product->id ) );
		$available_quantity = $product->get_available_quantity( null );
		// Use the existing bookings to find days which are partially booked
		foreach ( $existing_bookings as $booking ) {

			$check_date  = $booking->start;
			// Loop over all booked days in this booking
			while ( $check_date < $booking->end ) {

				$js_date = date( 'Y-n-j', $check_date );

				if ( $check_date < current_time( 'timestamp' ) ) {
					$check_date = strtotime( '+1 day', $check_date );
					continue;
				}

				if ( isset( $booked_day_counts[ $js_date ] ) ) {
					$booked_day_counts[ $js_date ]++;
				} else {
					$booked_day_counts[ $js_date ] = 1;
				}

				$check_date = strtotime( '+1 day', $check_date );
			}
		}

		foreach ( $booked_day_counts as $booked_date => $number_of_bookings ) {
			if ( $number_of_bookings < $available_quantity ) {
				$partially_booked_days[ $booked_date ][0] = true;
			}
		}

		$booked_data_array['partially_booked_days'] += $partially_booked_days;

		return $booked_data_array;
	}
}

new WC_Accommodation_Booking_Date_Picker;
