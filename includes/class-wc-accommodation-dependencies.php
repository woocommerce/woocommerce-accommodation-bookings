<?php
/**
 * WC Accommodation Bookings Dependency Checker
 *
 * Checks if WooCommerce Bookings is enabled and if it is the correct
 * version for accommodations to work.
 */
class WC_Accommodation_Dependencies {

	/**@var an array of active plugins*/
	private static $active_plugins;

	/**
	 * Load a list of our currently active WordPress plugins
	 */
	public static function init() {
		self::$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
	}

	/**
	 * Returns true if bookings is installed/active and false if not
	 * @return boolean
	 */
	private static function is_bookings_installed() {
		return in_array( 'woocommerce-bookings/woocommmerce-bookings.php', self::$active_plugins ) || array_key_exists( 'woocommerce-bookings/woocommmerce-bookings.php', self::$active_plugins );
	}

	/**
	 * Returns true if bookings is greater than a specific version and false if not
	 * @param  string  $verson The version to check against
	 * @return boolean
	 */
	private static function is_bookings_above_or_equal_to_version( $version ) {
		if ( version_compare( get_option( 'wc_bookings_version' ), $version, '>=' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Reports if all of the accommodation extension's dependencies are met
	 * @return boolean
	 */
	public static function dependencies_are_met() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( ! self::is_bookings_installed() ) {
			return false;
		}

		if ( ! self::is_bookings_above_or_equal_to_version( '1.9.0' ) ) {
			return false;
		}

		return true;
	}

}
