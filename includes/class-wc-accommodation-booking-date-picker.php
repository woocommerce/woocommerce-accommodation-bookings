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
		// this array will contain the start and the end of all bookings
		$check_in_out_days     = array(
			'in' => array(),
			'out' => array(),
		);

		if ( 'night' !== $product->get_duration_unit() ) {
			return $booked_data_array;
		}

		$existing_bookings = WC_Bookings_Controller::get_bookings_for_objects( array( $product->get_id() ) );
		$available_quantity = $product->get_available_quantity( null );
		// Use the existing bookings to find days which are partially booked
		foreach ( $existing_bookings as $booking ) {

			$check_date  = $booking->start;
			$resource = $booking->get_resource_id();

			// if we have automatic assignment attempt to automatically assign a resource
			if ( $product->has_resources() && $product->is_resource_assignment_type( 'automatic' ) ) {
				// if the resource is not set, attempt to assign one
				if ( empty( $resource ) ) {
					$resources = $booking->get_product()->get_all_resources_availability( $booking->start, $booking->end, 1 );
					$resources = is_array( $resources ) ? array_keys( $resources ) : array();

					// found an available resource, automatically assign it
					if ( count( $resources ) > 0 ) {
						$resource = $resources[0];
						update_post_meta( $booking->get_id(), '_booking_resource_id', $resource );
					}
				}
			}

			$check_in_out_days['in'][ $resource ][] = date( 'Y-n-j', $check_date );

			// Loop over all booked days in this booking and mark them as fully booked (the last one will be filtered by check out filter)
			while ( $check_date < $booking->end ) {

				$js_date = date( 'Y-n-j', $check_date );

				$check_date = strtotime( '+1 day', $check_date );
				$booked_data_array['fully_booked_days'][ date( 'Y-n-j', $check_date ) ][ $resource ] = true;
			}

			$check_in_out_days['out'][ $resource ][] = date( 'Y-n-j', $check_date );
		}

		// if it has resources, remove resource id 0 from the fully and partially booked list (comes from Bookings itself)
		if ( $product->has_resources() ) {
			foreach ( array( 'partially_booked_days', 'fully_booked_days' ) as $which ) {
				foreach ( $booked_data_array[ $which ] as $day => $resource ) {
					$booked_data_array[ $which ][ $day ] = array_filter(
						$booked_data_array[ $which ][ $day ],
						function( $key ) {
							return 0 !== $key;
						},
						ARRAY_FILTER_USE_KEY
					);
				}
			}
		}

		// get all resources that have a checkin and a checkout date
		$resources = array_intersect( array_keys( $check_in_out_days['in'] ), array_keys( $check_in_out_days['out'] ) );

		foreach ( $resources as $resource ) {
			// mark as fully booked all days that intersect the check in and check out date
			// e.g. booking A starts from X to Y, and booking B starts from Y to Z.
			// X and Z will be partially booked, but Y will be fully booked.
			$fully_booked = array_intersect( $check_in_out_days['in'][ $resource ], $check_in_out_days['out'][ $resource ] );

			if ( ! empty( $fully_booked ) ) {
				foreach ( $fully_booked as $day ) {
					$booked_data_array['fully_booked_days'][ $day ][ $resource ] = true;
				}

				// since we're marking the fully booked checkin days as partially booked, we will exclude the intersection (fully booked ones)
				$check_in_out_days['in'][ $resource ] = array_diff( $check_in_out_days['in'][ $resource ], $fully_booked );

				// since we're marking the checkout days as partially booked, we will exclude the intersection (fully booked ones)
				$check_in_out_days['out'][ $resource ] = array_diff( $check_in_out_days['out'][ $resource ], $fully_booked );
			}
		}

		// go through each checkin and checkout days and mark them as partially booked
		foreach ( array( 'in', 'out' ) as $which ) {
			foreach ( $check_in_out_days[ $which ] as $resource => $days ) {
				$full_days = array();

				// if all resources were used, set the id to 0 (date-picker.js depends on that)
				if ( $product->has_resources() && count( $product->get_resources() ) === count( array_keys( $check_in_out_days[ $which ] ) ) && $product->is_resource_assignment_type( 'automatic' ) ) {
					$full_days = call_user_func_array( 'array_intersect', $check_in_out_days[ $which ] );
				}

				foreach ( $days as $day ) {
					// if the first or the last checkout day for a booking was marked as fully booked, move to partially booked
					if ( ! empty( $booked_data_array['fully_booked_days'][ $day ][ $resource ] ) ) {
						$booked_data_array['partially_booked_days'][ $day ][ in_array( $day, $full_days ) ? 0 : $resource ] = $booked_data_array['fully_booked_days'][ $day ][ $resource ];
						unset( $booked_data_array['fully_booked_days'][ $day ][ $resource ] );
					}
				}
			}
		}

		return $booked_data_array;
	}
}

new WC_Accommodation_Booking_Date_Picker;
