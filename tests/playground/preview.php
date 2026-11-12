<?php
/**
 * Preview helper for WordPress Playground. Not part of the plugin ZIP.
 *
 * The Playground Blueprints copy this file to mu-plugins. Accommodation Bookings does
 * nothing without WooCommerce Bookings, a paid extension that a public Playground link
 * cannot install. This file says so on every admin screen, and creates a sample room
 * once WooCommerce Bookings is active.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether WooCommerce Bookings is active and Accommodation Bookings has loaded on top of it.
 *
 * @return bool
 */
function wc_accommodation_playground_is_ready() {
	return class_exists( 'WC_Product_Accommodation_Booking' );
}

/**
 * Create the sample room once.
 *
 * Runs on admin_init, so the room exists on the first admin screen that loads after
 * WooCommerce Bookings is activated.
 *
 * @return void
 */
function wc_accommodation_playground_create_room() {
	if ( ! wc_accommodation_playground_is_ready() || get_option( 'wc_accommodation_playground_room' ) ) {
		return;
	}

	$room = new WC_Product_Accommodation_Booking( 0 );
	$room->set_props(
		array(
			'name'              => 'Sample room',
			'status'            => 'publish',
			'short_description' => 'A room for two, created for this preview. Rates and availability are made up.',
			'virtual'           => true,
			'qty'               => 2,
			'cost'              => 0,
			'block_cost'        => 100,
			'display_cost'      => 100,
			'min_duration'      => 1,
			'max_duration'      => 14,
			'min_date_value'    => 0,
			'min_date_unit'     => 'day',
			'max_date_value'    => 1,
			'max_date_unit'     => 'year',
		)
	);
	$room->save();

	update_option( 'wc_accommodation_playground_room', $room->get_id() );
}
add_action( 'admin_init', 'wc_accommodation_playground_create_room' );

/**
 * Tell the visitor what the preview needs, or where the sample room is.
 *
 * @return void
 */
function wc_accommodation_playground_notice() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	if ( ! wc_accommodation_playground_is_ready() ) {
		printf(
			'<div class="notice notice-info"><p><strong>%1$s</strong> %2$s</p><p>%3$s</p></div>',
			'Preview of WooCommerce Accommodation Bookings.',
			'This add-on needs WooCommerce Bookings, a paid extension that the preview cannot install for you.',
			sprintf(
				'To test the add-on, <a href="%s">upload your own copy of WooCommerce Bookings</a> and activate it. The preview then creates a sample room.',
				esc_url( admin_url( 'plugin-install.php?tab=upload' ) )
			)
		);
		return;
	}

	$room_id = (int) get_option( 'wc_accommodation_playground_room' );
	if ( ! $room_id || ! get_post( $room_id ) ) {
		return;
	}

	printf(
		'<div class="notice notice-info"><p><strong>%1$s</strong> <a href="%2$s">%3$s</a> | <a href="%4$s">%5$s</a> | <a href="%6$s">%7$s</a></p></div>',
		'Preview of WooCommerce Accommodation Bookings.',
		esc_url( (string) get_edit_post_link( $room_id ) ),
		'Edit the sample room',
		esc_url( (string) get_permalink( $room_id ) ),
		'Book it in the store',
		esc_url( admin_url( 'edit.php?post_type=wc_booking&page=wc_bookings_settings&tab=accommodation' ) ),
		'Check-in and check-out times'
	);
}
add_action( 'admin_notices', 'wc_accommodation_playground_notice' );
