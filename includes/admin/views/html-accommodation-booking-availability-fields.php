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

	if ( ! isset( $availability['type'] ) ) {
		$availability['type'] = 'custom';
	}

	if ( ! isset( $availability['priority'] ) ) {
		$availability['priority'] = 10;
	}
?>
<tr>
	<td class="sort">&nbsp;</td>
	<td>
		<div class="select wc_booking_availability_type">
			<select name="wc_accommodation_booking_availability_type[]">
				<option value="custom" <?php selected( $availability['type'], 'custom' ); ?>><?php _e( 'Custom date range', 'woocommerce-accommodation-bookings' ); ?></option>
				<option value="months" <?php selected( $availability['type'], 'months' ); ?>><?php _e( 'Range of months', 'woocommerce-accommodation-bookings' ); ?></option>
				<option value="weeks" <?php selected( $availability['type'], 'weeks' ); ?>><?php _e( 'Range of weeks', 'woocommerce-accommodation-bookings' ); ?></option>
				<option value="days" <?php selected( $availability['type'], 'days' ); ?>><?php _e( 'Range of days', 'woocommerce-accommodation-bookings' ); ?></option>
			</select>
		</div>
	</td>
	<td>
		<div class="select from_day_of_week">
			<select name="wc_accommodation_booking_availability_from_day_of_week[]">
				<?php foreach ( $intervals['days'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $availability['from'] ) && $availability['from'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select from_month">
			<select name="wc_accommodation_booking_availability_from_month[]">
				<?php foreach ( $intervals['months'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $availability['from'] ) && $availability['from'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select from_week">
			<select name="wc_accommodation_booking_availability_from_week[]">
				<?php foreach ( $intervals['weeks'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $availability['from'] ) && $availability['from'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="from_date">
			<input type="text" class="date-picker" name="wc_accommodation_booking_availability_from_date[]" value="<?php if ( $availability['type'] == 'custom' && ! empty( $availability['from'] ) ) echo esc_attr( $availability['from'] ) ?>" />
		</div>
	</td>
	<td>
		<div class="select to_day_of_week">
			<select name="wc_accommodation_booking_availability_to_day_of_week[]">
				<?php foreach ( $intervals['days'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $availability['to'] ) && $availability['to'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select to_month">
			<select name="wc_accommodation_booking_availability_to_month[]">
				<?php foreach ( $intervals['months'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $availability['to'] ) && $availability['to'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select to_week">
			<select name="wc_accommodation_booking_availability_to_week[]">
				<?php foreach ( $intervals['weeks'] as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $availability['to'] ) && $availability['to'] == $key, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="to_date">
			<input type="text" class="date-picker" name="wc_accommodation_booking_availability_to_date[]" value="<?php if ( $availability['type'] == 'custom' && ! empty( $availability['to'] ) ) echo esc_attr( $availability['to'] ); ?>" />
		</div>
	</td>
	<td>
		<div class="select">
			<select name="wc_accommodation_booking_availability_bookable[]">
				<option value="no" <?php selected( isset( $availability['bookable'] ) && $availability['bookable'] == 'no', true ) ?>><?php _e( 'No', 'woocommerce-accommodation-bookings' ) ;?></option>
				<option value="yes" <?php selected( isset( $availability['bookable'] ) && $availability['bookable'] == 'yes', true ) ?>><?php _e( 'Yes', 'woocommerce-accommodation-bookings' ) ;?></option>
			</select>
		</div>
	</td>
	<td>
	<div class="priority">
		<input type="number" name="wc_accommodation_booking_availability_priority[]" value="<?php echo esc_attr( $availability['priority'] ); ?>" placeholder="10" />
	</div>
	</td>
	<td class="remove">&nbsp;</td>
</tr>
