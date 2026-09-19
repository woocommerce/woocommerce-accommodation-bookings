<?php
/**
 * Accommodation prices and lifecycle through real dependency hooks.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

/** Exercise persisted pricing, migrations and order hooks. */
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
		// Retrying a delivery of the real status callback must not duplicate state.
		do_action( 'woocommerce_order_status_' . $transition, $order->get_id() );
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
		$addon     = array(
			'name'       => 'Breakfast',
			'value'      => 'Breakfast',
			'price'      => 12,
			'price_type' => 'quantity_based',
		);
		$addons    = apply_filters( 'woocommerce_product_addon_cart_item_data', array( $addon ), array( 'wc_accommodation_booking_block_qty_multiplier' => 1 ), $product->get_id(), array() );
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
		$this->assertFalse( apply_filters( 'woocommerce_product_addons_adjust_price', true, array( 'data' => $product ) ) );
		list( $order, $booking ) = $this->persist_stay( $product, $cart_data['booking']['_cost'], $data['_start_date'], $data['_end_date'] );
		$this->assertSame( '336.00', wc_get_order( $order->get_id() )->get_total() );
		$this->assertSame( 336.0, (float) ( new WC_Booking( $booking->get_id() ) )->get_cost() );
	}
}
