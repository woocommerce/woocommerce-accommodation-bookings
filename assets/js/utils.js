/**
 * Should return whether the product is a accommodation booking or not.
 *
 * @since x.x.x
 *
 * @param (object) $booking_form
 *
 * @return {boolean}
 */
export function is_product_type_accommodation_booking( $booking_form ) {
	return $booking_form.closest( '.product' ).hasClass( 'product-type-accommodation-booking' );
}

/**
 * Should return booking form jQuery selector.
 *
 * @since x.x.x
 *
 * @param (object) $field
 *
 * @return {object}
 */
export function get_booking_form( $field ) {
	// Convert to jQuery selector.
	$field = get_jquery_element( $field );

	return $field.closest( 'form' );
}

/**
 * Should return jQuery selector element.
 *
 * @since x.x.x
 *
 * @param {object} $field
 *
 * @return {jQuery}
 */
export function get_jquery_element( $field ) {
	return jQuery( $field )
}
