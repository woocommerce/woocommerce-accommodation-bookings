<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Product_Accommodation_Booking' ) ) :

/**
 * Class that creates our new accommodation booking product type
 * Mostly inheirted from WC_Product_Booking (code reuse!) but overrides a few methods
 */
class WC_Product_Accommodation_Booking extends WC_Product_Booking {

	/**
	 * Set up our new type and fill out some basic info
	 */
	public function __construct( $product ) {
		$this->product_type = $this->get_type();
		parent::__construct( $product );

		$this->wc_booking_duration_type = 'customer';
		$this->wc_booking_duration_unit = 'night';
		$this->wc_booking_duration = 1;
	}


	/**
	 * Get resource by ID.
	 * Need to override this to return the proper resource class.
	 *
	 * @param  int $id
	 * @return WC_Product_Booking_Resource object
	 */
	public function get_resource( $id ) {
		$resource = parent::get_resource( $id );

		if ( $resource ) {
			$resource = new WC_Product_Accommodation_Booking_Resource( $id, $this->get_id() );
		}

		return $resource;
	}

	/**
	 * Override product type
	 * @return string
	 */
	public function get_type() {
		return 'accommodation-booking';
	}

	/**
	 * Get resources objects.
	 *
	 * @param WC_Product
	 *
	 * @return array(
	 *   type WC_Product_Accommodation_Booking_Resource
	 * )
	 */
	public function get_resources() {
		$product_resources = array();

		foreach ( $this->get_resource_ids() as $resource_id ) {
			$product_resources[] = new WC_Product_Accommodation_Booking_Resource( $resource_id, $this->get_id() );
		}

		return $product_resources;
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
	 * @param  string $context
	 * @return string
	 */
	public function get_duration_type( $context = 'view' ) {
		return 'customer';
	}

	/**
	 * Our duration is nights instead of days
	 * @param  string $context
	 * @return string
	 */
	public function get_duration_unit( $context = 'view' ) {
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
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$display_price = $tax_display_mode == 'incl' ? $this->get_price_including_tax( 1, $this->get_price() ) : $this->get_price_excluding_tax( 1, $this->get_price() );
		} else {
			$display_price = $tax_display_mode == 'incl' ? wc_get_price_including_tax( $this, array( 'qty' => 1, 'price' => $this->get_price() ) ) : wc_get_price_excluding_tax( $this, array( 'qty' => 1, 'price' => $this->get_price() ) );

		}

		$min_duration = absint( get_post_meta( $this->get_id(), '_wc_booking_min_duration', true ) );

		if ( $min_duration > 1 ) {
			$display_price = $display_price / $min_duration;
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
}

endif;
