// External dependencies.
import { __ } from '@wordpress/i18n';
import jQuery from 'jquery';

// Internal dependencies.
import {
	get_booking_form,
	get_jquery_element,
	get_selected_date_type,
	is_product_type_accommodation_booking,
} from './utils';

// eslint-disable-next-line no-unused-vars,@typescript-eslint/no-unused-vars
(function ($) {
	const HookApi = window.wc_bookings.hooks;

	// Filter the date element attributes.
	HookApi.addFilter(
		'wc_bookings_date_picker_get_day_attributes',
		'wc_accommodation_booking/booking_form',
		(
			attributes,
			{ booking_data, custom_data, date_picker, resource_id, date }
		) => {
			const $form = get_booking_form(date_picker);
			const year = date.getFullYear();
			const month = date.getMonth() + 1;
			const day = date.getDate();
			const ymdIndex = `${year}-${month}-${day}`;

			// Exit if product is not accommodation booking.
			if (!is_product_type_accommodation_booking($form)) {
				return attributes;
			}

			if (
				booking_data.fully_booked_start_days &&
				booking_data.fully_booked_start_days[ymdIndex] &&
				(custom_data.resources_assignment === 'automatic' ||
					booking_data.fully_booked_start_days[ymdIndex][0] ||
					booking_data.fully_booked_start_days[ymdIndex][resource_id])
			) {
				attributes.class.push('fully_booked_start_days');
			}

			if (
				booking_data.fully_booked_end_days &&
				booking_data.fully_booked_end_days[ymdIndex] &&
				(custom_data.resources_assignment === 'automatic' ||
					booking_data.fully_booked_end_days[ymdIndex][0] ||
					booking_data.fully_booked_end_days[ymdIndex][resource_id])
			) {
				attributes.class.push('fully_booked_end_days');
			}

			if (attributes.class.indexOf('fully_booked_start_days') > -1) {
				attributes.title = __(
					'Available for check-out only.',
					'woocommerce-accommodation-bookings'
				);
			} else if (attributes.class.indexOf('fully_booked_end_days') > -1) {
				attributes.title = __(
					'Available for check-in only.',
					'woocommerce-accommodation-bookings'
				);
			}

			return attributes;
		}
	);

	// Make the days disable and unselectable according to the selection.
	HookApi.addAction(
		'wc_bookings_date_picker_refreshed',
		'wc_accommodation_booking/booking_form',
		({ date_picker }) => {
			const $form = get_booking_form(date_picker);

			// Exit if product is not accommodation booking.
			if (!is_product_type_accommodation_booking($form)) {
				return;
			}

			$form
				.find('fieldset')
				.attr(
					'data-content',
					__('Select check-in', 'woocommerce-accommodation-bookings')
				);
			$form
				.find('.fully_booked_start_days')
				.addClass('ui-datepicker-unselectable ui-state-disabled');
			$form
				.find('.fully_booked_end_days')
				.removeClass('ui-datepicker-unselectable ui-state-disabled');
		}
	);

	// Add attribute to field set when date selected start date.
	HookApi.addAction(
		'wc_bookings_date_selected',
		'wc_accommodation_booking/booking_form',
		({ fieldset, date_picker }) => {
			const $fieldset = get_jquery_element(fieldset);
			const $date_picker = get_jquery_element(date_picker);
			const date_type = get_selected_date_type($date_picker);
			const $form = get_booking_form(fieldset);
			let data_content = '';

			// Exit if product is not accommodation booking.
			if (!is_product_type_accommodation_booking($form)) {
				return;
			}

			$fieldset.attr('data-selected-date-type', date_type);

			switch (date_type) {
				case 'end':
					data_content = __(
						'Selected! Re-select to change your check-in date.',
						'woocommerce-accommodation-bookings'
					);
					break;

				case 'start':
				default:
					data_content = __(
						'Select check-out',
						'woocommerce-accommodation-bookings'
					);
			}

			$fieldset.attr('data-content', data_content);
		}
	);

	// Toogle accomadated date as per selected date.
	HookApi.addAction(
		'wc_bookings_pre_calculte_booking_cost',
		'wc_accommodation_booking/booking_form',
		({ form, date_picker }) => {
			const $date_picker = get_jquery_element(date_picker);
			const $form = get_jquery_element(form);
			const date_type = get_selected_date_type($date_picker);

			// Exit if product is not accommodation booking.
			if (!is_product_type_accommodation_booking($form)) {
				return;
			}

			switch (date_type) {
				case 'end':
					$form
						.find('.fully_booked_start_days')
						.addClass(
							'ui-datepicker-unselectable ui-state-disabled'
						);
					$form
						.find('.fully_booked_end_days')
						.removeClass(
							'ui-datepicker-unselectable ui-state-disabled'
						);
					break;

				case 'start':
				default:
					$form
						.find('.fully_booked_start_days')
						.removeClass(
							'ui-datepicker-unselectable ui-state-disabled'
						);
					$form
						.find('.fully_booked_end_days')
						.addClass(
							'ui-datepicker-unselectable ui-state-disabled'
						);
			}
		}
	);
})(jQuery);
