import jQuery from 'jquery';

jQuery(function ($) {
	$('#rates_rows').sortable({
		items: 'tr',
		cursor: 'move',
		axis: 'y',
		handle: '.sort',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'wc-metabox-sortable-placeholder',
		start(event, ui) {
			ui.item.css('background-color', '#f6f6f6');
		},
		stop(event, ui) {
			ui.item.removeAttr('style');
		},
	});

	function wc_accommodation_bookings_trigger_change_events() {
		$('#_wc_accommodation_booking_has_restricted_days').trigger('change');
	}

	$('#_wc_accommodation_booking_has_restricted_days').on(
		'change',
		function () {
			if ($(this).is(':checked')) {
				$('.booking-day-restriction').show();
			} else {
				$('.booking-day-restriction').hide();
			}
		}
	);

	wc_accommodation_bookings_trigger_change_events();
});
