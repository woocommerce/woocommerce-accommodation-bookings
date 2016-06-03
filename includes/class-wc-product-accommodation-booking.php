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

}
