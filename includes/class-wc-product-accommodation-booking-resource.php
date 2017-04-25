<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Product_Accommodation_Booking_Resource' ) ) :

/**
 * Class that creates our new accommodation booking product resource type
 * Mostly inherited from WC_Product_Booking_Resource (code reuse!) but overrides a few methods
 *
 * @version 1.0.9
 * @since 1.0.9
 */
class WC_Product_Accommodation_Booking_Resource extends WC_Product_Booking_Resource {
	/**
	 * Return the base cost (set at parent level).
	 * @version 1.0.9
	 * @since 1.0.9
	 *
	 * @return float
	 */
	public function get_base_cost() {
		if ( $this->get_parent_id() && ( $parent = wc_get_product( $this->get_parent_id() ) ) && $parent->is_type( 'accommodation-booking' ) ) {
			$costs = $parent->get_resource_base_costs();
			$cost  = isset( $costs[ $this->get_id() ] ) ? $costs[ $this->get_id() ] : '';
		} else {
			$cost   = '';
		}

		return (float) $cost;
	}

	/**
	 * Return the block cost  (set at parent level).
	 * @version 1.0.9
	 * @since 1.0.9
	 *
	 * @return float
	 */
	public function get_block_cost() {
		if ( $this->get_parent_id() && ( $parent = wc_get_product( $this->get_parent_id() ) ) && $parent->is_type( 'accommodation-booking' ) ) {
			$costs  = $parent->get_resource_block_costs();
			$cost   = isset( $costs[ $this->get_id() ] ) ? $costs[ $this->get_id() ] : '';
		} else {
			$cost   = '';
		}
		return (float) $cost;
	}
}

endif;
