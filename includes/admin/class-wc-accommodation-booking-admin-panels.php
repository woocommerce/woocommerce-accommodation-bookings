<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Sets up our "write" panels for accommodations products.
 */
class WC_Accommodation_Booking_Admin_Panels {

	/**
	 * Hook into WordPress and WooCommerce
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles_and_scripts' ) );

		add_filter( 'product_type_selector' , array( $this, 'product_type_selector' ) );
		add_filter( 'product_type_options', array( $this, 'product_type_options' ), 15 );

		add_action( 'woocommerce_product_write_panels', array( $this, 'panels' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'general_product_data' ) );

		add_filter( 'woocommerce_product_data_tabs', array( $this, 'hide_shipping_tab' ) );
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'add_tabs' ), 5 );

		add_action( 'woocommerce_process_product_meta', array( $this,'save_product_data' ), 25 );
	}

	/**
	 * Add the accommodation booking product type
	 * @return array
	 */
	public function product_type_selector( $types ) {
		$types[ 'accommodation-booking' ] = __( 'Accommodation product', 'woocommerce-accommodation-bookings' );
		return $types;
	}

	/**
	 * Displays the main accommodation booking settings/data view
	 */
	public function general_product_data() {
		global $post;
		$post_id = $post->ID;
		include( 'views/html-accommodation-booking-data.php' );
	}

