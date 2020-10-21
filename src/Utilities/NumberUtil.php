<?php
/**
 * A class of utilities for dealing with numbers.
 */

namespace Automattic\WooCommerceAccommodationBookings\Utilities;

/**
 * A class of utilities for dealing with numbers.
 */
final class NumberUtil {

	/**
	 * Get the absolute value of a number using the built-in `abd` function, but unless the value is numeric
	 * (a number or a string that can be parsed as a number), apply 'floatval' first to it
	 * (so it will convert it to 0 in most cases).
	 *
	 * This is needed because in PHP 7 applying `abs` to a non-numeric value returns 0,
	 * but in PHP 8 it throws an error. Specifically, in WooCommerce we have a few places where
	 * abs('') is often executed.
	 *
	 * @param mixed $val The value to get the absolute value for.
	 *
	 * @return float The absolute value.
	 */
	public static function abs( $val ) : float {
		if ( ! is_numeric( $val ) ) {
			$val = floatval( $val );
		}
		return abs( $val );
	}
}