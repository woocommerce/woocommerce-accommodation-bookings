<?php
	$intervals = array();

	$intervals['months'] = array(
		'1'  => __( 'January', 'woocommerce-accommodation-bookings' ),
		'2'  => __( 'February', 'woocommerce-accommodation-bookings' ),
		'3'  => __( 'March', 'woocommerce-accommodation-bookings' ),
		'4'  => __( 'April', 'woocommerce-accommodation-bookings' ),
		'5'  => __( 'May', 'woocommerce-accommodation-bookings' ),
		'6'  => __( 'June', 'woocommerce-accommodation-bookings' ),
		'7'  => __( 'July', 'woocommerce-accommodation-bookings' ),
		'8'  => __( 'August', 'woocommerce-accommodation-bookings' ),
		'9'  => __( 'September', 'woocommerce-accommodation-bookings' ),
		'10' => __( 'October', 'woocommerce-accommodation-bookings' ),
		'11' => __( 'November', 'woocommerce-accommodation-bookings' ),
		'12' => __( 'December', 'woocommerce-accommodation-bookings' )
	);

	$intervals['days'] = array(
		'1' => __( 'Monday', 'woocommerce-accommodation-bookings' ),
		'2' => __( 'Tuesday', 'woocommerce-accommodation-bookings' ),
		'3' => __( 'Wednesday', 'woocommerce-accommodation-bookings' ),
		'4' => __( 'Thursday', 'woocommerce-accommodation-bookings' ),
		'5' => __( 'Friday', 'woocommerce-accommodation-bookings' ),
		'6' => __( 'Saturday', 'woocommerce-accommodation-bookings' ),
		'7' => __( 'Sunday', 'woocommerce-accommodation-bookings' )
	);

	for ( $i = 1; $i <= 53; $i ++ ) {
		$intervals['weeks'][ $i ] = sprintf( __( 'Week %s', 'woocommerce-accommodation-bookings' ), $i );
	}

	if ( ! isset( $rate['type'] ) ) {
		$rate['type'] = 'custom';
	}
?>

<tr>
	<td class="sort">&nbsp;</td>
	<td>
		<div class="select wc_booking_availability_type">
			<select name="wc_accommodation_booking_pricing_type[]">
				<option value="custom" <?php selected( $rate['type'], 'custom' ); ?>><?php _e( 'Range of certain nights', 'woocommerce-accommodation-bookings' ); ?></option>
				<option value="months" <?php selected( $rate['type'], 'months' ); ?>><?php _e( 'Range of months', 'woocommerce-accommodation-bookings' ); ?></option>
				<option value="weeks" <?php selected( $rate['type'], 'weeks' ); ?>><?php _e( 'Range of weeks', 'woocommerce-accommodation-bookings' ); ?></option>
				<option value="days" <?php selected( $rate['type'], 'days' ); ?>><?php _e( 'Range of nights during the week', 'woocommerce-accommodation-bookings' ); ?></option>
			</select>
		</div>
	</td>
	<td>
		<div class="select from_day_of_week">
			<select name="wc_accommodation_booking_pricing_from_day_of_week[]">
				<?php foreach ( $intervals['days'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $rate['from'] ) && $rate['from'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select from_month">
			<select name="wc_accommodation_booking_pricing_from_month[]">
				<?php foreach ( $intervals['months'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $rate['from'] ) && $rate['from'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select from_week">
			<select name="wc_accommodation_booking_pricing_from_week[]">
				<?php foreach ( $intervals['weeks'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $rate['from'] ) && $rate['from'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="from_date">
			<input type="text" class="date-picker" name="wc_accommodation_booking_pricing_from_date[]" value="<?php if ( $rate['type'] == 'custom' && ! empty( $rate['from'] ) ) echo esc_attr( $rate['from'] ) ?>" />
		</div>
	</td>
	<td>
		<div class="select to_day_of_week">
			<select name="wc_accommodation_booking_pricing_to_day_of_week[]">
				<?php foreach ( $intervals['days'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $rate['to'] ) && $rate['to'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select to_month">
			<select name="wc_accommodation_booking_pricing_to_month[]">
				<?php foreach ( $intervals['months'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $rate['to'] ) && $rate['to'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select to_week">
			<select name="wc_accommodation_booking_pricing_to_week[]">
				<?php foreach ( $intervals['weeks'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $rate['to'] ) && $rate['to'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="to_date">
			<input type="text" class="date-picker" name="wc_accommodation_booking_pricing_to_date[]" value="<?php if ( $rate['type'] == 'custom' && ! empty( $rate['to'] ) ) echo esc_attr( $rate['to'] ); ?>" />
		</div>

		<div class="to_time">
			<input type="time" class="time-picker" name="wc_accommodation_booking_pricing_to_time[]" value="<?php if ( strrpos( $rate['type'], 'time' ) === 0 && ! empty( $rate['to'] ) ) echo esc_attr( $rate['to'] ); ?>" placeholder="HH:MM" />
		</div>
	</td>
	<td>
		<input type="number" step="0.01" name="wc_accommodation_booking_pricing_block_cost[]" value="<?php if ( ! empty( $rate['override_block'] ) ) echo $rate['override_block']; ?>" placeholder="0" />
		<?php do_action( 'woocommerce_accommodation_bookings_after_booking_pricing_override_block_cost', $rate, $post_id ); ?>
	</td>
	<td class="remove">&nbsp;</td>
</tr>