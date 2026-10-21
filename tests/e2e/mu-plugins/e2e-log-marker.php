<?php
/**
 * Plugin Name: E2E Log Marker
 * Description: Test-only, installed into mu-plugins for the E2E site. Writes a marker to the PHP error log, so that warnings.spec.js can tell that it reads the log WordPress writes to.
 *
 * @package woocommerce-accommodation-bookings
 */

add_action(
	'init',
	static function () {
		if ( isset( $_GET['e2e_log_marker'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test-only request, nothing is changed.
			error_log( 'e2e-log-marker-' . sanitize_key( wp_unslash( $_GET['e2e_log_marker'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Test-only request, logging is the purpose.
		}
	}
);
