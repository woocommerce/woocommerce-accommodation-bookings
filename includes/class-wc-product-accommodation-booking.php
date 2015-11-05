<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for the accommodation booking product type
 */
class WC_Product_Accommodation_Booking extends WC_Product_Booking {

	/**
	 * Constructor
	 */
	public function __construct( $product ) {
		$this->product_type = 'accommodation-booking';
		parent::__construct( $product );

		$this->wc_booking_duration_type = 'customer';
		$this->wc_booking_duration_unit = 'night';
		$this->wc_booking_duration = 1;
	}


	function has_persons() {
		return false;
	}

	public function has_person_types() {
		return false;
	}

	public function has_person_qty_multiplier() {
		return false;
	}

	public function has_resources() {
		return false;
	}

	public function is_range_picker_enabled() {
		return true;
	}

	public function get_duration_type() {
		return 'customer';
	}

	public function get_duration_unit() {
		return 'night'; // night?
	}

	public function has_additional_costs() {
		return true;
	}

	public function get_default_availability() {
		return true;
	}

}