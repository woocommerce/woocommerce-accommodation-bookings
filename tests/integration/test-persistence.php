<?php
/**
 * Accommodation prices and lifecycle through real dependency hooks.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

/**
 * Exercise persisted pricing, migrations and order hooks.
 *
 * @version x.x.x
 */
class Accommodation_Persistence_Test extends WP_UnitTestCase {
	// phpcs:disable Squiz.Commenting.FunctionComment -- PHPUnit method names describe the contract under test.
	public function set_up() {
		parent::set_up();
		update_option(
			'woocommerce_accommodation_bookings_times_settings',
			array(
				'check_in'  => '14:00',
				'check_out' => '11:00',
			)
		);
	}

	public function test_persisted_mixed_nightly_rates_stop_at_checkout() {
		$year    = (int) gmdate( 'Y' ) + 1;
		$product = accommodation_test_room(
			array(
				'pricing' => array(
					array(
						'type'          => 'custom',
						'from'          => $year . '-12-31',
						'to'            => $year . '-12-31',
						'modifier'      => 'plus',
						'cost'          => 50,
						'base_modifier' => 'plus',
						'base_cost'     => 0,
					),
					array(
						'type'          => 'custom',
						'from'          => $year . '-12-31',
						'to'            => ( $year + 1 ) . '-01-01',
						'modifier'      => 'plus',
						'cost'          => 80,
						'base_modifier' => 'plus',
						'base_cost'     => 0,
					),
				),
			)
		);
		$data    = wc_bookings_get_posted_data(
			array(
				'wc_bookings_field_start_date_year'  => $year,
				'wc_bookings_field_start_date_month' => 12,
				'wc_bookings_field_start_date_day'   => 30,
				'wc_bookings_field_duration'         => 3,
			),
			$product
		);
		$cost    = WC_Bookings_Cost_Calculation::calculate_booking_cost( $data, $product );
		$this->assertNotWPError( $cost );
		// 100 + first matching Dec 31 rate 150 + Jan 1 rate 180. Checkout is excluded.
		$this->assertSame( 430.0, (float) $cost );
		list( $order, $booking ) = $this->persist_stay( $product, $cost, $data['_start_date'], strtotime( ( $year + 1 ) . '-01-02' ) );
		$this->assertSame( '430.00', wc_get_order( $order->get_id() )->get_total() );
		$this->assertSame( 430.0, (float) ( new WC_Booking( $booking->get_id() ) )->get_cost() );
	}

	/** Create a real linked booking and order, then return reloaded objects. */
	private function persist_stay( $product, $cost, $start, $end ) {
		$order = wc_create_order();
		$order->set_currency( 'EUR' );
		$item = $order->add_product(
			$product,
			1,
			array(
				'subtotal' => $cost,
				'total'    => $cost,
			)
		);
		$order->calculate_totals( false );
		$order->save();
		$booking = get_wc_booking(
			array(
				'product_id'    => $product->get_id(),
				'order_id'      => $order->get_id(),
				'order_item_id' => $item,
				'cost'          => $cost,
				'start_date'    => $start,
				'end_date'      => $end,
				'all_day'       => false,
			)
		);
		$booking->create( 'unpaid' );
		return array( wc_get_order( $order->get_id() ), new WC_Booking( $booking->get_id() ) );
	}

