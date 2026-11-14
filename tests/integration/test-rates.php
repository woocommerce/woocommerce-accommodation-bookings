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
					$this->custom_rate( $night, $modifier, $cost ),
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

		$row_count = null === $fallback_cost ? 1 : 2;
		$form      = $this->rates_form( $product, $night, $row_count );
		$this->assertSame( $shown, $form['wc_accommodation_booking_pricing_block_cost'][0] );
		$saved = $this->save_rates_form( $product, $form );

		// Saving must preserve the quote, including which overlapping rule applies.
		$this->assertSame( $charged, $this->night_cost( $saved, $night ) );
		$this->assertSame( (float) $block_cost, $saved->get_block_cost( 'edit' ) );
	}

	/**
	 * Keep an unchanged rule's effect on the nightly charge for the selected person type.
	 *
	 * @dataProvider person_charge_rules
	 */
	public function test_saving_rates_preserves_rules_with_a_person_charge( $modifier, $cost, $new_standard, $override_block, $charge ) {
		$night = ( (int) gmdate( 'Y' ) + 1 ) . '-12-01';
		$rate  = $this->custom_rate( $night, $modifier, $cost );
		if ( null !== $override_block ) {
			$rate['override_block'] = $override_block;
		}
		$product = accommodation_test_room(
			array(
				'has_persons'                => true,
				'has_person_types'           => true,
				'min_persons'                => 1,
				'max_persons'                => 5,
				'has_person_cost_multiplier' => false,
				'pricing'                    => array( $rate ),
			)
		);
		$person  = new WC_Product_Booking_Person_Type();
		$person->set_parent_id( $product->get_id() );
		$person->set_name( 'Adult' );
		$person->set_block_cost( 20 );
		$person->save();
		$product = new WC_Product_Accommodation_Booking( $product->get_id() );
		$persons = array( 'wc_bookings_field_persons_' . $person->get_id() => 1 );
		$this->assertSame( $charge, $this->night_cost( $product, $night, $persons ) );

		$form                                        = $this->rates_form( $product, $night, 1 );
		$form['_wc_booking_has_persons']             = 'yes';
		$form['_wc_booking_has_person_types']        = 'yes';
		$form['_wc_booking_min_persons_group']       = '1';
		$form['_wc_booking_max_persons_group']       = '5';
		$form['_wc_accommodation_booking_base_cost'] = $new_standard;

		$this->assertSame( $charge, $this->night_cost( $this->save_rates_form( $product, $form ), $night, $persons ) );
	}

	public function person_charge_rules() {
		return array(
			'unchanged multiplier'     => array( 'times', 1.5, '100', null, 180.0 ),
			'empty legacy override'    => array( 'times', 1.5, '100', '', 180.0 ),
			'fixed rate at a new base' => array( 'equals', 80, '110', null, 80.0 ),
		);
	}

	/**
	 * The first matching rate must survive a Bookings REST read and write after an admin save.
	 *
	 * @dataProvider standard_rate_edits
	 */
	public function test_saving_rates_preserves_priority_across_a_rest_round_trip( $modifier, $first_rate, $edit_to_standard ) {
		$night                     = ( (int) gmdate( 'Y' ) + 1 ) . '-12-01';
		$fallback                  = $this->custom_rate( $night, 'plus', 30 );
		$fallback['base_modifier'] = 'plus';
		$product                   = accommodation_test_room(
			array(
				'pricing' => array(
					$this->custom_rate( $night, $modifier, $first_rate ),
					$fallback,
				),
			)
		);
		$form                      = $this->rates_form( $product, $night, 2 );
		if ( $edit_to_standard ) {
			$form['wc_accommodation_booking_pricing_block_cost'][0] = '100';
			$form['wc_accommodation_booking_pricing_block_cost'][1] = '120';
		}
		$saved = $this->save_rates_form( $product, $form );
		$this->assertSame( 100.0, $this->night_cost( $saved, $night ) );

		$previous_user = get_current_user_id();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		try {
			$route = '/wc-bookings/v1/products/' . $saved->get_id();
			$get   = new WP_REST_Request( 'GET', $route );
			$get->set_param( 'context', 'edit' );
			$response = rest_do_request( $get );
			$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

			$put = new WP_REST_Request( 'PUT', $route );
			$put->set_header( 'Content-Type', 'application/json' );
			$put->set_body(
				wp_json_encode(
					array(
						'type'    => 'accommodation-booking',
						'pricing' => $response->get_data()['pricing'],
					)
				)
			);
			$response = rest_do_request( $put );
			$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		} finally {
			wp_set_current_user( $previous_user );
		}

		$this->assertSame( 100.0, $this->night_cost( new WC_Product_Accommodation_Booking( $product->get_id() ), $night ) );
	}

	public function test_saving_rates_preserves_the_rule_through_woocommerce_admin_save() {
		$night   = ( (int) gmdate( 'Y' ) + 1 ) . '-12-01';
		$product = accommodation_test_room(
			array(
				'pricing' => array( $this->custom_rate( $night, 'times', 1.5 ) ),
			)
		);
		$form    = $this->rates_form( $product, $night, 1 );
		$post_id = $product->get_id();
		// This product was updated through Bookings CRUD. The classic Accommodation form uses the block rate.
		update_post_meta( $post_id, '_wc_booking_base_cost', 80 );

		require_once WC_ABSPATH . 'includes/admin/class-wc-admin-meta-boxes.php';
		new WC_Bookings_Admin();
		$previous_user = get_current_user_id();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Preserve the test process's request data.
		$previous_post = $_POST;
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$_POST = $form + array(
			'post_ID'                                => (string) $post_id,
			'woocommerce_meta_nonce'                 => wp_create_nonce( 'woocommerce_save_data' ),
			'_sku'                                   => 'accommodation-rates-admin-save',
			'_virtual'                               => 'yes',
			'_wc_booking_block_cost'                 => '0',
			'_wc_booking_buffer_period'              => '0',
			'_wc_booking_calendar_display_mode'      => '',
			'_wc_booking_cancel_limit_unit'          => '',
			'_wc_booking_cancel_limit'               => '0',
			'_wc_booking_check_availability_against' => '',
			'_wc_booking_cost'                       => '0',
			'_wc_display_cost'                       => '',
			'_wc_booking_duration_type'              => 'customer',
			'_wc_booking_first_block_time'           => '',
			'_wc_booking_max_date_unit'              => 'year',
			'_wc_booking_max_date'                   => '10',
			'_wc_booking_max_duration'               => '14',
			'_wc_booking_max_persons_group'          => '1',
			'_wc_booking_min_date_unit'              => 'day',
			'_wc_booking_min_date'                   => '0',
			'_wc_booking_min_duration'               => '1',
			'_wc_booking_min_persons_group'          => '1',
			'_wc_booking_qty'                        => '1',
			'_wc_booking_resource_label'             => '',
			'_wc_booking_resources_assignment'       => '',
		);
		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Exercise WooCommerce's nonce-protected product save.
			do_action( 'save_post', $post_id, get_post( $post_id ), true );
		} finally {
			$_POST = $previous_post;
			wp_set_current_user( $previous_user );
		}

		$saved = new WC_Product_Accommodation_Booking( $post_id );
		$this->assertSame( 'accommodation-rates-admin-save', $saved->get_sku( 'edit' ) );
		$this->assertSame( 100.0, $saved->get_block_cost( 'edit' ) );
		$this->assertSame( array( 'times' ), array_column( $saved->get_pricing( 'edit' ), 'modifier' ) );
		$this->assertSame( 150.0, $this->night_cost( $saved, $night ) );
	}

	public function standard_rate_edits() {
		return array(
			'unchanged standard rate' => array( 'equals', 100, false ),
			'edited to standard rate' => array( 'equals', 90, true ),
			'active string zero'      => array( 'minus', '0.00', false ),
		);
	}

	public function test_reordering_preserves_the_operator_for_each_row() {
		$night   = ( (int) gmdate( 'Y' ) + 1 ) . '-12-01';
		$product = accommodation_test_room(
			array(
				'pricing' => array(
					$this->custom_rate( $night, 'equals', 100 ),
					$this->custom_rate( $night, 'times', 1 ),
				),
			)
		);
		$form    = $this->rates_form( $product, $night, 2 );
		foreach ( $form as &$field ) {
			if ( is_array( $field ) ) {
				$field = array_reverse( $field );
			}
		}
		unset( $field );

		$saved = $this->save_rates_form( $product, $form );
		$this->assertSame( array( 'times', 'equals' ), array_column( $saved->get_pricing( 'edit' ), 'modifier' ) );
	}

	public function test_editing_the_standard_rate_keeps_the_displayed_range_price() {
		$night                                       = ( (int) gmdate( 'Y' ) + 1 ) . '-12-01';
		$product                                     = accommodation_test_room(
			array(
				'pricing' => array( $this->custom_rate( $night, 'plus', 30 ) ),
			)
		);
		$form                                        = $this->rates_form( $product, $night, 1 );
		$form['_wc_accommodation_booking_base_cost'] = '120';

		$saved = $this->save_rates_form( $product, $form );
		$this->assertSame( 120.0, $saved->get_block_cost( 'edit' ) );
		$this->assertSame( 130.0, $this->night_cost( $saved, $night ) );
	}

	private function custom_rate( $night, $modifier, $cost ) {
		return array(
			'type'          => 'custom',
			'from'          => $night,
			'to'            => $night,
			'modifier'      => $modifier,
			'cost'          => $cost,
			'base_modifier' => '+',
			'base_cost'     => 0,
		);
	}

	/** Build the Rates inputs from the real admin panel. */
	private function rates_form( $product, $night, $row_count ) {
		$post_id = $product->get_id();
		require_once WC_ABSPATH . 'includes/admin/wc-meta-box-functions.php';
		ob_start();
		include dirname( __DIR__, 2 ) . '/includes/admin/views/html-accommodation-booking-rates.php';
		$html = ob_get_clean();
		$this->assertSame( 1, preg_match( '/id="_wc_accommodation_booking_base_cost" value="([^"]*)"/', $html, $standard_rate ) );
		$this->assertSame( $row_count, preg_match_all( '/name="wc_accommodation_booking_pricing_block_cost\[\]" value="([^"]*)"/', $html, $shown_costs ) );
		$this->assertSame( $row_count, preg_match_all( '/name="wc_accommodation_booking_pricing_key\[\]" value="([a-f0-9]{32})"/', $html, $rate_keys ) );

		return array(
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
			'wc_accommodation_booking_pricing_key'        => $rate_keys[1],
		);
	}

	private function save_rates_form( $product, $form ) {
		$post_id = $product->get_id();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Preserve the test process's request data.
		$previous_post = $_POST;
		$_POST         = $form;
		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Exercise the existing WooCommerce save hook.
			do_action( 'woocommerce_process_product_meta', $post_id );
		} finally {
			$_POST = $previous_post;
		}

		return new WC_Product_Accommodation_Booking( $post_id );
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

	public function test_saving_rates_preserves_a_rule_base_charge() {
		$night             = ( (int) gmdate( 'Y' ) + 1 ) . '-12-01';
		$rate              = $this->custom_rate( $night, 'times', '' );
		$rate['base_cost'] = 7;
		$product           = accommodation_test_room(
			array(
				'pricing' => array( $rate ),
			)
		);

		$form = $this->rates_form( $product, $night, 1 );
		$this->assertSame( '0', $form['wc_accommodation_booking_pricing_block_cost'][0] );
		$this->assertSame( 7.0, $this->night_cost( $product, $night ) );
		$this->assertSame( 7.0, $this->night_cost( $this->save_rates_form( $product, $form ), $night ) );
	}

	/** Cost of a one-night stay starting on the given date. */
	private function night_cost( $product, $night, $persons = array() ) {
		list( $year, $month, $day ) = explode( '-', $night );
		$cost                       = WC_Bookings_Cost_Calculation::calculate_booking_cost(
			wc_bookings_get_posted_data(
				$persons + array(
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
