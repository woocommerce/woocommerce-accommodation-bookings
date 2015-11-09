<div id="accommodation_bookings_availability" class="panel woocommerce_options_panel bookings_extension">
	<div class="options_group">
		<?php woocommerce_wp_text_input( array( 'id' => '_wc_accommodation_booking_qty', 'label' => __( 'Number of rooms available', 'woocommerce-accommodation-bookings' ), 'description' => __( 'The maximum number of rooms available.', 'woocommerce-accommodation-bookings' ), 'value' => max( absint( get_post_meta( $post_id, '_wc_booking_qty', true ) ), 1 ), 'desc_tip' => true, 'type' => 'number', 'custom_attributes' => array(
			'min'   => '',
			'step' 	=> '1'
		) ) ); ?>
		<?php
			$min_date      = absint( get_post_meta( $post_id, '_wc_booking_min_date', true ) );
			$min_date_unit = get_post_meta( $post_id, '_wc_booking_min_date_unit', true );
		?>
		<p class="form-field">
			<label for="_wc_accommodation_booking_min_date"><?php _e( 'Bookings can be made starting', 'woocommerce-accommodation-bookings' ); ?></label>
			<input type="number" name="_wc_accommodation_booking_min_date" id="_wc_accommodation_booking_min_date" value="<?php echo esc_attr( $min_date ); ?>" step="1" min="0" style="margin-right: 7px; width: 4em;">
			<select name="_wc_accommodation_booking_min_date_unit" id="_wc_accommodation_booking_min_date_unit" class="short" style="margin-right: 7px;">
				<option value="month" <?php selected( $min_date_unit, 'month' ); ?>><?php _e( 'Month(s)', 'woocommerce-accommodation-bookings' ); ?></option>
				<option value="week" <?php selected( $min_date_unit, 'week' ); ?>><?php _e( 'Week(s)', 'woocommerce-accommodation-bookings' ); ?></option>
				<option value="day" <?php selected( $min_date_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-accommodation-bookings' ); ?></option>
			</select> <?php _e( 'into the future', 'woocommerce-accommodation-bookings' ); ?>
		</p>
		<?php
			$max_date = get_post_meta( $post_id, '_wc_booking_max_date', true );
			if ( $max_date == '' ) {
				$max_date = 12;
			}
			$max_date      = max( absint( $max_date ), 1 );
			$max_date_unit = get_post_meta( $post_id, '_wc_booking_max_date_unit', true );
		?>
		<p class="form-field">
			<label for="_wc_accommodation_booking_max_date"><?php _e( 'Bookings can only be made', 'woocommerce-accommodation-bookings' ); ?></label>
			<input type="number" name="_wc_accommodation_booking_max_date" id="_wc_accommodation_booking_max_date" value="<?php echo esc_attr( $max_date ); ?>" step="1" min="1" style="margin-right: 7px; width: 4em;">
			<select name="_wc_accommodation_booking_max_date_unit" id="_wc_accommodation_booking_max_date_unit" class="short" style="margin-right: 7px;">
				<option value="month" <?php selected( $max_date_unit, 'month' ); ?>><?php _e( 'Month(s)', 'woocommerce-accommodation-bookings' ); ?></option>
				<option value="week" <?php selected( $max_date_unit, 'week' ); ?>><?php _e( 'Week(s)', 'woocommerce-accommodation-bookings' ); ?></option>
				<option value="day" <?php selected( $max_date_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-accommodation-bookings' ); ?></option>
				</select> <?php _e( 'into the future', 'woocommerce-accommodation-bookings' ); ?>
		</p>

	</div>
	<div class="options_group">
		<div class="table_grid">
			<table class="widefat">
				<thead>
					<tr>
						<th class="sort" width="1%">&nbsp;</th>
						<th><?php _e( 'Range type', 'woocommerce-accommodation-bookings' ); ?></th>
						<th><?php _e( 'From', 'woocommerce-accommodation-bookings' ); ?></th>
						<th><?php _e( 'To', 'woocommerce-accommodation-bookings' ); ?></th>
						<th><?php _e( 'Bookable', 'woocommerce-accommodation-bookings' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'If not bookable, users won\'t be able to choose this room.', 'woocommerce-accommodation-bookings' ); ?>">[?]</a></th>
						<th><?php _e( 'Priority', 'woocommerce-accommodation-bookings' ); ?>&nbsp;<a class="tips" data-tip="<?php esc_html_e( 'The lower the priority number, the earlier this rule gets applied. By default, global rules take priority over product rules which take priority over resource rules. By using priority numbers you can execute rules in different orders.', 'woocommerce-accommodation-bookings' ); ?>">[?]</a></th>
						<th class="remove" width="1%">&nbsp;</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th colspan="6">
							<a href="#" class="button button-primary add_row" data-row="<?php
								ob_start();
								include( 'html-accommodation-booking-availability-fields.php' );
								$html = ob_get_clean();
								echo esc_attr( $html );
							?>"><?php _e( 'Add Range', 'woocommerce-accommodation-bookings' ); ?></a>
							<span class="description"><?php _e( 'Rules with lower numbers will execute first. Rules further down this table with the same priority will also execute first.', 'woocommerce-accommodation-bookings' ); ?></span>
						</th>
					</tr>
				</tfoot>
				<tbody id="availability_rows">
					<?php
						$values = get_post_meta( $post_id, '_wc_booking_availability', true );
						if ( ! empty( $values ) && is_array( $values ) ) {
							foreach ( $values as $availability ) {
								include( 'html-accommodation-booking-availability-fields.php' );
							}
						}
					?>
				</tbody>
			</table>
		</div>
	</div>

</div>