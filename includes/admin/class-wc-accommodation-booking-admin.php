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

		//wp_enqueue_script( 'wc_bookings_writepanel_js' );
		//include( 'views/html-booking-persons.php' );
	}

	/**
	 * Hides the shipping tab for accommodoation products
	 */
	public function hide_shipping_tab( $tabs ) {
		$tabs['shipping']['class'][] = 'hide_if_accommodation_booking';
		error_log( print_r ( $tabs, 1 ) );
		return $tabs;
	}


}

new WC_Accommodation_Booking_Admin();
