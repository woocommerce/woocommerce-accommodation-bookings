<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Addons integration class.
 */
class WC_Accommodation_Booking_Addons {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_addons_show_grand_total', array( $this, 'addons_show_grand_total' ), 20, 2 );
		add_action( 'woocommerce_product_addons_panel_before_options', array( $this, 'addon_options' ), 20, 3 );
		add_filter( 'woocommerce_product_addons_adjust_price', array( $this, 'disable_product_add_on_price_adjustment' ), 20, 2 );
		add_filter( 'woocommerce_product_addon_cart_item_data', array( $this, 'addon_price' ), 25, 4 );
	}

	/**
	 * Show grand total or not?
	 * @param  bool $show_grand_total
	 * @param  object $product
	 * @return bool
	 */
	public function addons_show_grand_total( $show_grand_total, $product ) {
		if (
			$product->is_type( 'accommodation-booking' ) &&
			( defined( 'WC_PRODUCT_ADDONS_VERSION' ) && version_compare( WC_PRODUCT_ADDONS_VERSION, '3.0', '<' ) )
		) {
			$show_grand_total = false;
		}

		return $show_grand_total;
	}

	/**
	 * Show same options as bookings integration now.
	 * Only difference is the product type we are targeting.
	 */
	public function addon_options( $post, $addon, $loop ) {
		$css_classes = 'show_if_accommodation-booking';

		if ( is_object( $post ) ) {
			$product = wc_get_product( $post->ID );
			if ( 'accommodation-booking' !== $product->get_type() ) {
				$css_classes .= ' hide_initial_booking_addon_options';
			}
		} else {
			$css_classes .= ' hide_initial_booking_addon_options';
		}

		if ( defined( 'WC_PRODUCT_ADDONS_VERSION' ) && version_compare( WC_PRODUCT_ADDONS_VERSION, '3.0', '<' ) ) {
			?>
			<tr class="<?php echo esc_attr( $css_classes ); ?>">
				<td class="addon_wc_booking_person_qty_multiplier addon_required" width="50%">
					<label for="addon_wc_booking_accommodation_person_qty_multiplier_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Bookings: Multiply cost by person count', 'woocommerce-accommodation-bookings' ); ?></label>
					<input type="checkbox" id="addon_wc_booking_accommodation_person_qty_multiplier_<?php echo esc_attr( $loop ); ?>" name="addon_wc_booking_person_qty_multiplier[<?php echo esc_attr( $loop ); ?>]" <?php checked( ! empty( $addon['wc_booking_person_qty_multiplier'] ), true ); ?> />
				</td>
				<td class="addon_wc_booking_block_qty_multiplier addon_required" width="50%">
					<label for="addon_wc_booking_accommodation_block_qty_multiplier_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Bookings: Multiply cost by block count', 'woocommerce-accommodation-bookings' ); ?></label>
					<input type="checkbox" id="addon_wc_booking_accommodation_block_qty_multiplier_<?php echo esc_attr( $loop ); ?>" name="addon_wc_booking_block_qty_multiplier[<?php echo esc_attr( $loop ); ?>]" <?php checked( ! empty( $addon['wc_booking_block_qty_multiplier'] ) || ! empty( $addon['wc_accommodation_booking_block_qty_multiplier'] ), true ); ?> />
				</td>
			</tr>
			<?php
		} else {
			?>
			<div class="<?php echo esc_attr( $css_classes ); ?>">
				<div class="addon_wc_booking_person_qty_multiplier">
					<label for="addon_wc_booking_accommodation_person_qty_multiplier_<?php echo esc_attr( $loop ); ?>">
						<input type="checkbox" id="addon_wc_booking_accommodation_person_qty_multiplier_<?php echo esc_attr( $loop ); ?>" name="addon_wc_booking_person_qty_multiplier[<?php echo esc_attr( $loop ); ?>]" <?php checked( ! empty( $addon['wc_booking_person_qty_multiplier'] ), true ); ?> /> <?php esc_html_e( 'Bookings: Multiply cost by person count', 'woocommerce-accommodation-bookings' ); ?>
						<?php echo wc_help_tip( __( 'Only applies to options which use quantity based prices.', 'woocommerce-accommodation-bookings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</label>
				</div>

				<div class="addon_wc_booking_block_qty_multiplier">
					<label for="addon_wc_booking_accommodation_block_qty_multiplier_<?php echo esc_attr( $loop ); ?>">
						<input type="checkbox" id="addon_wc_booking_accommodation_block_qty_multiplier_<?php echo esc_attr( $loop ); ?>" name="addon_wc_booking_block_qty_multiplier[<?php echo esc_attr( $loop ); ?>]" <?php checked( ! empty( $addon['wc_booking_block_qty_multiplier'] ) || ! empty( $addon['wc_accommodation_booking_block_qty_multiplier'] ), true ); ?> /> <?php esc_html_e( 'Bookings: Multiply cost number of nights', 'woocommerce-accommodation-bookings' ); ?>
						<?php echo wc_help_tip( __( 'Only applies to options which use quantity based prices.', 'woocommerce-accommodation-bookings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</label>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Don't adjust cart item price for accommodation bookings since the booking form class adds the costs itself
	 *
	 * @param boolean $bool should the the addon price be added to the product price.
	 * @param array   $cart_item the corresponding cart item.
	 *
	 * @return bool
	 */
	public function disable_product_add_on_price_adjustment( $bool, $cart_item ) {
		if ( $cart_item['data']->is_type( 'accommodation-booking' ) ) {
			return false;
		}
		return $bool;
	}

	/**
	 * Change addon price based on settings
	 * @return float
	 */
	public function addon_price( $cart_item_data, $addon, $product_id, $post_data ) {

		// Option `wc_accommodation_booking_block_qty_multiplier` is deprecated and identical to booking's `wc_booking_block_qty_multiplier`.
		foreach ( $cart_item_data as $key => $data ) {
			if ( ! empty( $addon['wc_accommodation_booking_block_qty_multiplier'] ) ) {
				// Intentionally using a different key as these mean the same thing, and we will not need to duplicate code here.
				$cart_item_data[ $key ]['wc_booking_block_qty_multiplier'] = 1;
			}
		}

		return $cart_item_data;
	}
}

new WC_Accommodation_Booking_Addons();
