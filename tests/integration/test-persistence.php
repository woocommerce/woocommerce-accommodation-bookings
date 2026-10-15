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

	public function test_legacy_per_night_addon_is_charged_once_per_night() {
		$product       = accommodation_test_room();
		$data          = wc_bookings_get_posted_data(
			array(
				'wc_bookings_field_start_date_year'  => (int) gmdate( 'Y' ) + 1,
				'wc_bookings_field_start_date_month' => 3,
				'wc_bookings_field_start_date_day'   => 27,
				'wc_bookings_field_duration'         => 3,
			),
			$product
		);
		$data['_cost'] = WC_Bookings_Cost_Calculation::calculate_booking_cost( $data, $product );
		$this->assertNotWPError( $data['_cost'] );
		$addon = array(
			'name'       => 'Breakfast',
			'value'      => 'Breakfast',
			'price'      => 12,
			'price_type' => 'quantity_based',
		);
		/**
		 * Apply the real add-on callbacks to the legacy per-night fixture.
		 *
		 * @since 1.3.14
		 */
		$addons = apply_filters( 'woocommerce_product_addon_cart_item_data', array( $addon ), array( 'wc_accommodation_booking_block_qty_multiplier' => 1 ), $product->get_id(), array() );
		/**
		 * Build booking cart data through the registered extension callbacks.
		 *
		 * @since 1.3.14
		 */
		$cart_data = apply_filters(
			'woocommerce_add_cart_item_data',
			array(
				'booking' => $data,
				'addons'  => $addons,
			),
			$product->get_id(),
			0,
			1
		);
		$this->assertSame( 336.0, (float) $cart_data['booking']['_cost'] );
		$this->assertSame( 36.0, (float) $cart_data['addons'][0]['price'] );
		/**
		 * Verify that add-ons leave the accommodation price calculation intact.
		 *
		 * @since 1.3.14
		 */
		$this->assertFalse( apply_filters( 'woocommerce_product_addons_adjust_price', true, array( 'data' => $product ) ) );
		list( $order, $booking ) = $this->persist_stay( $product, $cart_data['booking']['_cost'], $data['_start_date'], $data['_end_date'] );
		$this->assertSame( '336.00', wc_get_order( $order->get_id() )->get_total() );
		$this->assertSame( 336.0, (float) ( new WC_Booking( $booking->get_id() ) )->get_cost() );
	}
}
