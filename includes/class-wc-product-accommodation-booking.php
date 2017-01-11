<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that creates our new accommodation booking product type
 * Mostly inheirted from WC_Product_Booking (code reuse!) but overrides a few methods
 */
class WC_Product_Accommodation_Booking extends WC_Product_Booking {

	/**
	 * Set up our new type and fill out some basic info
	 */
	public function __construct( $product ) {
		$this->product_type = 'accommodation-booking';
		parent::__construct( $product );

		$this->wc_booking_duration_type = 'customer';
		$this->wc_booking_duration_unit = 'night';
		$this->wc_booking_duration = 1;
	}

	/**
	 * Tells Bookings that this product type is a bookings addon.
	 * @return boolean
	 */
	public function is_bookings_addon() {
		return true;
	}

	/**
	 * Human readable version of the addon title
	 * @return string
	 */
	public function bookings_addon_title() {
		return __( 'Accommodation booking', 'woocommerce-accommodation-bookings' );
	}

	/**
	 * We want users to be able to select their range of dates
	 * @return boolean
	 */
	public function is_range_picker_enabled() {
		return true;
	}

	/**
	 * Customers define how many nights they want to stay. There is no concept
	 * of "fixed" durations for accommodations.
	 * @return string
	 */
	public function get_duration_type() {
		return 'customer';
	}

	/**
	 * Our duration is nights instead of days
	 * @return string
	 */
	public function get_duration_unit() {
		return 'night';
	}

	/**
	 * Costs can vary depending on rates (weekend rates, etc)
	 * In the future, addons like cots can also change cost.
	 * @return boolean
	 */
	public function has_additional_costs() {
		return true;
	}

	/**
	 * By default, rooms will be available.
	 * @return boolean
	 */
	public function get_default_availability() {
		return true;
	}

	/**
	 * Hotel rooms are a "virtual" product. No shipping is involved.
	 * @return boolean
	 */
	public function is_virtual() {
		return true;
	}

	/**
	 * Get price HTML
	 * @return string
	 */
	public function get_price_html( $price = '' ) {
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		$display_price    = $tax_display_mode == 'incl' ? $this->get_price_including_tax( 1, $this->get_price() ) : $this->get_price_excluding_tax( 1, $this->get_price() );

		if ( $this->wc_booking_min_duration > 1 ) {
			$display_price = $display_price / $this->wc_booking_min_duration;
		}

		if ( $display_price ) {
			if ( $this->has_additional_costs() ) {
				$price_html = sprintf( __( 'From %s per night', 'woocommerce-accommodation-bookings' ), wc_price( $display_price ) ) . $this->get_price_suffix();
			} else {
				$price_html = wc_price( $display_price ) . $this->get_price_suffix();
			}
		} elseif ( ! $this->has_additional_costs() ) {
			$price_html = __( 'Free', 'woocommerce-accommodation-bookings' );
		} else {
			$price_html = '';
		}
		return apply_filters( 'woocommerce_get_price_html', $price_html, $this );
	}

	/**
	 * Get an array of blocks within in a specified date range
	 *
	 * The WC_Product_Booking class does not account for 'nights' as a valid duration unit so it retrieves every minute of each day as a block,
	 * severly slowing down the load time of the page.
	 *
	 * @param       $start_date
	 * @param       $end_date
	 * @param array $intervals
	 * @param int   $resource_id
	 * @param array $booked
	 *
	 * @return array
	 */
	public function get_blocks_in_range( $start_date, $end_date, $intervals = array(), $resource_id = 0, $booked = array() ) {

		$blocks_in_range = $this->get_blocks_in_range_for_day( $start_date, $end_date, $resource_id, $booked );

		return array_unique( $blocks_in_range );
	}
	
	/**
	 * Check the resources availability against all the blocks.
	 *
	 * Identical to the get_blocks_availability function of the wc_product_booking class, except that the error returned accurately gives
	 * the available quantity of places remaining on the day that triggered the error.
	 *
	 * @param  string $start_date
	 * @param  string $end_date
	 * @param  int    $qty
	 * @param  int    $resource_id
	 * @param  WC_Product_Booking_Resource|null $booking_resource
	 * @return string|WP_Error
	 */
	public function get_blocks_availability( $start_date, $end_date, $qty, $resource_id, $booking_resource ) {
		$blocks   = $this->get_blocks_in_range( $start_date, $end_date, '', $resource_id );
		$interval = 'hour' === $this->get_duration_unit() ? $this->get_duration() * 60 : $this->get_duration();

		if ( ! $blocks ) {
			return false;
		}

		/**
		 * Grab all existing bookings for the date range
		 * @var array
		 */
		$existing_bookings = $this->get_bookings_in_date_range( $start_date, $end_date, $resource_id );
		$available_qtys    = array();

		// Check all blocks availability
		foreach ( $blocks as $block ) {
			$available_qty       = $this->has_resources() && $booking_resource->has_qty() ? $booking_resource->get_qty() : $this->get_qty();
			$qty_booked_in_block = 0;

			foreach ( $existing_bookings as $existing_booking ) {
				$block_end = strtotime( "+{$interval} minutes", $block );
				if ( $existing_booking->is_booked_on_day( $block, $block_end ) ) {
					$qty_to_add = $this->has_person_qty_multiplier() ? max( 1, array_sum( $existing_booking->get_persons() ) ) : 1;

					if ( $this->has_resources() ) {
						if ( $existing_booking->get_resource_id() === absint( $resource_id ) || ( ! $booking_resource->has_qty() && $existing_booking->get_resource() && ! $existing_booking->get_resource()->has_qty() ) ) {
							$qty_booked_in_block += $qty_to_add;
						}
					} else {
						$qty_booked_in_block += $qty_to_add;
					}
				}
			}

			$available_qty = $available_qty - $qty_booked_in_block;

			// Remaining places are less than requested qty, return an error.
			if ( $available_qty < $qty ) {
				if ( in_array( $this->get_duration_unit(), array( 'hour', 'minute' ) ) ) {
					return new WP_Error( 'Error', sprintf(
						_n( 'There is %d place remaining', 'There are %d places remaining', $available_qty , 'woocommerce-bookings' ),
						$available_qty
					) );
				} else {
					return new WP_Error( 'Error', sprintf(
						_n( 'There is %1$d place remaining on %2$s', 'There are %1$d places remaining on %2$s', $available_qty , 'woocommerce-bookings' ),
						$available_qty,
						date_i18n( wc_date_format(), $block )
					) );
				}
			}

			$available_qtys[] = $available_qty;
		}

		return min( $available_qtys );
	}
}
