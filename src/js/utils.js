import jQuery from 'jquery';
/**
 * Should return whether the product is a accommodation booking or not.
 *
 * @since 1.1.40
 *
 * @param {Object} $booking_form
 *
 * @return {boolean} Returns true if the product is a accommodation booking product type.
 */
export function is_product_type_accommodation_booking($booking_form) {
	return (
		$booking_form.closest('.product.product-type-accommodation-booking')
			.length > 0
	);
}

/**
 * Should return booking form jQuery selector.
 *
 * @since 1.1.40
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
 * @since 1.1.40
 *
 * @param {Object} $field jQuery selector.
 *
 * @return {jQuery} Return jQuery element.
 */
export function get_jquery_element($field) {
	return jQuery($field);
}

/**
 * Should return date type.
 *
 * @since 1.1.40
 * @param {jQuery} $date_picker Date picker jQuery element.
 * @return {string} date_type Selected date type. Value can be 'start' or 'end'.
 */
export function get_selected_date_type($date_picker) {
	const next_date_type = $date_picker.data('start_or_end_date');
	let date_type = null;

	switch (next_date_type) {
		case 'end':
			date_type = 'start';
			break;

		case 'start':
		default:
			date_type = 'end';
	}

	return date_type;
}
