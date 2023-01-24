// Internal dependencies.
import {is_product_type_accommodation_booking} from './utils'

(
	function ( $ ) {
		const HookApi = window.wc_bookings.hooks;

		// Remove selected day type attribute to match the design colors in newly switched resource.
		HookApi.addAction(
			'wc_bookings_form_field_change',
			'wc_accommodation_booking/booking_form',
			( $field ) => {
				const field_name = $( this ).attr( 'name' );

				if ( 'wc_bookings_field_resource' === field_name ) {
					$( '.wc-bookings-booking-form fieldset' ).removeAttr( 'selected_date_type' );
				}
			}
		);

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

				$form.find( 'fieldset' ).attr( 'data-content', wc_accommodation_bookings_form.i18n_check_in );
				$form.find( '.fully_booked_start_days' ).addClass( 'ui-datepicker-unselectable ui-state-disabled' );
				$form.find( '.fully_booked_end_days' ).removeClass( 'ui-datepicker-unselectable ui-state-disabled' );
			}
		);

		// Add attribute to field set when date selected start date.
		HookApi.addAction(
			'wc_bookings_date_selected',
			'wc_accommodation_booking/booking_form',
			( $fieldset, $picker ) => {
				const date_type = $fieldset.attr( 'start_or_end_date' );
				let data_content = '';

				switch ( date_type ) {
					case 'end':
						data_content = wc_accommodation_bookings_form.i18n_check_out;
						break;

					case 'start':
					default:
						data_content = wc_accommodation_bookings_form.i18n_check_in_again;
				}

				$fieldset.attr( 'data-content', data_content );
			}
		);

		// Toogle accomadated date as per selected date.
		HookApi.addAction(
			'wc_bookings_before_calculte_booking_cost',
			'wc_accommodation_booking/booking_form',
			( $field, $fieldset, $picker, $form ) => {
				const date_type = $fieldset.attr( 'start_or_end_date' );

				switch ( date_type ) {
					case 'end':
						$form.find( '.fully_booked_start_days' )
							.addClass( 'ui-datepicker-unselectable ui-state-disabled' );
						$form.find( '.fully_booked_end_days' )
							.removeClass( 'ui-datepicker-unselectable ui-state-disabled' );
						break;

					case 'start':
					default:
						$form.find( '.fully_booked_start_days' )
							.removeClass( 'ui-datepicker-unselectable ui-state-disabled' );
						$form.find( '.fully_booked_end_days' )
							.addClass( 'ui-datepicker-unselectable ui-state-disabled' );
				}
			}
		);
	}
)( jQuery )

// @todo: apply login to accommodation booking product.