	/**
	 * Loads any CSS or JS necessary for the admin
	 */
	public function admin_styles_and_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}

	/**
	 * Loads our panels related to accommodation bookings
	 */
	public function panels() {
		global $post;
		$post_id = $post->ID;

		include( 'views/html-accommodation-booking-rates.php' );
		include( 'views/html-accommodation-booking-availability.php' );
	}


	/**
	 * Hides the "virtal" option for accommodations
	 * @param  array $options
	 * @return array
	 */
	public function product_type_options( $options ) {
		$options['virtual']['wrapper_class'] .= ' hide_if_accommodation-booking';
		$options['wc_booking_has_resources']['wrapper_class'] .= ' show_if_accommodation-booking';
		return $options;
	}

	/**
	 * Hides the shipping tab for accommodoation products
	 */
	public function hide_shipping_tab( $tabs ) {
		$tabs['shipping']['class'][] = 'hide_if_accommodation-booking';
		return $tabs;
	}

	/**
	 * Loads the HTML that is used to display the actual tab navigation
	 */
	public function add_tabs() {
		include( 'views/html-accommodation-booking-tabs.php' );
	}

	/**
	 * Saves booking / accommodation data for a product
	 *
	 * @param int $post_id
	 */
	public function save_product_data( $post_id ) {
		global $wpdb;

		$product_type         = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );
		$has_additional_costs = false;

		if ( 'accommodation-booking' !== $product_type ) {
			return;
		}

		$meta_to_save = array(
			'_wc_booking_has_resources'                            => 'issetyesno',
			'_wc_booking_resources_assignment'                     => '',
			'_wc_booking_resouce_label'                            => '',
			'_wc_accommodation_booking_calendar_display_mode'      => '',
			'_wc_accommodation_booking_requires_confirmation'      => 'yesno',
			'_wc_accommodation_booking_user_can_cancel'            => '',
			'_wc_accommodation_booking_cancel_limit'               => 'int',
			'_wc_accommodation_booking_cancel_limit_unit'          => '',
			'_wc_accommodation_booking_max_date'                   => 'max_date',
			'_wc_accommodation_booking_max_date_unit'              => 'max_date_unit',
			'_wc_accommodation_booking_min_date'                   => 'int',
			'_wc_accommodation_booking_min_date_unit'              => '',
			'_wc_accommodation_booking_qty'                        => 'int',
			'_wc_accommodation_booking_base_cost'                  => 'float',
			'_wc_accommodation_booking_min_duration'               => 'int',
			'_wc_accommodation_booking_max_duration'               => 'int',
		);

		foreach ( $meta_to_save as $meta_key => $sanitize ) {
			$value = ! empty( $_POST[ $meta_key ] ) ? $_POST[ $meta_key ] : '';
			switch ( $sanitize ) {
				case 'int' :
					$value = $value ? absint( $value ) : '';
					break;
				case 'float' :
					$value = $value ? floatval( $value ) : '';
					break;
				case 'yesno' :
					$value = $value == 'yes' ? 'yes' : 'no';
					break;
				case 'issetyesno' :
					$value = $value ? 'yes' : 'no';
					break;
				case 'max_date' :
					$value = absint( $value );
					if ( $value == 0 ) {
						$value = 1;
					}
					break;
				default :
					$value = sanitize_text_field( $value );
			}

			$meta_key = str_replace( '_wc_accommodation_booking_', '_wc_booking_', $meta_key );
			update_post_meta( $post_id, $meta_key, $value );
		}

		// Availability
		$availability = array();
		$row_size     = isset( $_POST[ 'wc_accommodation_booking_availability_type' ] ) ? sizeof( $_POST[ 'wc_accommodation_booking_availability_type' ] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			$availability[ $i ]['type']     = wc_clean( $_POST[ 'wc_accommodation_booking_availability_type' ][ $i ] );
			$availability[ $i ]['bookable'] = wc_clean( $_POST[ 'wc_accommodation_booking_availability_bookable' ][ $i ] );
			$availability[ $i ]['priority'] = intval( $_POST[ 'wc_accommodation_booking_availability_priority' ][ $i ] );

			switch ( $availability[ $i ]['type'] ) {
				case 'custom' :
					$availability[ $i ]['from'] = wc_clean( $_POST[ 'wc_accommodation_booking_availability_from_date' ][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST[ 'wc_accommodation_booking_availability_to_date' ][ $i ] );
				break;
				case 'months' :
					$availability[ $i ]['from'] = wc_clean( $_POST[ 'wc_accommodation_booking_availability_from_month' ][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST[ 'wc_accommodation_booking_availability_to_month' ][ $i ] );
				break;
				case 'weeks' :
					$availability[ $i ]['from'] = wc_clean( $_POST[ 'wc_accommodation_booking_availability_from_week' ][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST[ 'wc_accommodation_booking_availability_to_week' ][ $i ] );
				break;
				case 'days' :
					$availability[ $i ]['from'] = wc_clean( $_POST[ 'wc_accommodation_booking_availability_from_day_of_week' ][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST[ 'wc_accommodation_booking_availability_to_day_of_week' ][ $i ] );
				break;
			}
		}
		update_post_meta( $post_id, '_wc_booking_availability', $availability );

		// Resources
		if ( isset( $_POST['resource_id'] ) && isset( $_POST['_wc_booking_has_resources'] ) ) {
			$resource_ids         = $_POST['resource_id'];
			$resource_menu_order  = $_POST['resource_menu_order'];
			$resource_base_cost   = $_POST['resource_cost'];
			$resource_block_cost  = $_POST['resource_block_cost'];
			$max_loop             = max( array_keys( $_POST['resource_id'] ) );
			$resource_base_costs  = array();
			$resource_block_costs = array();

			for ( $i = 0; $i <= $max_loop; $i ++ ) {
				if ( ! isset( $resource_ids[ $i ] ) ) {
					continue;
				}

				$resource_id = absint( $resource_ids[ $i ] );

				$wpdb->update(
					"{$wpdb->prefix}wc_booking_relationships",
					array(
						'sort_order'  => $resource_menu_order[ $i ]
					),
					array(
						'product_id'  => $post_id,
						'resource_id' => $resource_id
					)
				);

				$resource_base_costs[ $resource_id ]  = wc_clean( $resource_base_cost[ $i ] );
				$resource_block_costs[ $resource_id ] = wc_clean( $resource_block_cost[ $i ] );

				if ( ( $resource_base_cost[ $i ] + $resource_block_cost[ $i ] ) > 0 ) {
					$has_additional_costs = true;
				}
			}

			update_post_meta( $post_id, '_resource_base_costs', $resource_base_costs );
			update_post_meta( $post_id, '_resource_block_costs', $resource_block_costs );
		}
		
		// Rates
		$pricing = array();
		$row_size     = isset( $_POST[ 'wc_accommodation_booking_pricing_type' ] ) ? sizeof( $_POST[ 'wc_accommodation_booking_pricing_type' ] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			$pricing[ $i ]['type']          = wc_clean( $_POST[ 'wc_accommodation_booking_pricing_type' ][ $i ] );
			$pricing[ $i ]['base_cost'] = $pricing[ $i ]['cost'] = 0;
			$pricing[ $i ]['base_modifier'] = $pricing[$i]['modifier'] = 'plus';
			$pricing[ $i ]['override_block'] = wc_clean( $_POST[ 'wc_accommodation_booking_pricing_block_cost' ][ $i ] );

			switch ( $pricing[ $i ]['type'] ) {
				case 'custom' :
					$pricing[ $i ]['from'] = wc_clean( $_POST[ 'wc_accommodation_booking_pricing_from_date' ][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST[ 'wc_accommodation_booking_pricing_to_date' ][ $i ] );
				break;
				case 'months' :
					$pricing[ $i ]['from'] = wc_clean( $_POST[ 'wc_accommodation_booking_pricing_from_month' ][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST[ 'wc_accommodation_booking_pricing_to_month' ][ $i ] );
				break;
				case 'weeks' :
					$pricing[ $i ]['from'] = wc_clean( $_POST[ 'wc_accommodation_booking_pricing_from_week' ][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST[ 'wc_accommodation_booking_pricing_to_week' ][ $i ] );
				break;
				case 'days' :
					$pricing[ $i ]['from'] = wc_clean( $_POST[ 'wc_accommodation_booking_pricing_from_day_of_week' ][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST[ 'wc_accommodation_booking_pricing_to_day_of_week' ][ $i ] );
				break;
			}
		}

		update_post_meta( $post_id, '_wc_booking_pricing', $pricing );

		update_post_meta( $post_id, '_regular_price', '' );
		update_post_meta( $post_id, '_sale_price', '' );
		update_post_meta( $post_id, '_manage_stock', 'no' );

		// Set price so filters work - using get_base_cost()
		$product = get_product( $post_id );
		update_post_meta( $post_id, '_price', $product->get_base_cost() );
	}

}

new WC_Accommodation_Booking_Admin_Panels();
