<div class="options_group show_if_accommodation_booking">
	<?php
		woocommerce_wp_select( array(
			'id'          => '_wc_accommodation_booking_calendar_display_mode',
			'label'       => __( 'Calendar display mode', 'woocommerce-accommodation-bookings' ),
			'description' => __( 'Choose how the calendar is displayed on the booking form.', 'woocommerce-accommodation-bookings' ),
			'options'     => array(
				''               => __( 'Display calendar on click', 'woocommerce-accommodation-bookings' ),
				'always_visible' => __( 'Calendar always visible', 'woocommerce-accommodation-bookings' )
			),
			'desc_tip'    => true,
			'class'       => 'select'
		) );

		woocommerce_wp_checkbox( array(
			'id'          => '_wc_accommodation_booking_requires_confirmation',
			'label'       => __( 'Requires confirmation?', 'woocommerce-accommodation-bookings' ),
			'description' => __( 'Check this box if the booking requires admin approval/confirmation. Payment will not be taken during checkout.', 'woocommerce-accommodation-bookings' )
		) );

		woocommerce_wp_checkbox( array(
			'id'          => '_wc_accommodation_booking_user_can_cancel',
			'label'       => __( 'Can be cancelled?', 'woocommerce-accommodation-bookings' ),
			'description' => __( 'Check this box if the booking can be cancelled by the customer after it has been purchased. A refund will not be sent automatically.', 'woocommerce-accommodation-bookings' )
		) );

		$cancel_limit      = max( absint( get_post_meta( $post_id, '_wc_accommodation_booking_cancel_limit', true ) ), 1 );
		$cancel_limit_unit = get_post_meta( $post_id, '_wc_accommodation_booking_cancel_limit_unit', true );
	?>
	<p class="form-field accommodation-booking-cancel-limit">
		<label for="_wc_accommodation_booking_cancel_limit"><?php _e( 'Booking can be cancelled until', 'woocommerce-accommodation-bookings' ); ?></label>
		<input type="number" name="_wc_accommodation_booking_cancel_limit" id="_wc_accommodation_booking_cancel_limit" value="<?php echo esc_attr( $cancel_limit ); ?>" step="1" min="1" style="margin-right: 7px; width: 4em;">
		<select name="_wc_accommodation_booking_cancel_limit_unit" id="_wc_accommodation_booking_cancel_limit_unit" class="short" style="width: auto; margin-right: 7px;">
			<option value="month" <?php selected( $cancel_limit_unit, 'month' ); ?>><?php _e( 'Month(s)', 'woocommerce-accommodation-bookings' ); ?></option>
			<option value="day" <?php selected( $cancel_limit_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-accommodation-bookings' ); ?></option>
			<option value="hour" <?php selected( $cancel_limit_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-accommodation-bookings' ); ?></option>
			<option value="minute" <?php selected( $cancel_limit_unit, 'minute' ); ?>><?php _e( 'Minute(s)', 'woocommerce-accommodation-bookings' ); ?></option>
		</select>
		<span class="description"><?php _e( 'before the start date.', 'woocommerce-accommodation-bookings' ); ?></span>
	</p>

	<script type="text/javascript">
		jQuery( '._tax_status_field' ).closest( '.show_if_simple' ).addClass( 'show_if_accommodation_booking' );
	</script>
</div>
