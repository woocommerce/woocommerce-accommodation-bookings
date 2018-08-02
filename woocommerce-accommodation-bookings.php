<?php
/*
 * Plugin Name: WooCommerce Accommodation Bookings
 * Plugin URI: https://woocommerce.com/products/woocommerce-accommodation-bookings/
 * Description: An accommodations add-on for the WooCommerce Bookings extension.
 * Version: 1.1.3
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce-accommodation-bookings
 * Domain Path: /languages
 * WC tested up to: 3.3
 * WC requires at least: 2.6
 *
 * Copyright: © 2015-2017 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( 'includes/class-wc-accommodation-bookings-plugin.php' );
$plugin = new WC_Accommodation_Bookings_Plugin( __FILE__, '1.1.3' );
$plugin->run();
