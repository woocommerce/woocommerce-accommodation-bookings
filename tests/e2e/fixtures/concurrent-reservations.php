<?php
/**
 * Coordinate independent native requests against the final room.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

// phpcs:disable WordPress.WP.AlternativeFunctions,WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- CLI-only requests use private synchronization files and native PHP processes.
// phpcs:disable WordPress.DB.SlowDBQuery -- Queries select only this disposable fixture's newly created product bookings.

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'ACCOM_E2E' ) || ! ACCOM_E2E ) {
	throw new RuntimeException( 'Concurrency fixtures require an isolated ACCOM_E2E store.' );
}
require_once dirname( __DIR__, 2 ) . '/integration/fixtures.php';
$release = $args[0] ?? '';
if ( ! in_array( $release, array( 'cancelled', 'expired' ), true ) ) {
	throw new RuntimeException( 'Expected a supported release path.' );
}
$key       = 'accommodation_race_' . wp_generate_uuid4();
$product   = accommodation_test_room();
$users     = array();
$directory = sys_get_temp_dir() . '/' . $key;
mkdir( $directory, 0700 );
$processes = array();
$orders    = array();
try {
	foreach ( array( 1, 2 ) as $number ) {
		$fixture_id = wp_insert_user(
			array(
				'user_login' => $key . '_' . $number,
				'user_email' => $key . '_' . $number . '@example.test',
				'user_pass'  => wp_generate_password(),
				'role'       => 'customer',
			)
		);
		if ( is_wp_error( $fixture_id ) ) {
			throw new RuntimeException( 'Owned customer could not be created.' );
		}
		$users[] = $fixture_id;
	}
	$owned = array(
		'product' => $product->get_id(),
		'users'   => $users,
		'release' => $release,
		'posted'  => array(
			'wc_bookings_field_start_date_year'  => (int) gmdate( 'Y' ) + 1,
			'wc_bookings_field_start_date_month' => 7,
			'wc_bookings_field_start_date_day'   => 1,
			'wc_bookings_field_duration'         => 2,
		),
	);
	add_option( $key, $owned, '', false );
	$start              = static function ( $worker_mode, $index ) use ( $directory, $key ) {
		return proc_open(
			array( PHP_BINARY, dirname( __DIR__ ) . '/workers/reservation.php', untrailingslashit( ABSPATH ), $key, $worker_mode, $directory, (string) $index ),
			array(
				0 => array( 'file', '/dev/null', 'r' ),
				1 => array( 'file', $directory . '/' . $worker_mode . '.out', 'w' ),
				2 => array( 'file', $directory . '/' . $worker_mode . '.err', 'w' ),
			),
			$pipes
		);
	};
	$wait               = static function ( $file ) use ( $directory ) {
		$deadline = microtime( true ) + 10;
		while ( ! is_file( $directory . '/' . $file ) && microtime( true ) < $deadline ) {
			usleep( 10000 );
		}
		if ( ! is_file( $directory . '/' . $file ) ) {
			throw new RuntimeException( 'Native reservation request did not reach its barrier.' );
		}
	};
	$processes['first'] = $start( 'first', 0 );
	$wait( 'first-ready' );
	$processes['second'] = $start( 'second', 1 );
	$wait( 'second-started' );
	file_put_contents( $directory . '/go', 'go' );
	$results = array();
	foreach ( $processes as $worker_mode => $process ) {
		if ( 0 !== proc_close( $process ) ) {
			throw new RuntimeException( 'Native reservation worker failed: ' . file_get_contents( $directory . '/' . $worker_mode . '.err' ) );
		}
		$results[ $worker_mode ] = json_decode( file_get_contents( $directory . '/' . $worker_mode . '.json' ), true );
		if ( $results[ $worker_mode ]['order'] ) {
			$orders[] = $results[ $worker_mode ]['order'];
		}
	}
	$read   = static function () use ( $start, $directory ) {
		$process = $start( 'read', 0 );
		if ( 0 !== proc_close( $process ) ) {
			throw new RuntimeException( 'Fresh reservation reader failed.' );
		}
		return json_decode( file_get_contents( $directory . '/read.json' ), true );
	};
	$before = $read();
	if ( 1 !== count( $before['bookings'] ) || ! $results['first']['accepted'] || $results['second']['accepted'] ) {
		echo 'ACCOM_RACE:' . wp_json_encode(
			array(
				'customers' => $users,
				'first'     => $results['first'],
				'second'    => $results['second'],
				'before'    => $before,
			)
		) . "\n";
		return;
	}
	$first = $before['bookings'][0];
	if ( 'cancelled' === $release ) {
		wc_get_order( $results['first']['order'] )->update_status( 'cancelled' );
	} else {
		wp_cache_delete( 'cron', 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		$hook  = 'wc-booking-remove-inactive-cart';
		$event = wp_get_scheduled_event( $hook, array( $first['id'] ) );
		if ( ! $event ) {
			throw new RuntimeException( 'Native cart expiration was not scheduled.' );
		}
		wp_unschedule_event( $event->timestamp, $hook, $event->args );
		wp_schedule_single_event( time() - 1, $hook, $event->args );
		foreach ( wp_get_ready_cron_jobs() as $timestamp => $hooks ) {
			foreach ( $hooks[ $hook ] ?? array() as $ready ) {
				if ( $ready['args'] === $event->args ) {
					wp_unschedule_event( $timestamp, $hook, $ready['args'] );
					do_action_ref_array( $hook, $ready['args'] );
				}
			}
		}
	}
	$released = $read();
	$process  = $start( 'retry', 1 );
	if ( 0 !== proc_close( $process ) ) {
		throw new RuntimeException( 'Native reservation retry failed.' );
	}
	$retry = json_decode( file_get_contents( $directory . '/retry.json' ), true );
	if ( $retry['order'] ) {
		$orders[] = $retry['order'];
	}
	$after = $read();
	echo 'ACCOM_RACE:' . wp_json_encode(
		array(
			'customers' => $users,
			'first'     => $results['first'],
			'second'    => $results['second'],
			'before'    => $before,
			'released'  => $released,
			'retry'     => $retry,
			'after'     => $after,
		)
	) . "\n";
} finally {
	file_put_contents( $directory . '/go', 'go' );
	foreach ( $processes as $process ) {
		if ( is_resource( $process ) ) {
			proc_close( $process );
		}
	}
	wp_cache_delete( 'cron', 'options' );
	wp_cache_delete( 'alloptions', 'options' );
	foreach ( get_posts(
		array(
			'post_type'   => 'wc_booking',
			'post_status' => array_merge( get_wc_booking_statuses(), array( 'cancelled', 'was-in-cart', 'trash' ) ),
			'numberposts' => -1,
			'meta_key'    => '_booking_product_id',
			'meta_value'  => $product->get_id(),
		)
	) as $booking ) {
		$fixture_booking = new WC_Booking( $booking->ID );
		if ( $fixture_booking->get_order_id() ) {
			$orders[] = $fixture_booking->get_order_id();
		}
		wp_clear_scheduled_hook( 'wc-booking-remove-inactive-cart', array( $booking->ID ) );
		$fixture_booking->delete( true );
	}
	foreach ( array_unique( $orders ) as $fixture_id ) {
		$fixture_order = wc_get_order( $fixture_id );
		if ( $fixture_order ) {
			$fixture_order->delete( true );
		}
	}
	$product->delete( true );
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $users as $fixture_id ) {
		wp_delete_user( $fixture_id );
	}
	delete_option( $key );
	foreach ( glob( $directory . '/*' ) as $file ) {
		unlink( $file );
	}
	rmdir( $directory );
}
