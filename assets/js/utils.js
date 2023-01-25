/* globals jQuery */
/**
 * Should return whether the product is a accommodation booking or not.
 *
 * @since x.x.x
 *
 * @param {Object} $booking_form
 *
 * @return {boolean} Returns true if the product is a accommodation booking product type.
 */
export function is_product_type_accommodation_booking($booking_form) {
	return $booking_form
		.closest('.product')
		.hasClass('product-type-accommodation-booking');
}

/**
 * Should return booking form jQuery selector.
 *
 * @since x.x.x
 *
 * @param {Object} $field
 *
 * @return {Object} Return booking form jQuery element.
 */
export function get_booking_form($field) {
	// Convert to jQuery selector.
	$field = get_jquery_element($field);

	return $field.closest('form');
}

/**
 * Should return jQuery selector element.
 *
 * @since x.x.x
 *
 * @param {Object} $field
 *
 * @return {jQuery} Return jQuery element.
 */
export function get_jquery_element($field) {
	return jQuery($field);
}
