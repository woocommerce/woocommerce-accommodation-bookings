<?php
/*
Plugin Name: WooCommerce Accommodation Bookings
Plugin URI: http://www.woothemes.com/products/woocommerce-accommodation-bookings/
Description: An accommodations add-on for the WooCommerce Bookings extension.
Version: 1.0.0
Author: WooThemes
Author URI: http://woothemes.com
Text Domain: woocommerce-accommodation-bookings
Domain Path: /languages

Copyright: © 2015 WooThemes.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once( 'includes/class-wc-accommodation-dependencies.php' );

/**
 * Plugin updates
 */

if ( WC_Accommodation_Dependencies::dependencies_are_met() ) {

/**
 * WC Accommodation Bookings class
 */
class WC_Accommodation_Bookings {

	/**
	 * Constructor
	 */
	public function __construct() {
		define( 'WC_ACCOMMODATION_BOOKINGS_VERSION', '1.0.0' );
		define( 'WC_ACCOMMODATION_BOOKINGS_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
		define( 'WC_ACCOMMODATION_BOOKINGS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_ACCOMMODATION_BOOKINGS_MAIN_FILE', __FILE__ );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'woocommerce_loaded', array( $this, 'includes' ), 20 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'booking_form_styles' ) );

		if ( is_admin() ) {
			$this->admin_includes();
		}
	}

	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-accommodation-bookings' );
		$dir    = trailingslashit( WP_LANG_DIR );

		load_textdomain( 'woocommerce-accommodation-bookings', $dir . 'woocommerce-accommodation-bookings/woocommerce-accommodation-bookings-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-accommodation-bookings', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Load Classes
	 */
	public function includes() {
		include( 'includes/class-wc-product-accommodation-booking.php' );
		include( 'includes/class-wc-accommodation-booking.php' );
		include( 'includes/class-wc-accommodation-booking-cart-manager.php' );
		include( 'includes/class-wc-accommodation-booking-date-picker.php' );
		include( 'includes/class-wc-accommodation-booking-product-tabs.php' );
		include( 'includes/class-wc-accommodation-booking-order-info.php' );
	}

	/**
	 * Include admin
	 */
	public function admin_includes() {
		include( 'includes/admin/class-wc-accommodation-booking-admin-panels.php' );
		include( 'includes/admin/class-wc-accommodation-booking-admin-product-settings.php' );
	}

	/**
	 * Frontend booking form scripts
	 */
	public function booking_form_styles() {
		wp_enqueue_style( 'wc-accommodation-bookings-styles', WC_ACCOMMODATION_BOOKINGS_PLUGIN_URL . '/assets/css/frontend.css', null, WC_ACCOMMODATION_BOOKINGS_VERSION );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @access	public
	 * @param	mixed $links Plugin Row Meta
	 * @param	mixed $file  Plugin Base file
	 * @return	array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file == plugin_basename( WC_ACCOMMODATION_BOOKINGS_MAIN_FILE ) ) {
			$row_meta = array(
				'docs'		=>	'<a href="' . esc_url( apply_filters( 'woocommerce_accommodation_bookings_docs_url', 'http://docs.woothemes.com/documentation/plugins/woocommerce/woocommerce-extensions/accommodation-bookings/' ) ) . '" title="' . esc_attr( __( 'View Documentation', 'woocommerce-accommodation-bookings' ) ) . '">' . __( 'Docs', 'woocommerce-accommodation-bookings' ) . '</a>',
				'support'	=>	'<a href="' . esc_url( apply_filters( 'woocommerce_accommodation_bookings_support_url', 'http://support.woothemes.com/' ) ) . '" title="' . esc_attr( __( 'Visit Premium Customer Support Forum', 'woocommerce-accommodation-bookings' ) ) . '">' . __( 'Premium Support', 'woocommerce-accommodation-bookings' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}
}

new WC_Accommodation_Bookings();

}
