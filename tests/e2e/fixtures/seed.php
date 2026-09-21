<?php
/**
 * Reset this suite's own records and create real accommodation fixtures.
 *
 * @package WC_Accommodation_Bookings
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'ACCOM_E2E' ) || ! ACCOM_E2E ) {
	throw new RuntimeException( 'The browser seed requires an ACCOM_E2E test store.' );
}
require_once dirname( __DIR__, 2 ) . '/integration/fixtures.php';
// phpcs:disable WordPress.DB.SlowDBQuery -- Only suite-owned records in the disposable test store.
$previous = get_option( 'accommodation_e2e_fixture', array() );
foreach ( $previous['products'] ?? array() as $product_id ) {
	foreach ( get_posts(
		array(
			'post_type'   => 'wc_booking',
			'post_status' => array_merge( get_wc_booking_statuses(), array( 'cancelled', 'was-in-cart', 'trash' ) ),
			'numberposts' => -1,
			'meta_key'    => '_booking_product_id',
			'meta_value'  => $product_id,
		)
	) as $booking ) {
		( new WC_Booking( $booking->ID ) )->delete( true );
	}
}
foreach ( wc_get_orders(
	array(
		'limit'      => -1,
		'status'     => array_merge( array_keys( wc_get_order_statuses() ), array( 'trash', 'wc-checkout-draft' ) ),
		'meta_key'   => '_accommodation_e2e',
		'meta_value' => 'yes',
	)
) as $fixture_order ) {
	$fixture_order->delete( true );
}
foreach ( $previous['posts'] ?? array() as $fixture_id ) {
	wp_delete_post( $fixture_id, true );
}
$journal = array(
	'posts'    => array(),
	'products' => array(),
);
update_option( 'accommodation_e2e_fixture', $journal );
WC_Install::create_pages();
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_currency', 'EUR' );
update_option( 'woocommerce_default_country', 'US:CA' );
update_option( 'timezone_string', 'Europe/Berlin' );
update_option( 'date_format', 'F j, Y' );
update_option( 'time_format', 'g:i a' );
update_option( 'woocommerce_calc_taxes', 'no' );
update_option( 'woocommerce_enable_guest_checkout', 'yes' );
update_option(
	'woocommerce_cod_settings',
	array(
		'enabled'            => 'yes',
		'enable_for_virtual' => 'yes',
	)
);
update_option(
	'woocommerce_accommodation_bookings_times_settings',
	array(
		'check_in'  => '14:00',
		'check_out' => '11:00',
	)
);
$customer    = get_user_by( 'login', 'accommodation_customer' );
$customer_id = $customer ? $customer->ID : wp_create_user( 'accommodation_customer', 'accommodation-test-password', 'accommodation-customer@example.test' );
( new WP_User( $customer_id ) )->set_role( 'customer' );
delete_user_meta( $customer_id, '_woocommerce_persistent_cart_' . get_current_blog_id() );
( new WC_Session_Handler() )->delete_session( $customer_id );
$fixture_year  = (int) gmdate( 'Y' ) + 1;
$dst           = new DateTimeImmutable( 'last Sunday of March ' . $fixture_year );
$stays         = array(
	'year' => array(
		'start'  => "$fixture_year-12-30",
		'end'    => ( $fixture_year + 1 ) . '-01-02',
		'nights' => 3,
		'cost'   => 430,
	),
	'dst'  => array(
		'start'  => $dst->modify( '-1 day' )->format( 'Y-m-d' ),
		'end'    => $dst->modify( '+1 day' )->format( 'Y-m-d' ),
		'nights' => 2,
		'cost'   => 250,
	),
);
$products      = array();
$fixture_posts = array();
foreach ( $stays as $name => &$stay ) {
	$pricing  = 'year' === $name ? array(
		array(
			'from' => "$fixture_year-12-31",
			'to'   => "$fixture_year-12-31",
			'cost' => 50,
		),
		array(
			'from' => ( $fixture_year + 1 ) . '-01-01',
			'to'   => ( $fixture_year + 1 ) . '-01-01',
			'cost' => 80,
		),
	) : array(
		array(
			'from' => $dst->format( 'Y-m-d' ),
			'to'   => $dst->format( 'Y-m-d' ),
			'cost' => 50,
		),
	);
	$pricing  = array_map(
		function ( $rule ) {
			return array_merge(
				array(
					'type'          => 'custom',
					'modifier'      => 'plus',
					'base_modifier' => 'plus',
					'base_cost'     => 0,
				),
				$rule
			);
		},
		$pricing
	);
	$resource = new WC_Product_Booking_Resource();
	$resource->set_name( 'Regression room ' . $name );
	$resource->set_qty( 1 );
	$resource->save();
	$journal['posts'][] = $resource->get_id();
	update_option( 'accommodation_e2e_fixture', $journal );
	$product               = accommodation_test_room(
		array(
			'name'                  => 'Accommodation ' . $name . ' boundary',
			'calendar_display_mode' => '',
			'pricing'               => $pricing,
			'user_can_cancel'       => true,
			'cancel_limit'          => 1,
			'cancel_limit_unit'     => 'day',
			'has_resources'         => true,
			'resources_assignment'  => 'automatic',
			'resource_ids'          => array( $resource->get_id() ),
		)
	);
	$journal['posts'][]    = $product->get_id();
	$journal['products'][] = $product->get_id();
	update_option( 'accommodation_e2e_fixture', $journal );
	$stay['product']  = $product->get_id();
	$stay['resource'] = $resource->get_id();
	$products[]       = $product->get_id();
	$fixture_posts[]  = $product->get_id();
	$fixture_posts[]  = $resource->get_id();
}
unset( $stay );
$fixture_pages = array();
foreach ( array(
	'classic' => '[woocommerce_checkout]',
	'block'   => '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading"><!-- wp:woocommerce/checkout-fields-block --><div class="wp-block-woocommerce-checkout-fields-block"><!-- wp:woocommerce/checkout-contact-information-block /--><!-- wp:woocommerce/checkout-billing-address-block /--><!-- wp:woocommerce/checkout-payment-block /--><!-- wp:woocommerce/checkout-order-note-block --><div class="wp-block-woocommerce-checkout-order-note-block"></div><!-- /wp:woocommerce/checkout-order-note-block --><!-- wp:woocommerce/checkout-actions-block /--></div><!-- /wp:woocommerce/checkout-fields-block --><!-- wp:woocommerce/checkout-totals-block --><div class="wp-block-woocommerce-checkout-totals-block"><!-- wp:woocommerce/checkout-order-summary-block /--></div><!-- /wp:woocommerce/checkout-totals-block --></div><!-- /wp:woocommerce/checkout -->',
) as $fixture_mode => $content ) {
	$fixture_pages[ $fixture_mode ] = wp_insert_post(
		array(
			'post_title'   => 'Accommodation ' . $fixture_mode . ' checkout',
			'post_name'    => 'accommodation-' . $fixture_mode . '-checkout',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => $content,
		)
	);
	$fixture_posts[]                = $fixture_pages[ $fixture_mode ];
	$journal['posts'][]             = $fixture_pages[ $fixture_mode ];
	update_option( 'accommodation_e2e_fixture', $journal );
}
$fixture = array(
	'posts'    => $fixture_posts,
	'products' => $products,
	'customer' => $customer_id,
	'stays'    => $stays,
	'pages'    => $fixture_pages,
);
update_option( 'accommodation_e2e_fixture', $fixture );
update_option( 'accommodation_e2e_mail', array() );
WP_CLI::line( wp_json_encode( $fixture ) );
