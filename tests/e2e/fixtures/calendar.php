<?php
/**
 * Disposable calendar integration data for Playwright.
 *
 * @package WooCommerce_Accommodation_Bookings
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'ACCOM_E2E' ) || ! ACCOM_E2E ) {
	throw new RuntimeException( 'The calendar fixture requires an ACCOM_E2E test store and WP-CLI.' );
}

$fixture_key = 'wc_accommodation_e2e_calendar_fixture';
$fixture     = get_option( $fixture_key, array() );
foreach ( array_reverse( $fixture ) as $fixture_id ) {
	wp_delete_post( $fixture_id, true );
}
delete_option( $fixture_key );
$fixture = array();

if ( 'cleanup' === ( $args[0] ?? '' ) ) {
	return;
}

$product = new WC_Product_Accommodation_Booking( 0 );
$product->set_name( 'Accommodation calendar E2E' );
$product->set_status( 'publish' );
$product->set_qty( 1 );
$product->set_cost( 10 );
$product->set_default_date_availability( 'available' );
$product->set_min_date_value( 0 );
$product->set_max_date_value( 12 );
$product->set_max_date_unit( 'month' );
$product->save();
$fixture[] = $product->get_id();
update_option( $fixture_key, $fixture );

// A fixed week next month avoids month-end and midnight boundaries in CI.
$timezone = new DateTimeZone( 'UTC' );
$month    = new DateTimeImmutable( 'first day of next month', $timezone );
$start    = new DateTimeImmutable( $month->format( 'Y-m-10' ) . ' 14:00', $timezone );
$end      = new DateTimeImmutable( $month->format( 'Y-m-17' ) . ' 11:00', $timezone );
$booking  = new WC_Booking();
$booking->set_product_id( $product->get_id() );
$booking->set_start( $start->format( 'U' ) );
$booking->set_end( $end->format( 'U' ) );
$booking->set_status( 'confirmed' );
$booking->save();
$fixture[] = $booking->get_id();
update_option( $fixture_key, $fixture );

$content = '<!-- wp:woocommerce/bookings-availability-calendar-block -->' .
	'<div class="wp-block-woocommerce-bookings-availability-calendar-block wc-bookings-availability-main-calendar-container wc-bookings-availability-main-container" data-product-ids="' . esc_attr( (string) $product->get_id() ) . '" data-show-sold-out="true"></div>' .
	'<!-- /wp:woocommerce/bookings-availability-calendar-block -->';
$page_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Accommodation calendar E2E',
		'post_name'    => 'accommodation-calendar-e2e',
		'post_content' => $content,
	),
	true
);
if ( is_wp_error( $page_id ) ) {
	WP_CLI::error( $page_id->get_error_message() );
}
$fixture[] = $page_id;
update_option( $fixture_key, $fixture );
