<?php
/**
 * WooCommerce Accommodation Bookings REST and Admin required functions.
 *
 * @package woocommerce-accommodation-bookings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required functions for REST API and Admin functionality.
 */
class WC_Accommodation_Booking_REST_And_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'product_type_selector', array( $this, 'product_type_selector' ) );

		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ), 25 );

		// Add debug tool for clearing stale booking transients.
		add_filter( 'woocommerce_debug_tools', array( $this, 'accommodation_bookings_debug_tools' ) );
	}

	/**
	 * Add the accommodation booking product type.
	 *
	 * @param array $types Product types.
	 *
	 * @return array
	 */
	public function product_type_selector( $types ) {
		$types['accommodation-booking'] = __( 'Accommodation product', 'woocommerce-accommodation-bookings' );
		return $types;
	}

	/**
	 * Saves booking / accommodation data for a product.
	 *
	 * @version 1.0.11
	 * @param   int $post_id Post ID.
	 */
	public function save_product_data( $post_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		global $wpdb;

		$product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );

		if ( 'accommodation-booking' !== $product_type ) {
			return;
		}

		$meta_to_save = array(
			'_wc_booking_has_persons'                     => 'issetyesno',
			'_wc_booking_person_qty_multiplier'           => 'yesno',
			'_wc_booking_person_cost_multiplier'          => 'yesno',
			'_wc_booking_min_persons_group'               => 'int',
			'_wc_booking_max_persons_group'               => 'int',
			'_wc_booking_has_person_types'                => 'yesno',
			'_wc_booking_has_resources'                   => 'issetyesno',
			'_wc_booking_resources_assignment'            => '',
			'_wc_booking_resouce_label'                   => '',
			'_wc_accommodation_booking_calendar_display_mode' => '',
			'_wc_accommodation_booking_requires_confirmation' => 'yesno',
			'_wc_accommodation_booking_user_can_cancel'   => '',
			'_wc_accommodation_booking_cancel_limit'      => 'int',
			'_wc_accommodation_booking_cancel_limit_unit' => '',
			'_wc_accommodation_booking_max_date'          => 'max_date',
			'_wc_accommodation_booking_max_date_unit'     => 'max_date_unit',
			'_wc_accommodation_booking_min_date'          => 'int',
			'_wc_accommodation_booking_min_date_unit'     => '',
			'_wc_accommodation_booking_qty'               => 'int',
			'_wc_accommodation_booking_base_cost'         => 'float',
			'_wc_accommodation_booking_display_cost'      => '',
			'_wc_accommodation_booking_min_duration'      => 'int',
			'_wc_accommodation_booking_max_duration'      => 'int',
		);

		foreach ( $meta_to_save as $meta_key => $sanitize ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ?? '' ) );
			switch ( $sanitize ) {
				case 'int':
					$value = $value ? absint( $value ) : '';
					break;
				case 'float':
					$value = $value ? floatval( $value ) : '';
					break;
				case 'yesno':
					$value = 'yes' === $value ? 'yes' : 'no';
					break;
				case 'issetyesno':
					$value = $value ? 'yes' : 'no';
					break;
				case 'max_date':
					$value = absint( $value );
					if ( 0 === $value ) {
						$value = 1;
					}
					break;
			}

			$meta_key = str_replace( '_wc_accommodation_booking_', '_wc_booking_', $meta_key );
			update_post_meta( $post_id, $meta_key, $value );

			if ( '_wc_booking_display_cost' === $meta_key ) {
				update_post_meta( $post_id, '_wc_display_cost', $value );
			}

			if ( '_wc_booking_base_cost' === $meta_key ) {
				update_post_meta( $post_id, '_wc_booking_block_cost', $value );
			}
		}

		// Availability.
		$availability = array();
		$row_size     = isset( $_POST['wc_accommodation_booking_availability_type'] ) ? count( $_POST['wc_accommodation_booking_availability_type'] ) : 0;
		for ( $i = 0; $i < $row_size; $i++ ) {
			$availability[ $i ]['type']     = wc_clean( $_POST['wc_accommodation_booking_availability_type'][ $i ] );
			$availability[ $i ]['bookable'] = wc_clean( $_POST['wc_accommodation_booking_availability_bookable'][ $i ] );
			$availability[ $i ]['priority'] = intval( $_POST['wc_accommodation_booking_availability_priority'][ $i ] );

			switch ( $availability[ $i ]['type'] ) {
				case 'custom':
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_accommodation_booking_availability_from_date'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_accommodation_booking_availability_to_date'][ $i ] );
					break;
				case 'months':
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_accommodation_booking_availability_from_month'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_accommodation_booking_availability_to_month'][ $i ] );
					break;
				case 'weeks':
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_accommodation_booking_availability_from_week'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_accommodation_booking_availability_to_week'][ $i ] );
					break;
				case 'days':
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_accommodation_booking_availability_from_day_of_week'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_accommodation_booking_availability_to_day_of_week'][ $i ] );
					break;
			}
		}
		update_post_meta( $post_id, '_wc_booking_availability', $availability );

		// Restricted days.
		update_post_meta( $post_id, '_wc_booking_has_restricted_days', isset( $_POST['_wc_accommodation_booking_has_restricted_days'] ) );
		$restricted_days = isset( $_POST['_wc_accommodation_booking_restricted_days'] ) ? wc_clean( $_POST['_wc_accommodation_booking_restricted_days'] ) : '';
		update_post_meta( $post_id, '_wc_booking_restricted_days', $restricted_days );

		// Resources.
		if ( isset( $_POST['resource_id'] ) && isset( $_POST['_wc_booking_has_resources'] ) ) {
			$resource_data       = filter_input_array(
				INPUT_POST,
				array(
					'resource_id'         => array(
						'filter' => FILTER_VALIDATE_INT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
					'resource_menu_order' => array(
						'filter' => FILTER_VALIDATE_INT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
					'resource_cost'       => array(
						'filter' => FILTER_VALIDATE_FLOAT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
					'resource_block_cost' => array(
						'filter' => FILTER_VALIDATE_FLOAT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
				)
			);
			$resource_ids        = $resource_data['resource_id'];
			$resource_menu_order = $resource_data['resource_menu_order'];
			$resource_base_cost  = $resource_data['resource_cost'];
			$resource_block_cost = $resource_data['resource_block_cost'];

			$max_loop = max( array_keys( $resource_ids ) );

			$resource_base_costs  = array();
			$resource_block_costs = array();

			for ( $i = 0; $i <= $max_loop; $i++ ) {
				if ( ! isset( $resource_ids[ $i ] ) ) {
					continue;
				}

				$resource_id = absint( $resource_ids[ $i ] );

				$wpdb->update(
					"{$wpdb->prefix}wc_booking_relationships",
					array(
						'sort_order' => absint( $resource_menu_order[ $i ] ),
					),
					array(
						'product_id'  => $post_id,
						'resource_id' => $resource_id,
					)
				);

				$resource_base_costs[ $resource_id ]  = wc_clean( $resource_base_cost[ $i ] );
				$resource_block_costs[ $resource_id ] = wc_clean( $resource_block_cost[ $i ] );
			}

			update_post_meta( $post_id, '_resource_base_costs', $resource_base_costs );
			update_post_meta( $post_id, '_resource_block_costs', $resource_block_costs );
		}

		// Rates.
		$pricing            = array();
		$original_base_cost = abs( (float) get_post_meta( $post_id, '_wc_booking_base_cost', true ) );

		$row_size = isset( $_POST['wc_accommodation_booking_pricing_type'] ) ? count( $_POST['wc_accommodation_booking_pricing_type'] ) : 0;
		for ( $i = 0; $i < $row_size; $i++ ) {
			$pricing[ $i ]['base_cost']     = 0;
			$pricing[ $i ]['cost']          = 0;
			$pricing[ $i ]['type']          = wc_clean( $_POST['wc_accommodation_booking_pricing_type'][ $i ] );
			$new_cost                       = abs( (float) wc_clean( $_POST['wc_accommodation_booking_pricing_block_cost'][ $i ] ) );
			$pricing[ $i ]['base_modifier'] = $new_cost > $original_base_cost ? 'plus' : 'minus';
			$pricing[ $i ]['modifier']      = $pricing[ $i ]['base_modifier'];
			$pricing[ $i ]['cost']          = abs( $new_cost - $original_base_cost );

			switch ( $pricing[ $i ]['type'] ) {
				case 'custom':
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_accommodation_booking_pricing_from_date'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_accommodation_booking_pricing_to_date'][ $i ] );
					break;
				case 'months':
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_accommodation_booking_pricing_from_month'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_accommodation_booking_pricing_to_month'][ $i ] );
					break;
				case 'weeks':
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_accommodation_booking_pricing_from_week'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_accommodation_booking_pricing_to_week'][ $i ] );
					break;
				case 'days':
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_accommodation_booking_pricing_from_day_of_week'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_accommodation_booking_pricing_to_day_of_week'][ $i ] );
					break;
			}
		}

		// Person Types.
		if ( isset( $_POST['person_id'] ) && isset( $_POST['_wc_booking_has_persons'] ) ) {
			$person_data        = filter_input_array(
				INPUT_POST,
				array(
					'person_id'          => array(
						'filter' => FILTER_VALIDATE_INT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
					'person_menu_order'  => array(
						'filter' => FILTER_VALIDATE_INT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
					'person_name'        => array(
						'filter' => FILTER_DEFAULT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
					'person_cost'        => array(
						'filter' => FILTER_VALIDATE_FLOAT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
					'person_block_cost'  => array(
						'filter' => FILTER_VALIDATE_FLOAT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
					'person_description' => array(
						'filter' => FILTER_DEFAULT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
					'person_min'         => array(
						'filter' => FILTER_VALIDATE_INT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
					'person_max'         => array(
						'filter' => FILTER_VALIDATE_INT,
						'flags'  => FILTER_REQUIRE_ARRAY,
					),
				)
			);
			$person_ids         = $person_data['person_id'];
			$person_menu_order  = $person_data['person_menu_order'];
			$person_name        = $person_data['person_name'];
			$person_cost        = $person_data['person_cost'];
			$person_block_cost  = $person_data['person_block_cost'];
			$person_description = $person_data['person_description'];
			$person_min         = $person_data['person_min'];
			$person_max         = $person_data['person_max'];

			$max_loop = max( array_keys( $person_ids ) );

			for ( $i = 0; $i <= $max_loop; $i++ ) {
				if ( ! isset( $person_ids[ $i ] ) ) {
					continue;
				}

				$person_id = absint( $person_ids[ $i ] );

				if ( empty( $person_name[ $i ] ) ) {
					/* translators: %d: person type number */
					$person_name[ $i ] = sprintf( __( 'Person Type #%d', 'woocommerce-accommodation-bookings' ), ( $i + 1 ) );
				}

				wp_update_post(
					array(
						'ID'           => $person_id,
						'post_title'   => stripslashes( $person_name[ $i ] ),
						'post_excerpt' => stripslashes( $person_description[ $i ] ),
						'menu_order'   => $person_menu_order[ $i ],
					)
				);

				update_post_meta( $person_id, 'cost', wc_clean( $person_cost[ $i ] ) );
				update_post_meta( $person_id, 'block_cost', wc_clean( $person_block_cost[ $i ] ) );
				update_post_meta( $person_id, 'min', wc_clean( $person_min[ $i ] ) );
				update_post_meta( $person_id, 'max', wc_clean( $person_max[ $i ] ) );
			}
		}

		update_post_meta( $post_id, '_wc_booking_pricing', $pricing );
		update_post_meta( $post_id, '_wc_booking_cost', '' );

		update_post_meta( $post_id, '_regular_price', '' );
		update_post_meta( $post_id, '_sale_price', '' );
		update_post_meta( $post_id, '_manage_stock', 'no' );

		// Set price so filters work - using get_base_cost().
		$product   = wc_get_product( $post_id );
		$base_cost = $product->get_base_cost();
		update_post_meta( $post_id, '_price', $base_cost );

		// Price has been set to cost * min_duration in meta_lookup table, needs to be updated to maintain consistency.
		$wpdb->update(
			$wpdb->prefix . 'wc_product_meta_lookup',
			array(
				'min_price' => $base_cost,
				'max_price' => $base_cost,
			),
			array(
				'product_id' => $post_id,
			),
			'%d'
		);
	}

	/**
	 * Accommodation Bookings debug tools in WooCommerce > Status > Tools.
	 *
	 * @param array $tools Existing tools.
	 * @return array Tools with Accommodation Bookings tools added.
	 */
	public function accommodation_bookings_debug_tools( $tools ) {
		$accommodation_tools = array(
			'clear_stale_booking_transients' => array(
				'name'     => __( 'Clear stale booking transients', 'woocommerce-accommodation-bookings' ),
				'button'   => __( 'Clear transients', 'woocommerce-accommodation-bookings' ),
				'desc'     => __( 'This tool will clear stale booking transients that may cause availability or booking issues.', 'woocommerce-accommodation-bookings' ),
				'callback' => array( $this, 'clear_stale_booking_transients_tool' ),
			),
		);

		return array_merge( $tools, $accommodation_tools );
	}

	/**
	 * Callback for clearing stale booking transients tool.
	 *
	 * @return string Success message.
	 */
	public function clear_stale_booking_transients_tool() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			return __( 'Error: You do not have permission to run this tool.', 'woocommerce-accommodation-bookings' );
		}

		// Call the method to clear transients.
		$this->clear_stale_booking_transients();

		return __( 'Stale booking transients cleared successfully.', 'woocommerce-accommodation-bookings' );
	}

	/**
	 * Clear stale booking transients that may cause availability or booking issues.
	 * Can be run multiple times safely.
	 *
	 * @return void
	 */
	private function clear_stale_booking_transients() {
		// Step 1: Use parent plugin's method first (clears tracked transients and updates tracking array).
		if ( class_exists( 'WC_Bookings_Cache' ) && method_exists( 'WC_Bookings_Cache', 'delete_booking_slots_transient' ) ) {
			WC_Bookings_Cache::delete_booking_slots_transient(); // No product ID = clears all tracked transients.
		}

		// Step 2: Clear any orphaned transients directly (safety net for transients not in tracking array).
		// This catches transients that got out of sync.
		global $wpdb;

		if ( ! $wpdb || ! isset( $wpdb->options ) ) {
			return; // Database not available.
		}

		// Delete timeout entries.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_book_ts_' ) . '%'
			)
		);

		// Delete transient entries.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_book_ts_' ) . '%'
			)
		);
	}
}

new WC_Accommodation_Booking_REST_And_Admin();
