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
		$check_in  = WC_Product_Accommodation_Booking::get_check_times( 'in' );
		$check_out = WC_Product_Accommodation_Booking::get_check_times( 'out' );

		if ( 'night' === $product->get_duration_unit() ) {
			$data['_start_date'] = strtotime( "{$data['_year']}-{$data['_month']}-{$data['_day']} $check_in" );
			$data['_end_date']   = strtotime( "+{$total_duration} day $check_out", $data['_start_date'] );
			$data['_all_day']    = 0;
		}

		if ( $product->has_resources() && ! $product->is_resource_assignment_type( 'customer' ) ) {
			// Assign an available resource automatically
			$available_bookings = wc_bookings_get_total_available_bookings_for_range( $product, $data['_start_date'], $data['_end_date'], 0, $data['_qty'] );
			if ( is_array( $available_bookings ) ) {
				$data['_resource_id'] = current( array_keys( $available_bookings ) );
				$data['type']         = get_the_title( current( array_keys( $available_bookings ) ) );
			}
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
	 * If a calendar date has check-in ( a booking starts on that date ) if it is feasible we want it marked as
	 * partially booked because some other booking could end on that date.
	 * If a calendar date has check-out ( a booking ends on that date ) if it is feasible we want it marked as
	 * partially booked because some other booking could start on that date.
	 * When it is feasible to mark a date partially booked:
	 *  - for a check-in date we check if a day before that date has any available resources. Only if a day before
	 *    check-in has any avaialble resources it is possible that some booking could end ( had its check-out ) on the
	 *    check-in date we are testing.
	 *  - for a check-out date we chack if that date has any available resources. If it does it means that some other
	 *    booking can start ( can have its check-in ) on the check-out date that we are testing.
	 * Function works in followin steps:
	 *  1. gather all check-in and checko-out dates for product
	 *  2. loop over all dates from (1):
	 *    a. get all available resources for: day before for check-in and current for check-out
	 *    b. test if resources are available and if yes than move fully booked day to partially booked days
	 *
	 * @param array                            $booked_data_array
	 * @param WC_Product_Accommodation_Booking $product
	 */
	public function add_partially_booked_dates( $booked_data_array, $product ) {
		// This function makes sesne only for duration type: night.
		if ( 'night' !== $product->get_duration_unit() ) {
			return $booked_data_array;
		}

		// Start and the end dates of all bookings.
		$check_in_out_times = $this->get_check_in_and_out_times( $product );

		// Go through each checkin and checkout days and mark them as partially booked.
		foreach ( array( 'in', 'out' ) as $which ) {
			foreach ( $check_in_out_times[ $which ] as $resource_id => $times ) {
				foreach ( $times as $time ) {
					$day = date( 'Y-n-j', $time );
					if ( ! empty( $booked_data_array['partially_booked_days'][ $day ][ $resource_id ] ) ) {
						// The day is already partially booked so lets skipp to the next day.
						continue;
					}

					$check_in_time = $product->get_check_times( 'in' );
					if( 'in' === $which ){
						$check_time = strtotime( '-1 day ' . $check_in_time , $time );
					} else {
						$check_time = strtotime( $check_in_time, $time );
					}
					$check = date("F j, Y, g:i a", $check_time );
					// Check freele available blocks for resource. If some are available that means that the day is not fully booked.
					$not_fully_booked = $this->get_product_resource_available_blocks_on_time( $product, $resource_id, $check_time );
					if( $not_fully_booked ) {
						$booked_data_array = $this->move_day_from_fully_to_partially_booked( $booked_data_array, $resource_id, $day );
					}
				}
			}
		}

		return $booked_data_array;
	}

	/**
	 * Calculates array that contains the start and the end time of all bookings for given product.
	 * @param $product
	 */
	private function get_check_in_and_out_times( $product ) {

		$check_in_out_times     = array(
			'in' => array(),
			'out' => array(),
		);

		// Using all existing bookings we will calculate start and end time for each booking.
		// Those times will be considered for switching particular day from full to partially booked days.
		$existing_bookings  = WC_Bookings_Controller::get_all_existing_bookings( $product );
		foreach ( $existing_bookings as $booking ) {

			$resource   = $booking->get_resource_id();
			if( ! array_key_exists( $resource, $check_in_out_times['in'] ) ) {
				$check_in_out_times['in'][ $resource ] = array();
				$check_in_out_times['out'][ $resource ] = array();
			}

			if ( ! in_array( $booking->start, $check_in_out_times['in'][ $resource ] ) ) {
				$check_in_out_times['in'][ $resource ][] = $booking->start;
			}

			if ( ! in_array( $booking->end, $check_in_out_times['out'][ $resource ] ) ) {
				$check_in_out_times['out'][ $resource ][] = $booking->end;
			}
		}

		return $check_in_out_times;
	}

	/**
	 * Get amount of available product resoureces on a specific timestamp
	 * @param $product
	 * @param $resource
	 * @param $date
	 */
	private function get_product_resource_available_blocks_on_time( $product, $resource, $time ) {
		$blocks = $product->get_blocks_in_range_for_day( $time, $time, $resource, array() );
		$available_blocks = wc_bookings_get_time_slots( $product, $blocks, array(), 0, $time, $time );
		return ! empty( $available_blocks[ $time ] ) ? $available_blocks[ $time ][ 'available'] : 0;
	}

	/**
	 * Moves day from fully booked days array to partially booked days array and if the fully booked days is
	 * array for that day is empty ( no assigned resources ) removes that empty day entry
	 * @param $booked_data_array
	 * @param $resource
	 * @param $day
	 */
	private function move_day_from_fully_to_partially_booked( $booked_data_array, $resource, $day ) {
		if ( ! isset( $booked_data_array['fully_booked_days'][ $day ][ $resource ] ) ) {
			return $booked_data_array;
		}

		$booked_data_array['partially_booked_days'][ $day ][ $resource ] = $booked_data_array['fully_booked_days'][ $day ][ $resource ];

		unset( $booked_data_array['fully_booked_days'][ $day ][ $resource ] );

		if ( empty( $booked_data_array['fully_booked_days'][ $day ] ) ) {
			unset( $booked_data_array['fully_booked_days'][ $day ] );
		}

		return $booked_data_array;
	}

}

new WC_Accommodation_Booking_Date_Picker;
