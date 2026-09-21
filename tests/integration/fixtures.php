<?php
/**
 * Persisted accommodation fixtures shared with local browser setup.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

/**
 * Create a room with deterministic nightly pricing and one available place.
 *
 * @param array $props Product overrides.
 * @return WC_Product_Accommodation_Booking
 */
function accommodation_test_room( $props = array() ) {
	$product = new WC_Product_Accommodation_Booking( 0 );
	$product->set_props(
		array_merge(
			array(
				'name'           => 'Accommodation regression room',
				'status'         => 'publish',
				'virtual'        => true,
				'qty'            => 1,
				'cost'           => 0,
				'block_cost'     => 100,
				'min_duration'   => 1,
				'max_duration'   => 14,
				'min_date_value' => 0,
				'min_date_unit'  => 'day',
				'max_date_value' => 10,
				'max_date_unit'  => 'year',
			),
			$props
		)
	);
	$product->save();
	return new WC_Product_Accommodation_Booking( $product->get_id() );
}
