/**
 * Should return whether the product is a accommodation booking or not.
 *
 * @since x.x.x
 *
 * @param (object) $booking_form
 *
 * @return {boolean}
 */
export function is_product_type_accommodation_booking($booking_form) {
	return $booking_form.find('.product').hasClass('product-type-accommodation-booking');
}
