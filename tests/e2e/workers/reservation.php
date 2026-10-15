<?php
/**
 * Independent native cart reservation and fresh database reader.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

// phpcs:disable WordPress.WP.AlternativeFunctions,WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- CLI-only requests use private synchronization files and native PHP processes.
// phpcs:disable WordPress.DB.SlowDBQuery -- Queries select only this disposable fixture's newly created product bookings.

if ( 'cli' !== PHP_SAPI || 6 !== $argc ) {
	exit( 2 );
}
$fixture_site      = $argv[1];
$fixture_key       = $argv[2];
$worker_mode       = $argv[3];
$fixture_directory = $argv[4];
$fixture_index     = (int) $argv[5];
define( 'DISABLE_WP_CRON', true );
require $fixture_site . '/wp-load.php';
if ( ! defined( 'ACCOM_E2E' ) || ! ACCOM_E2E || realpath( ( new ReflectionClass( 'WC_Product_Accommodation_Booking' ) )->getFileName() ) !== realpath( dirname( __DIR__, 3 ) . '/includes/class-wc-product-accommodation-booking.php' ) ) {
	throw new RuntimeException( 'Reservation workers require their own accommodation checkout.' );
}
$owned = get_option( $fixture_key );
if ( 0 !== strpos( $fixture_key, 'accommodation_race_' ) || ! is_array( $owned ) ) {
	throw new RuntimeException( 'Owned reservation fixture is missing.' );
}
add_filter( 'pre_wp_mail', '__return_true', 999 );
global $wpdb;
$result = array(
	'pid'        => getmypid(),
	'connection' => (int) $wpdb->get_var( 'SELECT CONNECTION_ID()' ),
);
if ( 'read' === $worker_mode ) {
	$result['bookings'] = array();
	foreach ( get_posts(
		array(
			'post_type'   => 'wc_booking',
			'post_status' => array_merge( get_wc_booking_statuses(), array( 'cancelled', 'was-in-cart', 'trash' ) ),
			'numberposts' => -1,
			'meta_key'    => '_booking_product_id',
			'meta_value'  => $owned['product'],
		)
	) as $fixture_post ) {
		$booking              = new WC_Booking( $fixture_post->ID );
		$fixture_order        = $booking->get_order_id() ? wc_get_order( $booking->get_order_id() ) : false;
		$result['bookings'][] = array(
			'id'       => $booking->get_id(),
			'status'   => $booking->get_status(),
			'customer' => $booking->get_customer_id(),
			'cost'     => (float) $booking->get_cost(),
			'order'    => $fixture_order ? array(
				'id'       => $fixture_order->get_id(),
				'customer' => $fixture_order->get_customer_id(),
				'total'    => $fixture_order->get_total(),
				'status'   => $fixture_order->get_status(),
			) : null,
		);
	}
} else {
	wp_set_current_user( $owned['users'][ $fixture_index ] );
	WC()->session = new WC_Session_Handler();
	WC()->session->init();
	WC()->customer = new WC_Customer( get_current_user_id(), true );
	WC()->cart     = new WC_Cart();
	$paused        = false;
	add_action(
		'woocommerce_before_booking_object_save',
		static function ( $booking ) use ( $owned, $worker_mode, $fixture_directory, &$paused ) {
			if ( $booking->get_product_id() !== $owned['product'] || $booking->get_id() || $paused ) {
				return;
			}
			$paused = true;
			if ( 'first' === $worker_mode ) {
				file_put_contents( $fixture_directory . '/first-ready', 'ready' );
				$deadline = microtime( true ) + 25;
				while ( ! is_file( $fixture_directory . '/go' ) && microtime( true ) < $deadline ) {
					usleep( 10000 );
				}
				if ( ! is_file( $fixture_directory . '/go' ) ) {
					throw new RuntimeException( 'Reservation barrier timed out.' );
				}
			} elseif ( 'second' === $worker_mode ) {
				// A lock-bypassed request still reaches the barrier; assertions must catch overselling.
				file_put_contents( $fixture_directory . '/second-started', 'started' );
			}
		}
	);
	if ( 'second' === $worker_mode ) {
		add_filter(
			'query',
			static function ( $query ) use ( $fixture_directory ) {
				if ( 0 === strpos( $query, 'INSERT' ) && false !== strpos( $query, 'wc_booking_res_lock_' ) ) {
					file_put_contents( $fixture_directory . '/second-started', 'started' );
				}
				return $query;
			}
		);
	}
	$_POST              = $owned['posted'];
	$cart_key           = WC()->cart->add_to_cart( $owned['product'] );
	$result['accepted'] = (bool) $cart_key;
	$result['quantity'] = WC()->cart->get_cart_contents_count();
	$result['notices']  = wc_get_notices( 'error' );
	$result['order']    = 0;
	if ( $cart_key && 'cancelled' === $owned['release'] ) {
		WC()->cart->calculate_totals();
		$order_id = WC_Checkout::instance()->create_order( array( 'billing_email' => wp_get_current_user()->user_email ) );
		if ( is_wp_error( $order_id ) || ! $order_id ) {
			throw new RuntimeException( 'Native checkout could not create the owned reservation order.' );
		}
		do_action( 'woocommerce_checkout_order_processed', $order_id, array(), wc_get_order( $order_id ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Dispatch the existing native checkout stage after create_order.
		$result['order'] = $order_id;
	}
}
file_put_contents( $fixture_directory . '/' . $worker_mode . '.json', wp_json_encode( $result ) );
