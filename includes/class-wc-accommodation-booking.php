<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds our new product type to the list of WooCommerce product types.
 *
 * @class   WC_Accommodation_Booking
 * @version 1.3.15
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
		add_filter( 'get_booking_products_args', array( $this, 'add_accommodation_to_booking_products_args' ) );
		add_filter( 'woocommerce_bookings_product_rest_endpoint', array( $this, 'add_accommodation_to_booking_products_args' ) );
		add_filter( 'get_booking_products_args_for_slots_rest_endpoint', array( $this, 'add_accommodation_to_booking_products_args' ) );
		add_filter( 'woocommerce_bookings_product_type_rest_check', array( $this, 'validate_rest_product_type' ), 10, 2 ); 

		add_action( 'woocommerce_new_booking', array( $this, 'update_start_end_time' ) );
		add_filter( 'woocommerce_data_stores', array( $this, 'register_data_stores' ), 10 );
		add_filter( 'woocommerce_bookings_apply_multiple_rules_per_block', array( $this, 'disable_overlapping_rates' ), 10, 2 );
		add_filter( 'woocommerce_bookings_resource_duration_display_string', array( $this, 'filter_resource_duration_display_string' ), 10, 2 );
		add_filter( 'woocommerce_bookings_ics_format_date', array( $this, 'disable_ics_formatting_for_accommodation' ), 10, 4 );
	}

	/**
	 * Keep the calendar (.ics) dates of a booking on the days it really covers.
	 *
	 * Bookings moves the end of every multi-day booking to the following day. That suits an
	 * all-day booking, whose date-only end is exclusive, but not a stay that ends at the
	 * check-out time, so the original timestamp is exported instead.
	 *
	 * @param string          $value     Formatted value.
	 * @param int             $timestamp Timestamp to format.
	 * @param int             $old_ts    Original timestamp.
	 * @param WC_Booking|null $booking   Booking object, or null when Bookings formats a date without one.
	 *
	 * @return string Formatted date for ICS.
	 */
	public function disable_ics_formatting_for_accommodation( $value, $timestamp, $old_ts, $booking ) {
		if ( ! is_a( $booking, 'WC_Booking' ) ) {
			return $value;
		}

		if ( ! $booking->is_all_day() ) {
			return date( 'Ymd\THis', $old_ts ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Bookings timestamps hold site-local time, as in WC_Bookings_ICS_Exporter::format_date().
		}

		// WC_Booking::get_product() returns a plain WC_Product_Booking for every product type.
		$product = wc_get_product( $booking->get_product_id() );

		if ( $product && 'accommodation-booking' === $product->get_type() ) {
			// A date-only end is exclusive: the check-out day itself ends the stay.
			return date( 'Ymd', $old_ts ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- See above.
		}

		// All-day bookings of other products keep the date-only value Bookings wrote.
		return $value;
	}

	/**
	 * Apply only one rate modifier to any given accommodation block.
	 *
	 * Rate rules for accommodation bookings define the absolute accommodation
	 * rate for a block, so it does not make sense to apply multiple concurrent
	 * rates. This hook will cause the rate calculation loop to exit after the
	 * first applicable rate rule modifies the block cost.
	 *
	 * @since 1.1.12
	 *
	 * @param  bool               $enable_overlapping_rates
	 * @param  WC_Product_Booking $product
	 * @return bool
	 */
	public function disable_overlapping_rates( $enable_overlapping_rates, $product ) {
		if ( ! is_a( $product, 'WC_Product_Accommodation_Booking' ) ) {
			return $enable_overlapping_rates;
		}
		return false;
	}

	/**
	 * Register data stores for bookings.
	 *
	 * @param  array  $data_stores
	 * @return array
	 */
	public function register_data_stores( $data_stores = array() ) {
		if ( isset( $data_stores['product-booking'] ) ) {
			$data_stores['product-accommodation-booking'] = $data_stores['product-booking'];
		}
		return $data_stores;
	}

	/**
	 * Hooks into woocommerce_bookings_product_types and adds our new type
	 * @param array $types Array of WooCommerce Bookings Product Types
	 */
	public function add_product_type( $types ) {
		$types[] = 'accommodation-booking';
		return $types;
	}

	/**
	 * Hooks into woocommerce_bookings_product_type_rest_check and verifies that product is a correct bookings type
	 *
	 * @param mixed      $is_product_valid Whether the product is a valid bookings type so far.
	 * @param WC_Product $product          Product.
	 * @return bool
	 */
	public function validate_rest_product_type( $is_product_valid, $product ) {
		return $is_product_valid || 'accommodation-booking' === $product->get_type();
	}

	/**
	 * Shows the check-in time in the start date of an accommodation booking.
	 *
	 * @param mixed $date    Start date with time, as formatted by Bookings or earlier callbacks.
	 * @param mixed $booking Booking (WC_Booking).
	 * @return mixed
	 */
	public function add_checkin_time_to_booking_start_time( $date, $booking ) {
		$product = wc_get_product( $booking->product_id );
		if ( empty( $product ) || 'accommodation-booking' !== $product->get_type() ) {
			return $date;
		}

		$date_format = apply_filters( 'woocommerce_bookings_date_format', wc_date_format() );
		$time_format = apply_filters( 'woocommerce_bookings_time_format', ', ' . wc_time_format() );

		return date_i18n( $date_format, $booking->start ) . date_i18n( $time_format, $booking->start );
	}

	/**
	 * Shows the check-out time in the end date of an accommodation booking.
	 *
	 * @param mixed $date    End date with time, as formatted by Bookings or earlier callbacks.
	 * @param mixed $booking Booking (WC_Booking).
	 * @return mixed
	 */
	public function add_checkout_time_to_booking_end_time( $date, $booking ) {
		$product = wc_get_product( $booking->product_id );
		if ( empty( $product ) || 'accommodation-booking' !== $product->get_type() ) {
			return $date;
		}

		$date_format = apply_filters( 'woocommerce_bookings_date_format', wc_date_format() );
		$time_format = apply_filters( 'woocommerce_bookings_time_format', ', ' . wc_time_format() );

		return date_i18n( $date_format, $booking->end ) . date_i18n( $time_format, $booking->end );
	}

	/**
	 * Adds 'accommodation-booking' to the list of valid product types/terms
	 */
	public function add_accommodation_to_booking_product_terms( $terms ) {
		$terms[] = 'accommodation-booking';
		return $terms;
	}

	/**
	 * Adds 'accommodation-booking' to `get_booking_products` so 'accommodation-booking'
	 * products appear in the dropdown.
	 *
	 * @param array $args Current query args.
	 *
	 * @return array Query args
	 */
	public function add_accommodation_to_booking_products_args( $args ) {
		// If no tax query, return the args as it is.
		if ( empty( $args['tax_query'] ) ) {
			return $args;
		}

		/*
		 * Booking V3 has updated tax query for the product_type taxonomy and it now includes all registered product types.
		 * So we need to check whether the accommodation booking product type is already present before adding it again.
		 */
		$has_accommodation_booking_in_tax_query = array_filter(
			$args['tax_query'],
			function ( $filter ) {
				if ( ! is_array( $filter ) || 'product_type' !== $filter['taxonomy'] ) {
					return false;
				}

				$terms = $filter['terms'] ?? '';

				return is_array( $terms )
					? in_array( 'accommodation-booking', $terms, true )
					: 'accommodation-booking' === $terms;
			}
		);

		// Accommodation booking product type is already there in the tax query, return the args as it is.
		if ( ! empty( $has_accommodation_booking_in_tax_query ) ) {
			return $args;
		}

		foreach ( $args['tax_query'] as $index => $filter ) {
			if ( ! is_array( $filter ) || 'product_type' !== $filter['taxonomy'] ) {
				continue;
			}

			$terms = isset( $args['tax_query'][ $index ]['terms'] ) ? $args['tax_query'][ $index ]['terms'] : array( 'booking' );
			if ( ! is_array( $terms ) ) {
				$terms = array( $terms );
			}
			$terms[] = 'accommodation-booking';

			$args['tax_query'][ $index ]['terms'] = $terms;
		}

		return $args;
	}

	/**
	 * Update start and end time based on check in/out time.
	 *
	 * Since duration of accommodation product is one night, only date is respected
	 * and check-in/out times are not counted.
	 *
	 * @since 1.0.6
	 *
	 * @param int $booking_id Booking ID
	 * @return void
	 */
	public function update_start_end_time( $booking_id ) {
		$product_id = get_post_meta( $booking_id, '_booking_product_id', true );
		$product   = wc_get_product( $product_id );
		if ( ! is_a( $product, 'WC_Product_Accommodation_Booking' ) ) {
			return;
		}

		$check_in  = WC_Product_Accommodation_Booking::get_check_times( 'in', $product_id );
		$check_out = WC_Product_Accommodation_Booking::get_check_times( 'out', $product_id );

		$start = get_post_meta( $booking_id, '_booking_start', true );
		$end   = get_post_meta( $booking_id, '_booking_end', true );

		update_post_meta( $booking_id, '_booking_start', $this->_get_updated_timestamp_time( $start, $check_in ) );
		update_post_meta( $booking_id, '_booking_end', $this->_get_updated_timestamp_time( $end, $check_out ) );
	}

	/**
	 * Updates resource duration display string when duration unit is 'night'.
	 *
	 * @since 1.1.15
	 *
	 * @param string $duration_display Duration to display.
	 * @param object $product          The product we are working with.
	 *
	 * @return string $duration_display Duration to display.
	 */
	public function filter_resource_duration_display_string( $duration_display, $product ) {
		if ( ! is_a( $product, 'WC_Product_Accommodation_Booking' ) || ( 'night' !== $product->get_duration_unit() ) ) {
			return $duration_display;
		}

		return __( 'night', 'woocommerce-accommodation-bookings' );
	}

	/**
	 * Update the time of a given datetime.
	 *
	 * @param string            $datetime Date time without separator (YmdHis)
	 * @param string|false|null $time     Time in `xx:yy` pm/am format, or empty for none.
	 *
	 * @return string Datetime without separator
	 */
	protected function _get_updated_timestamp_time( $datetime, $time ) {
		if ( empty( $time ) ) {
			return $datetime;
		}

		$time  = str_replace( ':', '', $time );
		$time  = str_pad( $time, 6, '0' );

		return substr( $datetime, 0, 8 ) . $time;
	}

}

new WC_Accommodation_Booking;