	/**
	 * Check paid, cancelled and refunded stays in both order stores.
	 *
	 * @dataProvider order_storage_and_transition
	 */
	public function test_order_transitions_preserve_links_and_release_capacity( $storage, $transition ) {
		update_option( 'woocommerce_custom_orders_table_enabled', $storage );
		$product                 = accommodation_test_room();
		$start                   = strtotime( '+1 month midnight' );
		$end                     = strtotime( '+2 days', $start );
		list( $order, $booking ) = $this->persist_stay( $product, 200, $start, $end );
		$this->assertSame( 'yes' === $storage, Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() );
		$this->assertSame( 'yes' === $storage ? Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::class : WC_Order_Data_Store_CPT::class, $order->get_data_store()->get_current_class_name() );
		$this->assertSame( 0, $this->available_places( $product, $start, $end ) );
		$order->payment_complete( 'local-test-transaction' );
		$order->payment_complete( 'local-test-transaction' );
		$this->assertSame( 'paid', ( new WC_Booking( $booking->get_id() ) )->get_status() );
		$this->assertSame( 'completed', wc_get_order( $order->get_id() )->get_status() );
		$this->assertSame( 0, $this->available_places( $product, $start, $end ) );
		if ( 'refunded' === $transition ) {
			$refund = wc_create_refund(
				array(
					'order_id'       => $order->get_id(),
					'amount'         => 200,
					'reason'         => 'Test refund',
					'refund_payment' => false,
				)
			);
			$this->assertNotWPError( $refund );
		} else {
			$order->update_status( 'cancelled' );
		}
		/**
		 * Retry the real order status event without duplicating booking state.
		 *
		 * @since 1.3.14
		 */
		do_action( 'woocommerce_order_status_' . $transition, $order->get_id() );
		/**
		 * Repeat delivery to verify that the status callback remains idempotent.
		 *
		 * @since 1.3.14
		 */
		do_action( 'woocommerce_order_status_' . $transition, $order->get_id() );
		$saved = new WC_Booking( $booking->get_id() );
		$this->assertSame( 'cancelled', $saved->get_status() );
		$this->assertSame( $order->get_id(), $saved->get_order_id() );
		$this->assertSame( array( $booking->get_id() ), array_map( 'intval', WC_Booking_Data_Store::get_booking_ids_from_order_id( $order->get_id() ) ) );
		$this->assertSame( '200.00', wc_get_order( $order->get_id() )->get_total() );
		$this->assertSame( 'EUR', wc_get_order( $order->get_id() )->get_currency() );
		// The next storefront request rebuilds the availability query cache.
		// Read the persisted capacity input here; browser tests cover that request.
		$this->assertSame( array(), WC_Booking_Data_Store::get_bookings_for_objects_query( array( $product->get_id() ), get_wc_booking_statuses( 'fully_booked' ), $start, $end ) );
		$this->assertCount( 'refunded' === $transition ? 1 : 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	public function order_storage_and_transition() {
		return array( array( 'no', 'cancelled' ), array( 'yes', 'cancelled' ), array( 'no', 'refunded' ), array( 'yes', 'refunded' ) );
	}

	/**
	 * Only eligible virtual accommodation orders auto-complete.
	 *
	 * @dataProvider completion_cases
	 */
	public function test_native_payment_status_eligibility( $storage, $kind, $status, $incoming, $expected ) {
		update_option( 'woocommerce_custom_orders_table_enabled', $storage );
		$product  = accommodation_test_room( array( 'virtual' => 'physical-room' !== $kind ) );
		$order    = wc_create_order();
		$mixed    = 0 === strpos( $kind, 'mixed-' );
		$reversed = false !== strpos( $kind, '-reversed' );
		if ( $mixed ) {
			$simple = new WC_Product_Simple();
			$simple->set_name( 'Unrelated merchandise' );
			$simple->set_regular_price( '25' );
			$simple->set_virtual( false !== strpos( $kind, 'virtual' ) );
			$simple->save();
			if ( $reversed ) {
				$order->add_product( $simple, 1 );
			}
		}
		if ( 'empty' !== $kind ) {
			$order->add_product(
				$product,
				1,
				array(
					'subtotal' => 100,
					'total'    => 100,
				)
			);
		}
		if ( $mixed && ! $reversed ) {
			$order->add_product( $simple, 1 );
		}
		$order->calculate_totals( false );
		$order->set_status( $status );
		$order->save();
		if ( 'deleted' === $kind ) {
			$product->delete( true );
		}
		$saved = wc_get_order( $order->get_id() );
		$items = array_keys( $saved->get_items() );
		$total = 'empty' === $kind ? '0.00' : ( $mixed ? '125.00' : '100.00' );
		$this->assertSame( 'yes' === $storage, Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() );
		$this->assertSame( $expected, apply_filters( 'woocommerce_payment_complete_order_status', $incoming, $order->get_id(), $saved ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Exercise the existing WooCommerce payment hook.
		$this->assertSame( $status, wc_get_order( $order->get_id() )->get_status() );
		$this->assertSame( $total, wc_get_order( $order->get_id() )->get_total() );
		$this->assertSame( $items, array_keys( wc_get_order( $order->get_id() )->get_items() ) );
	}

	public function completion_cases() {
		$cases = array();
		foreach ( array( 'no', 'yes' ) as $storage ) {
			foreach ( array( 'pending', 'on-hold', 'failed' ) as $status ) {
				$cases[ $storage . '-room-' . $status ] = array( $storage, 'room', $status, 'processing', 'completed' );
			}
			foreach ( array( 'physical-room', 'mixed-physical', 'mixed-virtual', 'mixed-physical-reversed', 'mixed-virtual-reversed', 'empty', 'deleted' ) as $kind ) {
				$cases[ $storage . '-' . $kind ] = array( $storage, $kind, 'pending', 'processing', 'processing' );
			}
			foreach ( array( 'processing', 'completed', 'cancelled', 'refunded' ) as $status ) {
				$cases[ $storage . '-existing-' . $status ] = array( $storage, 'room', $status, 'processing', 'processing' );
			}
			$cases[ $storage . '-incoming-completed' ] = array( $storage, 'room', 'pending', 'completed', 'completed' );
		}
		return $cases;
	}

	/**
	 * Rejected customer requests leave the persisted stay and money intact.
	 *
	 * @dataProvider denied_cancellations
	 */
	public function test_native_cancel_denials_preserve_stay_and_order( $storage, $reason ) {
		update_option( 'woocommerce_custom_orders_table_enabled', $storage );
		$product                 = accommodation_test_room(
			array(
				'user_can_cancel'   => 'disabled' !== $reason,
				'cancel_limit'      => 2,
				'cancel_limit_unit' => 'day',
			)
		);
		$start                   = strtotime( 'cutoff' === $reason ? '+1 day midnight' : '+1 month midnight' );
		list( $order, $booking ) = $this->persist_stay( $product, 200, $start, strtotime( '+2 days', $start ) );
		$owner                   = self::factory()->user->create( array( 'role' => 'customer' ) );
		$order->set_customer_id( $owner );
		$order->save();
		$booking->set_customer_id( $owner );
		$booking->save();
		$before   = get_post_meta( $booking->get_id() );
		$previous = get_current_user_id();
		$request  = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restore caller-owned request data; the native handler verifies the test nonce.
		try {
			wp_set_current_user( 'owner' === $reason ? self::factory()->user->create( array( 'role' => 'customer' ) ) : $owner );
			$_GET = array(
				'cancel_booking' => 'true',
				'booking_id'     => $booking->get_id(),
				'redirect'       => '',
				'_wpnonce'       => 'nonce' === $reason ? 'invalid' : wp_create_nonce( 'woocommerce-bookings-cancel_booking_' . $booking->get_id() ),
			);
			wc_clear_notices();
			$this->assertNotFalse( has_action( 'init', array( 'WC_Booking_Form_Handler', 'cancel_booking' ) ) );
			WC_Booking_Form_Handler::cancel_booking();
			$this->assertSame( 'unpaid', ( new WC_Booking( $booking->get_id() ) )->get_status() );
			$this->assertSame( $before, get_post_meta( $booking->get_id() ) );
			$this->assertSame( 'pending', wc_get_order( $order->get_id() )->get_status() );
			$this->assertSame( '200.00', wc_get_order( $order->get_id() )->get_total() );
			$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
			$notice = in_array( $reason, array( 'cutoff', 'disabled' ), true ) ? 'Your booking can no longer be cancelled. Please contact us if you need assistance.' : 'Invalid booking.';
			$this->assertSame(
				array(
					array(
						'notice' => $notice,
						'data'   => array(),
					),
				),
				wc_get_notices( 'error' )
			);
			$this->assertSame( array( $booking->get_id() ), array_map( 'intval', WC_Booking_Data_Store::get_bookings_for_objects_query( array( $product->get_id() ), get_wc_booking_statuses( 'fully_booked' ), $start, strtotime( '+2 days', $start ) ) ) );
		} finally {
			$_GET = $request;
			wp_set_current_user( $previous );
			wc_clear_notices();
		}
	}

	public function denied_cancellations() {
		$cases = array();
		foreach ( array( 'no', 'yes' ) as $storage ) {
			foreach ( array( 'owner', 'nonce', 'cutoff', 'disabled' ) as $reason ) {
				$cases[ $storage . '-' . $reason ] = array( $storage, $reason );
			}
		}
		return $cases;
	}

	/** Read the actual slot API twice to include its cache hit. */
	private function available_places( $product, $start, $end ) {
		$blocks = version_compare( WC_BOOKINGS_VERSION, '3.0', '>=' ) ? array( $start => 0 ) : array( $start );
		$slots  = $product->get_time_slots( $blocks, 0, $start, $end, true );
		$this->assertSame( $slots, $product->get_time_slots( $blocks, 0, $start, $end, true ) );
		return $slots[ $start ]['available'];
	}

	public function test_historical_migrations_preserve_existing_stay_twice() {
		global $wc_accom_plugin;
		$settings                = new WC_Accommodation_Booking_Admin_Product_Settings();
		$product                 = accommodation_test_room();
		$start                   = strtotime( '+2 months midnight' );
		list( $order, $booking ) = $this->persist_stay( $product, 240, $start, strtotime( '+2 days', $start ) );
		// Accommodation <=1.1.2 stored an absolute override_block nightly rate.
		$old = array(
			array(
				'type'           => 'days',
				'from'           => '6',
				'to'             => '7',
				'override_block' => 120,
			),
		);
		update_post_meta( $product->get_id(), '_wc_booking_base_cost', 100 );
		update_post_meta( $product->get_id(), '_wc_booking_pricing', $old );
		update_post_meta( $product->get_id(), 'merchant_metadata', 'keep-product' );
		update_post_meta( $booking->get_id(), 'merchant_metadata', 'keep-booking' );
		update_option( 'wc_accommodation_bookings_version', '1.1.2' );
		// The pre-times-settings format used separate check-in/out options.
		delete_option( 'woocommerce_accommodation_bookings_times_settings' );
		update_option( 'woocommerce_accommodation_bookings_check_in', '15:30' );
		update_option( 'woocommerce_accommodation_bookings_check_out', '10:15' );
		update_option( 'merchant_unrelated_setting', array( 'keep' => true ) );
		$before = array( $booking->get_start(), $booking->get_end(), $booking->get_cost(), $booking->get_order_item_id() );
		for ( $run = 0; $run < 2; $run++ ) {
			$wc_accom_plugin->install();
			$settings->maybe_migrate();
			$pricing = ( new WC_Product_Accommodation_Booking( $product->get_id() ) )->get_pricing();
			$this->assertEquals(
				array(
					array(
						'type'          => 'days',
						'from'          => '6',
						'to'            => '7',
						'base_cost'     => 0,
						'cost'          => 20,
						'base_modifier' => 'plus',
						'modifier'      => 'plus',
					),
				),
				$pricing
			);
			$saved = new WC_Booking( $booking->get_id() );
			$this->assertSame( $before, array( $saved->get_start(), $saved->get_end(), $saved->get_cost(), $saved->get_order_item_id() ) );
			$this->assertSame( $order->get_id(), $saved->get_order_id() );
			$this->assertSame( '240.00', wc_get_order( $order->get_id() )->get_total() );
			$this->assertSame(
				array(
					'check_in'  => '15:30',
					'check_out' => '10:15',
				),
				get_option( 'woocommerce_accommodation_bookings_times_settings' )
			);
			$this->assertSame( array( 'keep' => true ), get_option( 'merchant_unrelated_setting' ) );
			$this->assertSame( 'keep-product', get_post_meta( $product->get_id(), 'merchant_metadata', true ) );
			$this->assertSame( 'keep-booking', get_post_meta( $booking->get_id(), 'merchant_metadata', true ) );
			$this->assertSame( WC_ACCOMMODATION_BOOKINGS_VERSION, get_option( 'wc_accommodation_bookings_version' ) );
		}
	}

	/**
	 * Legacy and modern per-night add-on flags charge once per night through native checkout.
	 *
	 * @dataProvider nightly_addon_cases
	 * @param array $scenario Independent inputs and monetary expectations.
	 */
	public function test_nightly_addon_is_charged_once_per_night_through_checkout( $scenario ) {
		$original = array( WC()->session, WC()->customer, WC()->cart, $_POST, get_current_user_id() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Snapshot caller-owned input in the native CLI fixture.
		try {
			$user = self::factory()->user->create( array( 'role' => 'customer' ) );
			wp_set_current_user( $user );
			WC()->session = new WC_Session_Handler();
			WC()->session->init();
			WC()->customer = new WC_Customer( $user );
			WC()->cart     = new WC_Cart();
			$product       = accommodation_test_room(
				array(
					'block_cost'                 => $scenario['night'],
					'has_persons'                => $scenario['multipliers'],
					'has_person_cost_multiplier' => $scenario['multipliers'],
					'min_persons'                => 1,
					'max_persons'                => 4,
				)
			);
			$resource_id   = 0;
			if ( $scenario['multipliers'] ) {
				$resource = new WC_Product_Booking_Resource();
				$resource->set_name( 'Owned room resource' );
				$resource->set_qty( 4 );
				$resource_id = $resource->save();
				$product->set_has_resources( true );
				$product->set_resources_assignment( 'customer' );
				$product->set_resource_ids( array( $resource_id ) );
				$product->set_resource_base_costs( array( $resource_id => '5.55' ) );
				$product->set_resource_block_costs( array( $resource_id => '2.25' ) );
			}
			$product->update_meta_data(
				'_product_addons',
				array(
					array(
						'name'                             => 'Breakfast',
						'type'                             => 'custom_text',
						'price'                            => $scenario['addon'],
						'price_type'                       => 'quantity_based',
						'adjust_price'                     => 1,
						( $scenario['legacy'] ? 'wc_accommodation_booking_block_qty_multiplier' : 'wc_booking_block_qty_multiplier' ) => 1,
						'wc_booking_person_qty_multiplier' => $scenario['multipliers'] ? 1 : 0,
					),
				)
			);
			$product->save();
			$_POST = array(
				'add-to-cart'                        => $product->get_id(),
				'wc_bookings_field_start_date_year'  => (int) gmdate( 'Y' ) + 1,
				'wc_bookings_field_start_date_month' => 3,
				'wc_bookings_field_start_date_day'   => 27,
				'wc_bookings_field_duration'         => 3,
				'addon-' . $product->get_id() . '-0' => 'Breakfast',
			);
			if ( $scenario['multipliers'] ) {
				$_POST['wc_bookings_field_resource'] = $resource_id;
				$_POST['wc_bookings_field_persons']  = 2;
			}
			$key = WC()->cart->add_to_cart( $product->get_id() );
			$this->assertIsString( $key, wp_json_encode( wc_get_notices() ) );
			$cart = WC()->cart->get_cart_item( $key );
			$this->assertSame( $scenario['total'], round( (float) $cart['booking']['_cost'], 2 ) );
			$this->assertSame( $scenario['addon_total'], round( (float) $cart['addons'][0]['price'], 2 ) );
			/**
			 * The registered callbacks must prevent Product Add-ons charging the add-on twice.
			 *
			 * @since 1.3.14
			 */
			$this->assertFalse( apply_filters( 'woocommerce_product_addons_adjust_price', true, array( 'data' => $product ) ) );
			WC()->cart->calculate_totals();
			$this->assertSame( $scenario['total'], (float) WC()->cart->get_total( 'edit' ) );
			$order_id = WC()->checkout()->create_order(
				array(
					'billing_email'  => 'owned@example.test',
					'payment_method' => 'cod',
				)
			);
			$this->assertNotWPError( $order_id );
			$order = wc_get_order( $order_id );
			$this->assertSame( number_format( $scenario['total'], 2, '.', '' ), $order->get_total() );
			$item    = array_values( $order->get_items() )[0];
			$booking = new WC_Booking( $cart['booking']['_booking_id'] );
			$this->assertSame( $order_id, $booking->get_order_id() );
			$this->assertSame( $item->get_id(), $booking->get_order_item_id() );
		} finally {
			list( WC()->session, WC()->customer, WC()->cart, $_POST, $original_user ) = $original;
			wp_set_current_user( $original_user );
		}
	}

	/**
	 * Three nights with a per-night breakfast add-on.
	 *
	 * @return array Independent expected cart and order totals.
	 */
	public static function nightly_addon_cases() {
		return array(
			// 3 nights * 100 + breakfast 12 * 3 nights.
			'legacy accommodation flag'        => array(
				array(
					'legacy'      => true,
					'multipliers' => false,
					'night'       => 100,
					'addon'       => 12,
					'addon_total' => 36.0,
					'total'       => 336.0,
				),
			),
			// 3 nights * 22.35 + breakfast 2.35 * 3 nights.
			'modern bookings flag'             => array(
				array(
					'legacy'      => false,
					'multipliers' => false,
					'night'       => 22.35,
					'addon'       => 2.35,
					'addon_total' => 7.05,
					'total'       => 74.10,
				),
			),
			// (22.35 * 3 + 5.55 + 2.25 * 3) * 2 persons = 158.70; breakfast 2.35 * 3 nights * 2 persons = 14.10.
			'modern with resource and persons' => array(
				array(
					'legacy'      => false,
					'multipliers' => true,
					'night'       => 22.35,
					'addon'       => 2.35,
					'addon_total' => 14.10,
					'total'       => 172.80,
				),
			),
		);
	}

	/**
	 * The stale-transient tool clears booking slot rows without touching look-alike options.
	 */
	public function test_maintenance_cleanup_preserves_unrelated_options() {
		global $wpdb;
		$tools   = apply_filters( 'woocommerce_debug_tools', array() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Exercise the registered native status tool.
		$product = accommodation_test_room();
		$orphan  = 'book_ts_orphan_' . $product->get_id();
		$timeout = '_transient_timeout_book_ts_timeout_only_' . $product->get_id();
		set_transient( $orphan, array( 'source' => 'orphan' ), DAY_IN_SECONDS );
		add_option( $timeout, time() + DAY_IN_SECONDS, '', false );
		set_transient( 'merchant_book_ts_' . $product->get_id(), array( 'preserve' => 'foreign' ), DAY_IN_SECONDS );
		update_option( 'book_ts_merchant_setting', array( 'preserve' => 'option' ) );
		// "_" is a LIKE wildcard: an unescaped prefix would also match this option.
		update_option( 'xtransient_book_ts_keep', 'preserve-pattern' );
		$previous_user = get_current_user_id();
		try {
			wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
			call_user_func( $tools['clear_stale_booking_transients']['callback'] );
		} finally {
			wp_set_current_user( $previous_user );
		}
		$saved = static function ( $name ) use ( $wpdb ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );
		};
		$this->assertNull( $saved( '_transient_' . $orphan ) );
		$this->assertNull( $saved( '_transient_timeout_' . $orphan ) );
		$this->assertNull( $saved( $timeout ) );
		$this->assertSame( maybe_serialize( array( 'preserve' => 'foreign' ) ), $saved( '_transient_merchant_book_ts_' . $product->get_id() ) );
		$this->assertSame( maybe_serialize( array( 'preserve' => 'option' ) ), $saved( 'book_ts_merchant_setting' ) );
		$this->assertSame( 'preserve-pattern', $saved( 'xtransient_book_ts_keep' ) );
	}

	/**
	 * Persisted adjacent stays produce exact maps for both assignment modes.
	 *
	 * @dataProvider resource_assignment_modes
	 */
	public function test_native_resource_calendar_maps( $assignment ) {
		$year = (int) gmdate( 'Y' ) + 1;
		$a    = new WC_Product_Booking_Resource();
		$a->set_name( 'One-place room' );
		$a->set_qty( 1 );
		$a->save();
		$b = new WC_Product_Booking_Resource();
		$b->set_name( 'Two-place room' );
		$b->set_qty( 2 );
		$b->save();
		$product = accommodation_test_room(
			array(
				'qty'                  => 3,
				'has_resources'        => true,
				'resource_ids'         => array( $a->get_id(), $b->get_id() ),
				'resources_assignment' => $assignment,
			)
		);
		$this->assertTrue( $product->is_resource_assignment_type( $assignment ) );
		foreach ( array( array( $a->get_id(), 1, 3 ), array( $a->get_id(), 3, 5 ), array( $b->get_id(), 2, 4 ), array( $b->get_id(), 3, 6 ) ) as $stay ) {
			$booking = get_wc_booking(
				array(
					'product_id'  => $product->get_id(),
					'resource_id' => $stay[0],
					'start_date'  => strtotime( "$year-01-{$stay[1]} 14:00" ),
					'end_date'    => strtotime( "$year-01-{$stay[2]} 11:00" ),
					'all_day'     => false,
				)
			);
			$booking->create( 'confirmed' );
		}
		$maps = WC_Bookings_Controller::find_booked_day_blocks( $product, strtotime( "$year-01-01" ), strtotime( "$year-01-07" ) );
		// A fills nights 1–4 with adjacent stays; B fills both places only on night 3.
		// Automatic boundaries block only when all three places are occupied.
		$expected = array(
			'fully_booked_days'       => array(
				"$year-1-2" => array( $a->get_id() => 1 ),
				"$year-1-3" => array( $a->get_id() => 1 ),
				"$year-1-4" => array( $a->get_id() => 1 ),
			),
			'fully_booked_start_days' => array(
				"$year-1-1" => array( $a->get_id() => 1 ),
				"$year-1-3" => array( $b->get_id() => 1 ),
			),
			'fully_booked_end_days'   => array(
				"$year-1-5" => array( $a->get_id() => 1 ),
				"$year-1-4" => array( $b->get_id() => 1 ),
			),
			'partially_booked_days'   => array(
				"$year-1-2" => array( $b->get_id() => 1 ),
				"$year-1-5" => array( $b->get_id() => 1 ),
				"$year-1-6" => array( $b->get_id() => 1 ),
			),
		);
		if ( 'automatic' === $assignment ) {
			$expected = array(
				'fully_booked_days'       => array(
					"$year-1-2" => array( $a->get_id() => 1 ),
					"$year-1-4" => array( $a->get_id() => 1 ),
				),
				'fully_booked_start_days' => array(
					"$year-1-3" => array(
						$a->get_id() => 1,
						$b->get_id() => 1,
					),
				),
				'fully_booked_end_days'   => array( "$year-1-4" => array( $b->get_id() => 1 ) ),
				'partially_booked_days'   => array(
					"$year-1-1" => array( $a->get_id() => 1 ),
					"$year-1-2" => array( $b->get_id() => 1 ),
					"$year-1-5" => array(
						$a->get_id() => 1,
						$b->get_id() => 1,
					),
					"$year-1-6" => array( $b->get_id() => 1 ),
				),
			);
		}
		foreach ( $expected as $name => $map ) {
			$actual = $maps[ $name ] ?? array();
			ksort( $map );
			ksort( $actual );
			foreach ( $map as &$resources ) {
				ksort( $resources );
			}
			unset( $resources );
			foreach ( $actual as &$resources ) {
				ksort( $resources );
			}
			unset( $resources );
			$this->assertSame( $map, $actual, $name );
		}
		$this->assertSame( array(), $maps['unavailable_days'] );
		// On the adjacent handoff date only the arriving stay consumes A's one place.
		$handoff = strtotime( "$year-01-03 14:00" );
		$blocks  = version_compare( WC_BOOKINGS_VERSION, '3.0', '>=' ) ? array( $handoff => 0 ) : array( $handoff );
		$this->assertSame(
			array(
				$handoff => array(
					'booked'    => 1,
					'available' => 0,
					'resources' => array( $a->get_id() => 0 ),
				),
			),
			$product->get_time_slots( $blocks, $a->get_id(), $handoff, strtotime( "$year-01-04 11:00" ), true )
		);
		// Checkout at 11:00 permits the next stay from 14:00; a full night cannot overlap.
		foreach ( array( 'allowed', 'overlap' ) as $case ) {
			$day  = 'allowed' === $case ? ( 'customer' === $assignment ? 5 : 4 ) : ( 'customer' === $assignment ? 4 : 3 );
			$data = wc_bookings_get_posted_data(
				array(
					'wc_bookings_field_start_date_year'  => $year,
					'wc_bookings_field_start_date_month' => 1,
					'wc_bookings_field_start_date_day'   => $day,
					'wc_bookings_field_duration'         => 1,
					'wc_bookings_field_resource'         => 'customer' === $assignment ? $a->get_id() : 0,
				),
				$product
			);
			$cost = WC_Bookings_Cost_Calculation::calculate_booking_cost( $data, $product );
			if ( 'allowed' === $case ) {
				$this->assertNotWPError( $cost );
				$this->assertSame( 100.0, (float) $cost );
				$this->assertSame( 'customer' === $assignment ? $a->get_id() : $b->get_id(), $data['_resource_id'] );
				$this->assertSame( strtotime( "$year-01-$day 14:00" ), $data['_start_date'] );
				$this->assertSame( strtotime( "$year-01-" . ( $day + 1 ) . ' 11:00' ), $data['_end_date'] );
			} else {
				$this->assertWPError( $cost );
				if ( 'customer' === $assignment ) {
					// Older Bookings reports the preceding date, but rejects the same occupied night.
					$this->assertStringStartsWith( 'There are a maximum of 0 places remaining on ', $cost->get_error_message() );
				} else {
					$this->assertSame( 'This block cannot be booked.', $cost->get_error_message() );
				}
			}
		}
	}

	public function resource_assignment_modes() {
		return array( array( 'customer' ), array( 'automatic' ) );
	}

	public function test_native_exact_maximum_nights_and_excess() {
		$product = accommodation_test_room( array( 'max_duration' => 5 ) );
		$year    = (int) gmdate( 'Y' ) + 1;
		foreach ( array( 5, 6 ) as $nights ) {
			$data = wc_bookings_get_posted_data(
				array(
					'wc_bookings_field_start_date_year'  => $year,
					'wc_bookings_field_start_date_month' => 2,
					'wc_bookings_field_start_date_day'   => 1,
					'wc_bookings_field_duration'         => $nights,
				),
				$product
			);
			$cost = WC_Bookings_Cost_Calculation::calculate_booking_cost( $data, $product );
			if ( 5 === $nights ) {
				$this->assertNotWPError( $cost );
				$this->assertSame( 500.0, (float) $cost );
			} else {
				$this->assertWPError( $cost );
				$this->assertSame( 'The maximum duration is 5 blocks', $cost->get_error_message() );
			}
		}
	}

	/**
	 * Cache entries cannot reuse another block selection inside the same range.
	 *
	 * @dataProvider block_formats
	 */
	public function test_native_cache_isolates_changed_block_selection( $flat ) {
		$product = accommodation_test_room();
		$year    = (int) gmdate( 'Y' ) + 1;
		$one     = strtotime( "$year-01-01 14:00" );
		$two     = strtotime( "$year-01-02 14:00" );
		$from    = strtotime( "$year-01-01" );
		$to      = strtotime( "$year-01-03" );
		$booking = get_wc_booking(
			array(
				'product_id' => $product->get_id(),
				'start_date' => $two,
				'end_date'   => strtotime( "$year-01-03 11:00" ),
				'all_day'    => false,
			)
		);
		$booking->create( 'confirmed' );
		$available = array(
			$one => array(
				'booked'    => 0,
				'available' => 1,
				'resources' => array( 0 => 1 ),
			),
		);
		$occupied  = array(
			$two => array(
				'booked'    => 1,
				'available' => 0,
				'resources' => array( 0 => 0 ),
			),
		);
		$this->assertSame( $available, $product->get_time_slots( $flat ? array( $one ) : array( $one => 0 ), 0, $from, $to, true ) );
		$this->assertSame( $occupied, $product->get_time_slots( $flat ? array( $two ) : array( $two => 0 ), 0, $from, $to, true ) );
		$this->assertSame( array(), $product->get_time_slots( array(), 0, $from, $to, true ) );
		$this->assertSame( $available, $product->get_time_slots( $flat ? array( $one ) : array( $one => 0 ), 0, $from, $to, true ) );
	}

	public function block_formats() {
		return array( array( false ), array( true ) );
	}

	/**
	 * The Store API availability endpoint passes a resource id along for a room without resources
	 * (and, before Bookings 3.10.0, for any room); one the room does not use yields the same slots
	 * as no resource selection, as it does in Bookings itself.
	 *
	 * @dataProvider resource_setups
	 */
	public function test_time_slots_ignore_a_resource_that_is_not_assigned_to_the_room( $has_resources ) {
		$other = new WC_Product_Booking_Resource();
		$other->set_name( 'Room of another product' );
		$other->set_qty( 1 );
		$other->save();
		$props     = array();
		$resources = array( 0 => 1 );
		if ( $has_resources ) {
			$assigned = new WC_Product_Booking_Resource();
			$assigned->set_name( 'Assigned room' );
			$assigned->set_qty( 2 );
			$assigned->save();
			$props     = array(
				'has_resources' => true,
				'resource_ids'  => array( $assigned->get_id() ),
			);
			$resources = array( $assigned->get_id() => 2 );
		}
		$room  = accommodation_test_room( $props );
		$year  = (int) gmdate( 'Y' ) + 1;
		$night = strtotime( "$year-01-01 14:00" );
		$from  = strtotime( "$year-01-01" );
		$to    = strtotime( "$year-01-02" );

		$this->assertFalse( $room->get_resource( $other->get_id() ) );
		$expected = array(
			$night => array(
				'booked'    => 0,
				'available' => array_sum( $resources ),
				'resources' => $resources,
			),
		);
		$this->assertSame( $expected, $room->get_time_slots( array( $night => 0 ), $other->get_id(), $from, $to, true ) );
		$this->assertSame( $expected, $room->get_time_slots( array( $night => 0 ), 0, $from, $to, true ) );
	}

	public function resource_setups() {
		return array(
			'room without resources'         => array( false ),
			'room with an assigned resource' => array( true ),
		);
	}
}
