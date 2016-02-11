<?php
/*
Plugin Name: WooCommerce Accommodation Bookings
Plugin URI: http://www.woothemes.com/products/woocommerce-accommodation-bookings/
Description: An accommodations add-on for the WooCommerce Bookings extension.
Version: 1.0.2
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

try {
	WC_Accommodation_Dependencies::check_dependencies();
	require_once( 'includes/class-wc-accommodation-bookings-plugin.php' );

	new WC_Accommodation_Bookings_Plugin( __FILE__, '1.0.2' );

} catch ( Exception $e ) {
	$wc_accommodation_bookings_dependencies_not_satisfied_message = $e->getMessage();

	function wc_accommodation_bookings_dependencies_not_satisfied() {
		global $wc_accommodation_bookings_dependencies_not_satisfied_message;
		echo wp_kses_post( sprintf( '<div class="error">%s %s</div>', wpautop( esc_html( $wc_accommodation_bookings_dependencies_not_satisfied_message ) ), wpautop( 'Plugin <strong>deactivated</strong>.' ) ) );
	}
	add_action( 'admin_notices', 'wc_accommodation_bookings_dependencies_not_satisfied' );

	deactivate_plugins( plugin_basename( __FILE__ ) );
}
