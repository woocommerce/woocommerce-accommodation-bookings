<?php
/**
 * Check-in and check-out times in the cart, on saved bookings and in calendar files.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

/**
 * Follow a stay's times through a real site time zone, date formats and the calendar export.
 *
 * @version x.x.x
 */
class Accommodation_Times_Test extends WP_UnitTestCase {
	// phpcs:disable Squiz.Commenting.FunctionComment -- PHPUnit method names describe the contract under test.
	public function set_up() {
		parent::set_up();
		update_option( 'timezone_string', 'Europe/Berlin' );
		update_option( 'date_format', 'd.m.Y' );
		update_option( 'time_format', 'H:i' );
		update_option(
			'woocommerce_accommodation_bookings_times_settings',
			array(
				'check_in'  => '15:30',
				'check_out' => '10:15',
			)
		);
	}

	public function tear_down() {
		// The cart and its notices live in the session, which the database rollback does not reset.
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}
		wc_clear_notices();
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Berlin clocks go back on the second night, and one product has its own filtered times.
	 * Unusable filtered times fall back to the saved ones; empty values mean no time, as before.
	 *
	 * @dataProvider check_times
	 */
	public function test_stay_over_the_autumn_clock_change_keeps_its_times( $filtered, $check_in_time, $check_out_time ) {
		// Saturday to Monday around the last Sunday of October, when Berlin leaves summer time.
		$sunday    = new DateTimeImmutable( 'last sunday of october ' . ( (int) gmdate( 'Y' ) + 1 ), new DateTimeZone( 'UTC' ) );
		$check_in  = $sunday->modify( '-1 day' );
		$check_out = $sunday->modify( '+1 day' );
		$room      = accommodation_test_room();
		$suite     = accommodation_test_room( array( 'name' => 'Suite with its own times' ) );
		$suite_id  = $suite->get_id();
		add_filter(
			'woocommerce_accommodation_booking_get_check_times',
			function ( $time, $type, $product_id ) use ( $suite_id, $filtered ) {
				if ( $suite_id !== $product_id ) {
					return $time;
				}
				return $filtered[ $type ];
			},
			10,
			3
		);

		$item = $this->add_stay_to_cart( null === $filtered ? $room : $suite, $check_in, 2 );
		// Two nights at 100, whatever the clocks do.
		$this->assertSame( 200.0, (float) $item['booking']['_cost'] );

		$shown = wp_list_pluck( apply_filters( 'woocommerce_get_item_data', array(), $item ), 'value', 'name' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WooCommerce core hook.
		$this->assertSame( '2 nights', $shown['Duration'] );
		if ( '' === $check_in_time ) {
			// No time: the cart leaves the lines out, and the stay runs from midnight to midnight.
			$this->assertArrayNotHasKey( 'Check-in', $shown );
			$this->assertArrayNotHasKey( 'Check-out', $shown );
			$check_in_time  = '00:00';
			$check_out_time = '00:00';
		} else {
			$this->assertSame( $check_in->format( 'd.m.Y' ) . ' at ' . $check_in_time, $shown['Check-in'] );
			$this->assertSame( $check_out->format( 'd.m.Y' ) . ' at ' . $check_out_time, $shown['Check-out'] );
		}

		$booking_id = $item['booking']['_booking_id'];
		$clock_in   = str_replace( ':', '', $check_in_time ) . '00';
		$clock_out  = str_replace( ':', '', $check_out_time ) . '00';
		$this->assertSame( $check_in->format( 'Ymd' ) . $clock_in, get_post_meta( $booking_id, '_booking_start', true ) );
		$this->assertSame( $check_out->format( 'Ymd' ) . $clock_out, get_post_meta( $booking_id, '_booking_end', true ) );

		$event = $this->calendar_event( new WC_Booking( $booking_id ) );
		$this->assertSame( 'TZID=Europe/Berlin:' . $check_in->format( 'Ymd' ) . 'T' . $clock_in, $event['DTSTART'] );
		$this->assertSame( 'TZID=Europe/Berlin:' . $check_out->format( 'Ymd' ) . 'T' . $clock_out, $event['DTEND'] );
	}

	public function check_times() {
		return array(
			'site settings'           => array( null, '15:30', '10:15' ),
			'times filtered per room' => array(
				array(
					'in'  => '16:45',
					'out' => '09:20',
				),
				'16:45',
				'09:20',
			),
			'unpadded hour'           => array(
				array(
					'in'  => '9:00',
					'out' => '9:00',
				),
				'15:30',
				'10:15',
			),
			'12-hour time'            => array(
				array(
					'in'  => '2pm',
					'out' => '2pm',
				),
				'15:30',
				'10:15',
			),
			'array'                   => array(
				array(
					'in'  => array(),
					'out' => array(),
				),
				'15:30',
				'10:15',
			),
			'empty string'            => array(
				array(
					'in'  => '',
					'out' => '',
				),
				'',
				'',
			),
			'false'                   => array(
				array(
					'in'  => false,
					'out' => false,
				),
				'',
				'',
			),
			'null'                    => array(
				array(
					'in'  => null,
					'out' => null,
				),
				'',
				'',
			),
		);
	}

	/**
	 * This plugin filters every calendar date Bookings exports, also those of other products.
	 */
	public function test_all_day_booking_of_another_product_keeps_the_bookings_calendar_dates() {
		$product = new WC_Product_Booking( 0 );
		$product->set_props(
			array(
				'name'          => 'Bike rental by the day',
				'status'        => 'publish',
				'duration_type' => 'customer',
				'duration_unit' => 'day',
				'qty'           => 1,
			)
		);
		$product->save();
		$first_day = new DateTimeImmutable( 'first day of january ' . ( (int) gmdate( 'Y' ) + 1 ), new DateTimeZone( 'UTC' ) );
		$booking   = $this->create_booking( $product, $first_day->getTimestamp(), $first_day->modify( '+3 days -1 second' )->getTimestamp(), true );

		$event = $this->calendar_event( $booking );
		// Export again without this plugin's callback; WP_UnitTestCase restores it after the test.
		$removed = 0;
		foreach ( $GLOBALS['wp_filter']['woocommerce_bookings_ics_format_date']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof WC_Accommodation_Booking ) {
					$removed += (int) remove_filter( 'woocommerce_bookings_ics_format_date', $callback['function'], $priority );
				}
			}
		}
		$this->assertSame( 1, $removed );
		$this->assertSame( $this->calendar_event( $booking ), $event );
		// Three whole days: date-only values, and an end date that is itself excluded (RFC 5545).
		$this->assertSame( 'VALUE=DATE:' . $first_day->format( 'Ymd' ), $event['DTSTART'] );
		$this->assertSame( 'VALUE=DATE:' . $first_day->modify( '+3 days' )->format( 'Ymd' ), $event['DTEND'] );
	}

	/**
	 * Bookings pushes the end of a multi-day booking to the next day; a stay ends on check-out day.
	 */
	public function test_all_day_stay_ends_on_the_check_out_day_in_the_calendar() {
		$check_in = new DateTimeImmutable( 'first day of january ' . ( (int) gmdate( 'Y' ) + 1 ), new DateTimeZone( 'UTC' ) );
		$booking  = $this->create_booking( accommodation_test_room(), $check_in->getTimestamp(), $check_in->modify( '+3 days' )->getTimestamp(), true );
		$this->assertTrue( $booking->is_all_day() );

		$event = $this->calendar_event( $booking );
		// Nights of the 1st, 2nd and 3rd: the excluded end date is the check-out day itself.
		$this->assertSame( 'VALUE=DATE:' . $check_in->format( 'Ymd' ), $event['DTSTART'] );
		$this->assertSame( 'VALUE=DATE:' . $check_in->modify( '+3 days' )->format( 'Ymd' ), $event['DTEND'] );
	}

	/** Submit the booking form the way the product page does and return the cart item. */
	private function add_stay_to_cart( $product, DateTimeImmutable $check_in, $nights ) {
		wc_load_cart();
		$_POST = array(
			'wc_bookings_field_start_date_year'  => $check_in->format( 'Y' ),
			'wc_bookings_field_start_date_month' => $check_in->format( 'n' ),
			'wc_bookings_field_start_date_day'   => $check_in->format( 'j' ),
			'wc_bookings_field_duration'         => $nights,
		);
		$key   = WC()->cart->add_to_cart( $product->get_id() );
		$this->assertNotFalse( $key, wp_json_encode( wc_get_notices() ) );
		return WC()->cart->get_cart_item( $key );
	}

	/** Save a booking through the Bookings API and return it reloaded. */
	private function create_booking( $product, $start, $end, $all_day ) {
		$booking = get_wc_booking(
			array(
				'product_id' => $product->get_id(),
				'start_date' => $start,
				'end_date'   => $end,
				'all_day'    => $all_day,
			)
		);
		$booking->create( 'unpaid' );
		return new WC_Booking( $booking->get_id() );
	}

	/** Export the booking with the real exporter and return its DTSTART and DTEND parameters and values. */
	private function calendar_event( $booking ) {
		$path  = ( new WC_Bookings_ICS_Exporter() )->get_booking_ics( $booking );
		$lines = file( $path, FILE_IGNORE_NEW_LINES );
		wp_delete_file( $path );
		$event = array();
		foreach ( $lines as $line ) {
			if ( preg_match( '/^(DTSTART|DTEND);(.+)$/', $line, $matches ) ) {
				$event[ $matches[1] ] = $matches[2];
			}
		}
		ksort( $event );
		$this->assertSame( array( 'DTEND', 'DTSTART' ), array_keys( $event ), implode( "\n", $lines ) );
		return $event;
	}
}
