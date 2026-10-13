<?php
/**
 * Create a page with Single Product blocks for two rooms and an ordinary product.
 *
 * @package WC_Accommodation_Bookings
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'ACCOM_E2E' ) || ! ACCOM_E2E ) {
	throw new RuntimeException( 'The Single Product block fixture requires an ACCOM_E2E test store.' );
}
require_once dirname( __DIR__, 2 ) . '/integration/fixtures.php';

$ordinary = new WC_Product_Simple();
$ordinary->set_name( 'Block page ordinary product' );
$ordinary->set_regular_price( '10' );
$ordinary->set_status( 'publish' );
$ordinary->save();

// Distinct nightly rates show which room a displayed booking cost belongs to.
$rooms = array();
foreach ( array(
	'first'  => 100,
	'second' => 70,
) as $room => $nightly_rate ) {
	$rooms[ $room ] = accommodation_test_room(
		array(
			'name'       => 'Block page ' . $room . ' room',
			'block_cost' => $nightly_rate,
		)
	)->get_id();
}

// The ordinary product comes first: the rooms after it still have to be found.
$content = '';
foreach ( array( $ordinary->get_id(), $rooms['first'], $rooms['second'] ) as $product_id ) {
	$content .= '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><div class="wp-block-woocommerce-single-product"><!-- wp:post-title {"isLink":false,"__woocommerceNamespace":"woocommerce/product-query/product-title"} /--><!-- wp:woocommerce/add-to-cart-form /--></div><!-- /wp:woocommerce/single-product -->';
}
$page_id = wp_insert_post(
	array(
		'post_title'   => 'Accommodation Single Product blocks',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => $content,
	)
);

echo 'ACCOM_RESULT:' . wp_json_encode(
	array(
		'page'     => $page_id,
		'ordinary' => $ordinary->get_id(),
		'first'    => $rooms['first'],
		'second'   => $rooms['second'],
	)
);
