<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Accommodation booking admin
 */
class WC_Accommodation_Booking_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles_and_scripts' ) );

		add_filter( 'product_type_selector' , array( $this, 'product_type_selector' ) );
		add_filter( 'product_type_options', array( $this, 'product_type_options' ) );

		add_action( 'woocommerce_product_write_panels', array( $this, 'panels' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'general_product_data' ) );

		add_filter( 'woocommerce_product_data_tabs', array( $this, 'hide_shipping_tab' ) );
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'add_tabs' ), 5 );

		add_action( 'woocommerce_process_product_meta', array( $this,'save_product_data' ), 25 );
	}

	/**
	 * Add the accommodation booking product type
	 */
	public function product_type_selector( $types ) {
		$types[ 'accommodation-booking' ] = __( 'Accommodation booking product', 'woocommerce-accommodation-bookings' );
		return $types;
	}

	/**
	 * Tweak product type options
	 * @param  array $options
	 * @return array
	 */
	public function product_type_options( $options ) {
		$options['virtual']['wrapper_class'] .= ' show_if_accommodation_booking';
		return $options;
	}

	/**
	 * Show the accommodation booking data view
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

		wp_enqueue_script( 'wc_accommodation_bookings_writepanel_js', WC_ACCOMMODATION_BOOKINGS_PLUGIN_URL . '/assets/js/writepanel' . $suffix . '.js', array( 'jquery' ), WC_ACCOMMODATION_BOOKINGS_VERSION, true );
	}

	/**
	 * Show the panels related to accommodation bookings
	 */
	public function panels() {
		global $post;

		$post_id = $post->ID;

		include( 'views/html-accommodation-booking-rates.php' );
		include( 'views/html-accommodation-booking-availability.php' );
	}

	/**
	 * Hides the shipping tab for accommodoation products
	 */
	public function hide_shipping_tab( $tabs ) {
		$tabs['shipping']['class'][] = 'hide_if_accommodation_booking';
		return $tabs;
	}

	/**
	 * Shows any tabs related to accommodations.
	 */
	public function add_tabs() {
		include( 'views/html-accommodation-booking-tabs.php' );
	}

	/**
	 * Save booking / accommodation data for the product
	 *
	 * @param  int $post_id
	 */
	public function save_product_data( $post_id ) {
		global $wpdb;

		$product_type         = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );
		$has_additional_costs = false;

		if ( 'accommodation-booking' !== $product_type ) {
			return;
		}

		$meta_to_save = array(
			'_wc_accommodation_booking_calendar_display_mode'      => '',
			'_wc_accommodation_booking_requires_confirmation'      => 'yesno',
			'_wc_accommodation_booking_user_can_cancel'            => '',
			'_wc_accommodation_booking_cancel_limit'               => 'int',
			'_wc_accommodation_booking_cancel_limit_unit'          => '',
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
			update_post_meta( $post_id, $meta_key, $value );
		}

		update_post_meta( $post_id, '_regular_price', '' );
		update_post_meta( $post_id, '_sale_price', '' );
		update_post_meta( $post_id, '_manage_stock', 'no' );

		// Set price so filters work - using get_base_cost()
		$product = get_product( $post_id );
		update_post_meta( $post_id, '_price', $product->get_base_cost() );
	}

}

new WC_Accommodation_Booking_Admin();
