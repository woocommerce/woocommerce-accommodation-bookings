(function($){
	const HookApi = window.wc_bookings.hooks;

	// Filter the date element attributes.
	HookApi.addFilter(
		'wc_bookings_date_picker_get_day_attributes',
		'wc_accommodation_booking/booking_form',
		( attributes, booking_data, $date_picker ) => {
			return attributes;
		}
	);

	// Make the days disable and unselectable according to the selection.
	HookApi.addAction(
		'wc_bookings_date_picker_refreshed',
		'wc_accommodation_booking/booking_form',
		( $date_picker ) => {
			const $form = $date_picker.closest( 'form.wc-bookings-booking-form' );
			console.log( params );

			$form.find( 'fieldset' ).attr( 'data-content', wc_accommodation_bookings_form.i18n_check_in );
			$form.find( '.fully_booked_start_days' ).addClass( 'ui-datepicker-unselectable ui-state-disabled' );
			$form.find( '.fully_booked_end_days' ).removeClass( 'ui-datepicker-unselectable ui-state-disabled' );
		}
	);

	// Add attribute to field set when date selected start date.
	HookApi.addAction(
		'wc_bookings_date_selected',
		'wc_accommodation_booking/booking_form',
		( params ) => {
			console.log( params );

			// start date
			//fieldset.attr( 'data-content', booking_form_params.i18n_check_in_again );

			// end date
			//fieldset.attr( 'data-content', booking_form_params.i18n_check_out );

			//selected date
			//fieldset.attr( 'data-content', booking_form_params.i18n_check_out );
		}
	);

	// Toogle accomadated date as per selected date.
	HookApi.addAction(
		'wc_bookings_before_calculte_booking_cost',
		'wc_accommodation_booking/booking_form',
		( params ) => {
			console.log( params );

			// Make the days disable and unselectable according to the selection.
			// if ( 'end' === $( '.wc-bookings-booking-form fieldset' ).attr( 'selected_date_type' ) ) {
			// 	$( '.fully_booked_start_days' ).addClass( 'ui-datepicker-unselectable ui-state-disabled' );
			// 	$( '.fully_booked_end_days' ).removeClass( 'ui-datepicker-unselectable ui-state-disabled' );
			// } else {
			// 	$( '.fully_booked_start_days' ).removeClass( 'ui-datepicker-unselectable ui-state-disabled' );
			// 	$( '.fully_booked_end_days' ).addClass( 'ui-datepicker-unselectable ui-state-disabled' );
			// }
		}
	);
})(jQuery)
