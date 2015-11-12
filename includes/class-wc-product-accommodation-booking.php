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
	 * Version 1.0.0 does not support persons/person types yet - just booking rooms
	 * @return boolean
	 */
	function has_persons() {
		return false;
	}

	/**
	 * Version 1.0.0 does not support persons/person types yet - just booking rooms
	 * @return boolean
	 */
	public function has_person_types() {
		return false;
	}

	/**
	 * Version 1.0.0 does not support persons/person types yet - just booking rooms
	 * @return boolean
	 */
	public function has_person_qty_multiplier() {
		return false;
	}

	/**
	 * Version 1.0.0 does not extra resources
	 * @return boolean
	 */
	public function has_resources() {
		return false;
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

}
