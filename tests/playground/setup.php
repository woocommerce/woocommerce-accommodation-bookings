<?php
/**
 * One-off store setup for WordPress Playground previews. Not part of the plugin ZIP.
 *
 * A Blueprint step runs this once, after WordPress has loaded and WooCommerce is active,
 * so the visitor lands on a store that needs no setup by hand.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

defined( 'ABSPATH' ) || exit;

// No setup wizard, no "coming soon" page in front of the store.
delete_transient( '_wc_activation_redirect' );
update_option( 'woocommerce_onboarding_profile', array( 'skipped' => true ) );
update_option( 'woocommerce_coming_soon', 'no' );

// A store that can take an order: pages, a currency, and a payment method that needs no account.
WC_Install::create_pages();
update_option( 'woocommerce_currency', 'USD' );
update_option( 'woocommerce_default_country', 'US:CA' );
update_option( 'woocommerce_enable_guest_checkout', 'yes' );
update_option(
	'woocommerce_cod_settings',
	array(
		'enabled'            => 'yes',
		'enable_for_virtual' => 'yes',
	)
);

// High-Performance Order Storage, the default on new stores. WooCommerce creates the tables.
update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );

// The add-on's own setting: check-in and check-out times.
update_option(
	'woocommerce_accommodation_bookings_times_settings',
	array(
		'check_in'  => '14:00',
		'check_out' => '11:00',
	)
);

// WooCommerce Bookings sends an administrator to its product templates once after it is
// activated. Stay where the preview opened instead.
update_option( 'wc_bookings_show_templates_on_activation', true );

// Product and page links need pretty permalinks.
update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();
