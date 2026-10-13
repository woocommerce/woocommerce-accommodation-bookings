<?php
/**
 * Create a one-room accommodation with a single confirmed two-night stay.
 *
 * The stay leaves a check-out-only day (its first), a fully booked day and a
 * check-in-only day (its last) in the month after next, so the browser has to
 * navigate the calendar to reach them.
 *
 * @package WC_Accommodation_Bookings
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'ACCOM_E2E' ) || ! ACCOM_E2E ) {
	throw new RuntimeException( 'The partial-day fixture requires an ACCOM_E2E test store.' );
}
require_once dirname( __DIR__, 2 ) . '/integration/fixtures.php';

// Remove what an interrupted run left behind: the booking first, because
// Bookings refuses to delete a product that still has one.
foreach ( array_reverse( get_option( 'accommodation_e2e_partial_days', array() ) ) as $stale_id ) {
	wp_delete_post( $stale_id, true );
}

$month   = new DateTimeImmutable( 'first day of +2 months', wp_timezone() );
$product = accommodation_test_room(
	array(
		'name'                  => 'Accommodation partial days',
		'calendar_display_mode' => 'always_visible',
	)
);
update_option( 'accommodation_e2e_partial_days', array( $product->get_id() ) );

// A stay runs from the store's check-in time to its check-out time, which
// Bookings stores as wall-clock time in a UTC timestamp.
$check_in  = WC_Product_Accommodation_Booking::get_check_times( 'in', $product->get_id() );
$check_out = WC_Product_Accommodation_Booking::get_check_times( 'out', $product->get_id() );
$booking   = new WC_Booking(
	array(
		'product_id' => $product->get_id(),
		'start_date' => strtotime( $month->format( 'Y-m-10 ' ) . $check_in . ' UTC' ),
		'end_date'   => strtotime( $month->format( 'Y-m-12 ' ) . $check_out . ' UTC' ),
		'persons'    => 1,
	)
);
$booking->create( 'confirmed' );
update_option( 'accommodation_e2e_partial_days', array( $product->get_id(), $booking->get_id() ) );

WP_CLI::line(
	'ACCOM_RESULT:' . wp_json_encode(
		array(
			'product' => $product->get_id(),
			'booking' => $booking->get_id(),
			'year'    => (int) $month->format( 'Y' ),
			'month'   => (int) $month->format( 'n' ),
		)
	)
);
