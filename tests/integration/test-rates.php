<?php
/**
 * Rates panel checks against real Bookings pricing.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

/**
 * Each range in Rates shows the nightly cost Bookings charges, and saving the panel keeps it.
 *
 * @version x.x.x
 */
class Accommodation_Rates_Test extends WP_UnitTestCase {
	// phpcs:disable Squiz.Commenting.FunctionComment -- PHPUnit method names describe the contract under test.

	/**
	 * A stored rule shows as its nightly cost, and saving the panel keeps that cost.
	 *
	 * @dataProvider stored_rules
	 */
	public function test_range_shows_and_saves_the_nightly_cost( $modifier, $cost, $shown, $charged, $fallback_cost = null, $block_cost = 100 ) {
		$night   = ( (int) gmdate( 'Y' ) + 1 ) . '-12-01';
		$product = accommodation_test_room(
			array(
				'block_cost' => $block_cost,
				'pricing'    => array(
					array(
						'type'          => 'custom',
						'from'          => $night,
						'to'            => $night,
						'modifier'      => $modifier,
						'cost'          => $cost,
						'base_modifier' => 'plus',
						'base_cost'     => 0,
					),
				),
			)
		);
		if ( null !== $fallback_cost ) {
			$pricing   = $product->get_pricing();
			$pricing[] = array_merge(
				$pricing[0],
				array(
					'modifier' => 'plus',
					'cost'     => $fallback_cost,
				)
			);
			$product->set_pricing( $pricing );
			$product->save();
		}
		$post_id = $product->get_id();
		// The admin form previously saved 100. Bookings CRUD and REST update only the block cost.
		update_post_meta( $post_id, '_wc_booking_base_cost', 100 );

		$this->assertSame( $charged, $this->night_cost( $product, $night ) );

		require_once WC_ABSPATH . 'includes/admin/wc-meta-box-functions.php';
		ob_start();
		include dirname( __DIR__, 2 ) . '/includes/admin/views/html-accommodation-booking-rates.php';
		$html = ob_get_clean();
		$this->assertSame( 1, preg_match( '/id="_wc_accommodation_booking_base_cost" value="([^"]*)"/', $html, $standard_rate ) );
		$row_count = null === $fallback_cost ? 1 : 2;
		$this->assertSame( $row_count, preg_match_all( '/name="wc_accommodation_booking_pricing_block_cost\[\]" value="([^"]*)"/', $html, $shown_costs ) );
		$this->assertSame( $row_count, preg_match_all( '/name="wc_accommodation_booking_pricing_is_active\[\]" value="([01])"/', $html, $active_rows ) );
		$this->assertSame( $shown, $shown_costs[1][0] );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Preserve the test process's request data.
		$previous_post = $_POST;
		$_POST         = array(
			'product-type'                                => 'accommodation-booking',
			'_wc_accommodation_booking_base_cost'         => $standard_rate[1],
			'_wc_accommodation_booking_qty'               => '1',
			'_wc_accommodation_booking_min_duration'      => '1',
			'_wc_accommodation_booking_max_duration'      => '14',
			'_wc_accommodation_booking_min_date'          => '0',
			'_wc_accommodation_booking_min_date_unit'     => 'day',
			'_wc_accommodation_booking_max_date'          => '10',
			'_wc_accommodation_booking_max_date_unit'     => 'year',
			'wc_accommodation_booking_pricing_type'       => array_fill( 0, $row_count, 'custom' ),
			'wc_accommodation_booking_pricing_from_date'  => array_fill( 0, $row_count, $night ),
			'wc_accommodation_booking_pricing_to_date'    => array_fill( 0, $row_count, $night ),
			'wc_accommodation_booking_pricing_block_cost' => $shown_costs[1],
			'wc_accommodation_booking_pricing_is_active'  => $active_rows[1],
		);
		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Exercise the existing WooCommerce save hook.
			do_action( 'woocommerce_process_product_meta', $post_id );
		} finally {
			$_POST = $previous_post;
		}

		// Saving must preserve the quote, including which overlapping rule applies.
		$saved = new WC_Product_Accommodation_Booking( $post_id );
		$this->assertSame( $charged, $this->night_cost( $saved, $night ) );
		$this->assertSame( (float) $block_cost, $saved->get_block_cost( 'edit' ) );
	}

	/** Stored operator and amount, the Rates field value, and the cost of that night before the panel is saved. */
	public function stored_rules() {
		return array(
			'no operator'                => array( '', 20, '120', 120.0 ),
			'plus sign'                  => array( '+', 15, '115', 115.0 ),
			'plus'                       => array( 'plus', '20.5', '120.5', 120.5 ),
			'minus'                      => array( 'minus', 10, '90', 90.0 ),
			'times'                      => array( 'times', 1.125, '112.5', 112.5 ),
			'divide'                     => array( 'divide', 4, '25', 25.0 ),
			'equals'                     => array( 'equals', 80, '80', 80.0 ),
			// Bookings skips a rule that has no amounts.
			'equals zero'                => array( 'equals', 0, '100', 100.0 ),
			'divide by zero'             => array( 'divide', 0, '100', 100.0 ),
			'times nothing'              => array( 'times', '', '100', 100.0 ),
			'minus nothing'              => array( 'minus', '', '100', 100.0 ),
			// Amounts a blank or mistyped field left behind.
			'minus blank'                => array( 'minus', ' ', '100', 100.0 ),
			'minus not a number'         => array( 'minus', 'abc', '100', 100.0 ),
			'minus comma decimal'        => array( 'minus', '10,5', '90', 90.0 ),
			// An active standard-rate rule must still take precedence over a later matching rule.
			'equals standard rate'       => array( 'equals', 100, '100', 100.0, 20 ),
			'times one'                  => array( 'times', 1, '100', 100.0, 20 ),
			'divide by one'              => array( 'divide', 1, '100', 100.0, 20 ),
			'active zero cost preserved' => array( 'minus', '0.00', '100', 100.0, 20 ),
			'inactive rule'              => array( 'minus', 0, '100', 120.0, 20 ),
			'inactive after rate update' => array( 'minus', 0, '150', 170.0, 20, 150 ),
		);
	}

	/** Rates has no one-off base-cost field, so this case checks rendering only. */
	public function test_base_cost_keeps_a_zero_nightly_rule_active_in_the_view() {
		$product = accommodation_test_room(
			array(
				'pricing' => array(
					array(
						'type'          => 'custom',
						'modifier'      => 'times',
						'cost'          => '',
						'base_modifier' => 'plus',
						'base_cost'     => 7,
					),
				),
			)
		);
		$post_id = $product->get_id();
		$rate    = get_post_meta( $post_id, '_wc_booking_pricing', true )[0];

		ob_start();
		include dirname( __DIR__, 2 ) . '/includes/admin/views/html-accommodation-booking-rates-fields.php';
		$this->assertStringContainsString( 'name="wc_accommodation_booking_pricing_block_cost[]" value="0"', ob_get_clean() );
	}

	/** Cost of a one-night stay starting on the given date. */
	private function night_cost( $product, $night ) {
		list( $year, $month, $day ) = explode( '-', $night );
		$cost                       = WC_Bookings_Cost_Calculation::calculate_booking_cost(
			wc_bookings_get_posted_data(
				array(
					'wc_bookings_field_start_date_year'  => $year,
					'wc_bookings_field_start_date_month' => $month,
					'wc_bookings_field_start_date_day'   => $day,
					'wc_bookings_field_duration'         => 1,
				),
				$product
			),
			$product
		);
		$this->assertNotWPError( $cost );
		return (float) $cost;
	}
}
