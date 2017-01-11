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
		add_filter( 'get_booking_products_args', array( $this, 'add_accommodation_to_booking_products_args' ) );
		add_filter( 'woocommerce_booking_is_booked_on_day', array( $this, 'is_booked_on_day_check' ), 10, 4 );

		add_action( 'woocommerce_new_booking', array( $this, 'update_start_end_time' ) );
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

	/**
	 * Adds 'accommodation-booking' to `get_booking_products` so 'accommodation-booking'
	 * products appear in the dropdown.
	 *
	 * @param array $args Current query args
	 *
	 * @return array Query args
	 */
	public function add_accommodation_to_booking_products_args( $args ) {
		foreach ( $args['tax_query'] as $index => $filter ) {
			if ( 'product_type' !== $filter['taxonomy'] ) {
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
	 */
	public function update_start_end_time( $booking_id ) {
		$product_id = get_post_meta( $booking_id, '_booking_product_id', true );
		$product   = wc_get_product( $product_id );
		if ( ! is_a( $product, 'WC_Product_Accommodation_Booking' ) ) {
			return;
		}

		$check_in  = get_option( 'woocommerce_accommodation_bookings_check_in', '' );
		$check_out = get_option( 'woocommerce_accommodation_bookings_check_out', '' );

		$start = get_post_meta( $booking_id, '_booking_start', true );
		$end   = get_post_meta( $booking_id, '_booking_end', true );

		update_post_meta( $booking_id, '_booking_start', $this->_get_updated_timestamp_time( $start, $check_in ) );
		update_post_meta( $booking_id, '_booking_end', $this->_get_updated_timestamp_time( $end, $check_out ) );
	}

	/**
	 * Update the time of a given datetime.
	 *
	 * @param string $datetime Date time without separator (YmdHis)
	 * @param string $time     Time in `xx:yy` pm/am format
	 *
	 * @return Datetime without separtor
	 */
	protected function _get_updated_timestamp_time( $datetime, $time ) {
		if ( empty( $time ) ) {
			return $datetime;
		}

		$time  = str_replace( ':', '', $time );
		$time  = str_pad( $time, 6, '0' );

		return substr( $datetime, 0, 8 ) . $time;
	}
	
	/**
	 * Verify that a booking's date range intersects with the date range of the block we are checking it against.
	 * Designed to hook into the is_booked_on_day method of the wc-booking class, which does not make this check
	 *
	 * @return boolean
	 */	
	public function is_booked_on_day_check( $is_booked, $wc_booking, $block_start, $block_end ) {
		$multiday_booking = date( 'Y-m-d', $wc_booking->start ) < date( 'Y-m-d', $wc_booking->end );
		if ( $multiday_booking ) {
			// Make sure that the booking's end date is not on or before the start date of the block we are checking it against
			//	or that the booking's start date is not on or after the end date of the block we are checking it against
			if ( date( 'YmdHi', $wc_booking->end ) <= date( 'YmdHi', $block_start ) || date( 'YmdHi', $wc_booking->start ) >= date( 'YmdHi', $block_end ) ) {
				$is_booked = false;
			}
		}
		return $is_booked;
	}
}

new WC_Accommodation_Booking;
