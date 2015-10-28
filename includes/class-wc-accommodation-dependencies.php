<?php
/**
 * WC Accommodation Bookings Dependency Checker
 *
 * Checks if WooCommerce Bookings is enabled
 */
class WC_Accommodation_Dependencies {

	/**@var an array of active plugins*/
	private static $active_plugins;

	/**
	 * Load a list of our currently active WordPress plugins
	 */
	public static function init() {
		self::$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() )
			self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
	}

	/**
	 * Reports if all of the accommodation extension's dependencies are met
	 * @return bool true if WooCommerce bookings is an active plugin, false if not.
	 */
	public static function dependencies_are_met() {
		if ( ! self::$active_plugins ) self::init();

		return in_array( 'woocommerce-bookings/woocommmerce-bookings.php', self::$active_plugins ) || array_key_exists( 'woocommerce-bookings/woocommmerce-bookings.php', self::$active_plugins );
	}

}
