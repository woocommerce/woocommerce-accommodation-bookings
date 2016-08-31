<div id="accommodation_bookings_rates" class="panel woocommerce_options_panel bookings_extension">
	<div class="options_group">
		<?php woocommerce_wp_text_input( array( 'id' => '_wc_accommodation_booking_base_cost', 'label' => __( 'Standard room rate', 'woocommerce-accommodation-bookings' ), 'description' => __( 'Standard cost for booking the room.', 'woocommerce-accommodation-bookings' ), 'value' => get_post_meta( $post_id, '_wc_booking_base_cost', true ), 'type' => 'number', 'desc_tip' => true, 'custom_attributes' => array(
			'min'   => '',
			'step' 	=> '0.01'
		) ) ); ?>
		<?php do_action( 'woocommerce_accommodation_bookings_after_booking_base_cost', $post_id ); ?>

		<?php woocommerce_wp_text_input( array( 'id' => '_wc_accommodation_booking_display_cost', 'label' => __( 'Display cost', 'woocommerce-accommodation-bookings' ), 'description' => __( 'The cost is displayed to the user on the frontend. Leave blank to have it calculated for you. If a booking has varying costs, this will be prefixed with the word "from:".', 'woocommerce-accommodation-bookings' ), 'value' => get_post_meta( $post_id, '_wc_display_cost', true ), 'type' => 'number', 'desc_tip' => true, 'custom_attributes' => array(
			'min'   => '',
			'step' 	=> '0.01'
		) ) ); ?>

        <?php do_action( 'woocommerce_accommodation_bookings_after_display_cost', $post_id ); ?>
	</div>
	<div class="options_group">
		<div class="table_grid">
			<table class="widefat">
				<thead>
					<tr>
						<th class="sort" width="1%">&nbsp;</th>
						<th><?php _e( 'Range type', 'woocommerce-accommodation-bookings' ); ?></th>
						<th><?php _e( 'Starting', 'woocommerce-accommodation-bookings' ); ?></th>
						<th><?php _e( 'Ending', 'woocommerce-accommodation-bookings' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Includes this date/night.', 'woocommerce-accommodation-bookings' ); ?>">[?]</a></th>
						<th><?php _e( 'Cost', 'woocommerce-accommodation-bookings' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Cost for this time period.', 'woocommerce-accommodation-bookings' ); ?>" colspan='2'>[?]</a></th>
						<th class="remove" width="1%">&nbsp;</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th colspan="6">
							<a href="#" class="button button-primary add_row" data-row="<?php
								ob_start();
								include( 'html-accommodation-booking-rates-fields.php' );
								$html = ob_get_clean();
								echo esc_attr( $html );
							?>"><?php _e( 'Add Range', 'woocommerce-accommodation-bookings' ); ?></a>
						</th>
					</tr>
				</tfoot>
				<tbody id="rates_rows">
					<?php
						$values = get_post_meta( $post_id, '_wc_booking_pricing', true );
						if ( ! empty( $values ) && is_array( $values ) ) {
							foreach ( $values as $rate ) {
								include( 'html-accommodation-booking-rates-fields.php' );
							}
						}
					?>
				</tbody>
			</table>
		</div>
		<?php do_action( 'woocommerce_accommodation_bookings_after_bookings_pricing', $post_id ); ?>
	</div>
</div>