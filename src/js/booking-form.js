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

			// Add data attributes for easier access to date values
			// This ensures screen reader text works correctly for all months
			attributes['data-day'] = day;
			attributes['data-month'] = month - 1; // 0-based for consistency with JS Date
			attributes['data-year'] = year;

			return attributes;
		}
	);

	const INFO_ICON_SVG =
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>';

	/**
	 * Add info icons with tooltips to partially available date cells.
	 *
	 * @param {jQuery} $form - The booking form element.
	 */
	const addPartialAvailabilityIcons = ($form) => {
		$form.find('.partial-availability-info-wrapper').remove();

		const createInfoIcon = (message) => {
			return $(`
				<div class="partial-availability-info-wrapper">
					<button type="button" class="partial-availability-info" aria-label="${message}">
						${INFO_ICON_SVG}
					</button>
					<div class="partial-availability-tooltip" role="tooltip" aria-hidden="true">${message}</div>
				</div>
			`);
		};

		const appendIcon = ($cell, message) => {
			if ($cell.find('.partial-availability-info-wrapper').length) {
				return;
			}
			const $dayElement = $cell.children('span, a').first();
			const $target = $dayElement.length ? $dayElement : $cell;
			$target.append(createInfoIcon(message));
		};

		$form.find('.fully_booked_start_days').each(function () {
			appendIcon(
				$(this),
				__(
					'Available for check-out only.',
					'woocommerce-accommodation-bookings'
				)
			);
		});

		$form.find('.fully_booked_end_days').each(function () {
			appendIcon(
				$(this),
				__(
					'Available for check-in only.',
					'woocommerce-accommodation-bookings'
				)
			);
		});
	};

	// Prevent date selection when clicking the info icon button.
	$(document).on(
		'click mousedown',
		'.partial-availability-info',
		function (e) {
			e.stopPropagation();
			e.preventDefault();
		}
	);

	/**
	 * Add accessible text to datepicker cells.
	 *
	 * @param {jQuery} $cell          - The datepicker cell element.
	 * @param {string} accessibleText - The text to announce to screen readers.
	 */
	const addAccessibleText = ($cell, accessibleText) => {
		const $dayElement = $cell.find('span, a').first();
		const $targetElement = $dayElement.length ? $dayElement : $cell;

		// Remove any existing screen reader text to avoid duplication
		$targetElement.find('.screen-reader-text').remove();

		// Add screen reader text
		$targetElement.append(
			`<span class="screen-reader-text"> ${accessibleText}</span>`
		);
	};

	// Shared month and day names for screen reader formatting
	const MONTH_NAMES = [
		__('January', 'woocommerce-accommodation-bookings'),
		__('February', 'woocommerce-accommodation-bookings'),
		__('March', 'woocommerce-accommodation-bookings'),
		__('April', 'woocommerce-accommodation-bookings'),
		__('May', 'woocommerce-accommodation-bookings'),
		__('June', 'woocommerce-accommodation-bookings'),
		__('July', 'woocommerce-accommodation-bookings'),
		__('August', 'woocommerce-accommodation-bookings'),
		__('September', 'woocommerce-accommodation-bookings'),
		__('October', 'woocommerce-accommodation-bookings'),
		__('November', 'woocommerce-accommodation-bookings'),
		__('December', 'woocommerce-accommodation-bookings'),
	];

	const DAY_NAMES = [
		__('Sunday', 'woocommerce-accommodation-bookings'),
		__('Monday', 'woocommerce-accommodation-bookings'),
		__('Tuesday', 'woocommerce-accommodation-bookings'),
		__('Wednesday', 'woocommerce-accommodation-bookings'),
		__('Thursday', 'woocommerce-accommodation-bookings'),
		__('Friday', 'woocommerce-accommodation-bookings'),
		__('Saturday', 'woocommerce-accommodation-bookings'),
	];

	/**
	 * Format date for screen reader announcement.
	 *
	 * @param {jQuery} $cell - The datepicker cell element.
	 * @return {string} Formatted date string.
	 */
	const formatDateForScreenReader = ($cell) => {
		// Get day from data-day attribute or extract from text content
		let day = $cell.attr('data-day');
		if (!day) {
			const $dayElement = $cell.find('span, a').first();
			const textContent = $dayElement.length
				? $dayElement.text()
				: $cell.text();
			day = textContent.trim().match(/^\d+/)?.[0];
		}

		// Get month and year from cell attributes or datepicker header
		let dataMonth = $cell.attr('data-month');
		let dataYear = $cell.attr('data-year');

		if (dataMonth === undefined || dataYear === undefined) {
			const $datepicker = $cell.closest('.ui-datepicker');
			const $monthEl = $datepicker.find('.ui-datepicker-month');
			const $yearEl = $datepicker.find('.ui-datepicker-year');

			dataMonth = $monthEl.is('select')
				? $monthEl.val()
				: MONTH_NAMES.findIndex((m) =>
						$monthEl.text().toLowerCase().includes(m.toLowerCase())
				  );

			dataYear = $yearEl.is('select')
				? $yearEl.val()
				: $yearEl.text().trim();
		}

		if (
			!day ||
			dataMonth === undefined ||
			dataMonth === null ||
			dataMonth === -1 ||
			!dataYear
		) {
			return '';
		}

		const date = new Date(
			parseInt(dataYear, 10),
			parseInt(dataMonth, 10),
			parseInt(day, 10)
		);
		return `${MONTH_NAMES[date.getMonth()]}, ${dataYear}, ${
			DAY_NAMES[date.getDay()]
		},`;
	};

	/**
	 * Add accessible text to all booking date types in the form.
	 *
	 * @param {jQuery} $form - The booking form element.
	 */
	const addAccessibleTextToBookingDates = ($form) => {
		// Add screen reader text for partially available dates (check-out only)
		$form.find('.fully_booked_start_days').each(function () {
			const $cell = $(this);
			const formattedDate = formatDateForScreenReader($cell);
			const accessibleText = `${formattedDate} ${__(
				'Available for check-out only.',
				'woocommerce-accommodation-bookings'
			)}`;
			addAccessibleText($cell, accessibleText);
		});

		// Add screen reader text for partially available dates (check-in only)
		$form.find('.fully_booked_end_days').each(function () {
			const $cell = $(this);
			const formattedDate = formatDateForScreenReader($cell);
			const accessibleText = `${formattedDate} ${__(
				'Available for check-in only.',
				'woocommerce-accommodation-bookings'
			)}`;
			addAccessibleText($cell, accessibleText);
		});

		// Add screen reader text for fully booked dates (both start and end unavailable)
		$form.find('.fully_booked').each(function () {
			const $cell = $(this);
			const formattedDate = formatDateForScreenReader($cell);
			const accessibleText = `${formattedDate} ${__(
				'Fully booked and unavailable.',
				'woocommerce-accommodation-bookings'
			)}`;
			addAccessibleText($cell, accessibleText);
		});
	};

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

			// Set --wc-bookable-bg from a fully-bookable cell so the
			// partial-availability gradient uses the theme's color.
			const $bookableLink = $form
				.find(
					'td.bookable:not(.fully_booked_start_days):not(.fully_booked_end_days):not(.fully_booked) a'
				)
				.first();
			if ($bookableLink.length) {
				const bgColor = window.getComputedStyle(
					$bookableLink[0]
				).backgroundColor;
				$form
					.find('.wc-bookings-date-picker')
					.css('--wc-bookable-bg', bgColor);
			}

			// Add screen reader text and info icons for booking date types
			addAccessibleTextToBookingDates($form);
			addPartialAvailabilityIcons($form);

			// Observe future DOM changes (month nav, cache) to re-add icons
			setupDatepickerObserver($form);
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

			// Re-add screen reader text and info icons after date selection triggers refresh
			setTimeout(() => {
				addAccessibleTextToBookingDates($form);
				addPartialAvailabilityIcons($form);
			}, 100);
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

	/**
	 * Set up a MutationObserver on the datepicker to detect DOM changes
	 * (month navigation, AJAX refresh, cache loads) and re-add icons and
	 * screen reader text when partial-availability cells appear without them.
	 *
	 * @param {jQuery} $form - The booking form element.
	 */
	const setupDatepickerObserver = ($form) => {
		const container = $form.find('.wc-bookings-date-picker')[0];
		if (!container || container.__partialIconObserver) {
			return;
		}

		let debounceTimer = null;
		const observer = new MutationObserver(() => {
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(() => {
				const needsUpdate =
					$form
						.find(
							'.fully_booked_start_days, .fully_booked_end_days'
						)
						.filter(function () {
							return (
								$(this).find(
									'.partial-availability-info-wrapper'
								).length === 0
							);
						}).length > 0;

				if (needsUpdate) {
					addAccessibleTextToBookingDates($form);
					addPartialAvailabilityIcons($form);
				}
			}, 100);
		});

		observer.observe(container, { childList: true, subtree: true });
		container.__partialIconObserver = observer;
	};
})(jQuery);
