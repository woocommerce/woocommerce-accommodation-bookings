<?php
/**
 * Add a second room and show both partially booked rooms in Single Product blocks.
 *
 * @package WC_Accommodation_Bookings
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'ACCOM_E2E' ) || ! ACCOM_E2E ) {
	throw new RuntimeException( 'The partial-day fixture requires an ACCOM_E2E test store.' );
}
require_once dirname( __DIR__, 2 ) . '/integration/fixtures.php';

$room  = accommodation_test_room(
	array(
		'name'                  => 'Second accommodation',
		'calendar_display_mode' => 'always_visible',
	)
);
$first = new WC_Booking( (int) $args[1] );
$stay  = new WC_Booking(
	array(
		'product_id' => $room->get_id(),
		'start_date' => $first->get_start(),
		'end_date'   => $first->get_end(),
		'persons'    => 1,
	)
);
$stay->create( 'confirmed' );

$content = '';
foreach ( array( (int) $args[0], $room->get_id() ) as $product_id ) {
	$content .= '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><div class="wp-block-woocommerce-single-product"><!-- wp:post-title {"isLink":false,"__woocommerceNamespace":"woocommerce/product-query/product-title"} /--><!-- wp:woocommerce/add-to-cart-form /--></div><!-- /wp:woocommerce/single-product -->';
}
$page_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Partial day forms',
		'post_content' => $content,
	)
);

echo 'ACCOM_RESULT:' . wp_json_encode(
	array(
		'page'    => $page_id,
		'product' => $room->get_id(),
		'booking' => $stay->get_id(),
	)
);
