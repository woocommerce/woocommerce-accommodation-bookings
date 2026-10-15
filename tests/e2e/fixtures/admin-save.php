<?php
/**
 * Owned records for real WordPress product-save requests.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'ACCOM_E2E' ) || ! ACCOM_E2E ) {
	throw new RuntimeException( 'Admin-save fixtures require the isolated ACCOM_E2E store.' );
}
require_once dirname( __DIR__, 2 ) . '/integration/fixtures.php';
$fixture_action = $args[0] ?? '';
$fixture_key    = $args[1] ?? '';
$cleanup        = static function ( $journal, $fixture_key ) {
	global $wpdb;
	foreach ( $journal['products'] as $fixture_id ) {
		$wpdb->delete( $wpdb->prefix . 'wc_booking_relationships', array( 'product_id' => $fixture_id ), array( '%d' ) );
	}
	foreach ( array_reverse( $journal['posts'] ) as $fixture_id ) {
		wp_delete_post( $fixture_id, true );
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $journal['users'] as $fixture_id ) {
		wp_delete_user( $fixture_id );
	}
	delete_option( $fixture_key );
};
if ( 'create' === $fixture_action ) {
	$fixture_key = 'accommodation_admin_e2e_' . wp_generate_uuid4();
	$journal     = array(
		'posts'     => array(),
		'users'     => array(),
		'roles'     => array(),
		'products'  => array(),
		'resources' => array(),
		'persons'   => array(),
	);
	add_option( $fixture_key, $journal, '', false );
	try {
		foreach ( array( 'shop_manager' ) as $fixture_role ) {
			$login      = 'accom-admin-' . wp_generate_uuid4();
			$fixture_id = wp_insert_user(
				array(
					'user_login' => $login,
					'user_pass'  => 'owned-admin-test-password',
					'user_email' => $login . '@example.test',
					'role'       => $fixture_role,
				)
			);
			if ( is_wp_error( $fixture_id ) ) {
				throw new RuntimeException( 'Owned admin-save user could not be created.' );
			}
			$journal['users'][]                = $fixture_id;
			$journal['roles'][ $fixture_role ] = array(
				'id'       => $fixture_id,
				'login'    => $login,
				'password' => 'owned-admin-test-password',
			);
			update_option( $fixture_key, $journal );
		}
		foreach ( array( 'target', 'control' ) as $fixture_label ) {
			$product                               = accommodation_test_room(
				array(
					'name'             => 'Owned admin ' . $fixture_label,
					'has_persons'      => true,
					'has_person_types' => true,
					'has_resources'    => true,
				)
			);
			$journal['posts'][]                    = $product->get_id();
			$journal['products'][ $fixture_label ] = $product->get_id();
			update_option( $fixture_key, $journal );
			$resource = new WC_Product_Booking_Resource();
			$resource->set_name( 'Owned resource ' . $fixture_label );
			$resource->set_qty( 4 );
			$resource_id        = $resource->save();
			$journal['posts'][] = $resource_id;
			update_option( $fixture_key, $journal );
			$product->set_resources_assignment( 'customer' );
			$product->set_resource_ids( array( $resource_id ) );
			$product->set_resource_base_costs( array( $resource_id => '3.25' ) );
			$product->set_resource_block_costs( array( $resource_id => '4.50' ) );
			$product->save();
			$person = wp_insert_post(
				array(
					'post_type'    => 'bookable_person',
					'post_status'  => 'publish',
					'post_parent'  => $product->get_id(),
					'post_title'   => 'Original person ' . $fixture_label,
					'post_excerpt' => 'Original description',
					'menu_order'   => 1,
				)
			);
			if ( ! $person ) {
				throw new RuntimeException( 'Owned admin-save person could not be created.' );
			}
			$journal['posts'][] = $person;
			update_option( $fixture_key, $journal );
			foreach ( array(
				'cost'            => '2.5',
				'block_cost'      => '1.25',
				'min'             => '1',
				'max'             => '4',
				'merchant_person' => 'preserve',
			) as $key => $value ) {
				update_post_meta( $person, $key, $value );
			}
			$journal['products'][ $fixture_label ]  = $product->get_id();
			$journal['resources'][ $fixture_label ] = $resource_id;
			$journal['persons'][ $fixture_label ]   = $person;
			update_post_meta( $product->get_id(), 'merchant_product', 'preserve' );
			update_post_meta( $resource_id, 'merchant_resource', 'preserve' );
			update_option( $fixture_key, $journal );
		}
	} catch ( Throwable $error ) {
		$cleanup( $journal, $fixture_key );
		throw $error;
	}
	$journal['key'] = $fixture_key;
	echo 'ACCOM_ADMIN:' . wp_json_encode( $journal ) . "\n";
	return;
}
if ( 0 !== strpos( $fixture_key, 'accommodation_admin_e2e_' ) ) {
	throw new RuntimeException( 'Expected an owned admin-save fixture key.' );
}
$journal = get_option( $fixture_key );
if ( ! is_array( $journal ) ) {
	throw new RuntimeException( 'Owned admin-save journal is missing.' );
}
if ( 'deny-person' === $fixture_action ) {
	$fixture_user = new WP_User( $journal['roles']['shop_manager']['id'] );
	foreach ( array( 'edit_bookable_person', 'edit_bookable_persons', 'edit_others_bookable_persons', 'edit_published_bookable_persons', 'edit_private_bookable_persons' ) as $capability ) {
		$fixture_user->add_cap( $capability, false );
	}
	echo "ACCOM_ADMIN:true\n";
} elseif ( 'snapshot' === $fixture_action ) {
	$records = array();
	foreach ( $journal['posts'] as $fixture_id ) {
		$fixture_post = get_post( $fixture_id );
		$fixture_meta = get_post_meta( $fixture_id );
		// WordPress edit locks are request bookkeeping, outside the saved product/person contract.
		unset( $fixture_meta['_edit_lock'], $fixture_meta['_edit_last'] );
		foreach ( array_keys( $fixture_meta ) as $fixture_meta_key ) {
			$fixture_meta[ $fixture_meta_key ] = get_post_meta( $fixture_id, $fixture_meta_key, false );
		}
		ksort( $fixture_meta );
		$records[ $fixture_id ] = array(
			'title'     => $fixture_post->post_title,
			'excerpt'   => $fixture_post->post_excerpt,
			'parent'    => (int) $fixture_post->post_parent,
			'menuOrder' => (int) $fixture_post->menu_order,
			'meta'      => $fixture_meta,
		);
		if ( in_array( $fixture_id, $journal['products'], true ) ) {
			global $wpdb;
			$rows                                    = $wpdb->get_results( $wpdb->prepare( "SELECT resource_id, sort_order FROM {$wpdb->prefix}wc_booking_relationships WHERE product_id = %d ORDER BY resource_id", $fixture_id ) );
			$records[ $fixture_id ]['relationships'] = array();
			foreach ( $rows as $row ) {
				$records[ $fixture_id ]['relationships'][ $row->resource_id ] = (int) $row->sort_order;
			}
		}
	}
	echo 'ACCOM_ADMIN:' . wp_json_encode( $records ) . "\n";
} elseif ( 'cleanup' === $fixture_action ) {
	$cleanup( $journal, $fixture_key );
	echo "ACCOM_ADMIN:true\n";
} else {
	throw new RuntimeException( 'Unknown owned admin-save action.' );
}
