<?php
/**
 * Local browser fixtures; explicitly enabled only in the isolated test store.
 *
 * @package WC_Accommodation_Bookings
 */

if ( ! defined( 'ACCOM_E2E' ) || ! ACCOM_E2E ) {
	return;
}
add_filter(
	'pre_http_request',
	function () {
		return new WP_Error( 'accommodation_e2e', 'External HTTP disabled in local browser tests.' );
	}
);
add_filter( 'woocommerce_defer_transactional_emails', '__return_false' );
add_filter(
	'pre_wp_mail',
	function ( $pre, $mail ) {
		$messages   = get_option( 'accommodation_e2e_mail', array() );
		$messages[] = $mail;
		update_option( 'accommodation_e2e_mail', $messages );
		return true;
	},
	10,
	2
);
// Tag only orders containing this suite's products, including abandoned block drafts.
foreach ( array( 'woocommerce_checkout_order_created', 'woocommerce_store_api_checkout_update_order_meta' ) as $test_order_hook ) {
	add_action(
		$test_order_hook,
		function ( $order ) {
			$fixture = get_option( 'accommodation_e2e_fixture', array() );
			foreach ( $order->get_items() as $item ) {
				if ( $item->get_product_id() && in_array( $item->get_product_id(), $fixture['products'] ?? array(), true ) ) {
					$order->update_meta_data( '_accommodation_e2e', 'yes' );
					$order->save();
					break;
				}
			}
		}
	);
}
