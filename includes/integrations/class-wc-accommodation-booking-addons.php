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
		add_filter( 'woocommerce_product_addons_save_data', array( $this, 'save_addon_options' ), 25, 2 );
		add_filter( 'woocommerce_product_addons_adjust_price', array( $this, 'disable_product_add_on_price_adjustment' ), 20, 2 );
	}

	/**
	 * Show grand total or not?
	 * @param  bool $show_grand_total
	 * @param  object $product
	 * @return bool
	 */
	public function addons_show_grand_total( $show_grand_total, $product ) {
		if ( $product->is_type( 'accommodation-booking' ) ) {
			$show_grand_total = false;
		}
		return $show_grand_total;
	}

	/**
	 * Show options
	 */
	public function addon_options( $post, $addon, $loop ) {
		$css_classes = '';
		
		if ( is_object( $post ) ) {
			$product = wc_get_product( $post->ID );
			$css_classes .= 'show_if_accommodation-booking';
			if ( 'accommodation-booking' !== $product->product_type ) {
				$css_classes .= ' hide_initial_booking_addon_options';
			}
		}
		?>
		<tr class="<?php echo esc_attr( $css_classes ); ?>">
			<td class="addon_wc_booking_person_qty_multiplier addon_required" width="50%">
				<label for="addon_wc_accommodation_booking_person_qty_multiplier_<?php echo $loop; ?>"><?php _e( 'Bookings: Multiply cost by person count', 'woocommerce-accommodation-bookings' ); ?></label>
				<input type="checkbox" id="addon_wc_accommodation_booking_person_qty_multiplier_<?php echo $loop; ?>" name="addon_wc_accommodation_booking_person_qty_multiplier[<?php echo $loop; ?>]" <?php checked( ! empty( $addon['wc_booking_person_qty_multiplier'] ), true ) ?> />
			</td>
			<td class="addon_wc_booking_block_qty_multiplier addon_required" width="50%">
				<label for="addon_wc_accommodation_booking_block_qty_multiplier_<?php echo $loop; ?>"><?php _e( 'Bookings: Multiply cost by number of nights', 'woocommerce-accommodation-bookings' ); ?></label>
				<input type="checkbox" id="addon_wc_accommodation_booking_block_qty_multiplier_<?php echo $loop; ?>" name="addon_wc_accommodation_booking_block_qty_multiplier[<?php echo $loop; ?>]" <?php checked( ! empty( $addon['wc_booking_block_qty_multiplier'] ), true ) ?> />
			</td>
		</tr>
		<?php
	}

	/**
	 * Save options
	 */
	public function save_addon_options( $data, $i ) {	
	
		if ( 'accommodation-booking' == $_POST['product-type'] ) {
			$data['wc_booking_person_qty_multiplier'] = isset( $_POST['addon_wc_accommodation_booking_person_qty_multiplier'][ $i ] ) ? 1 : 0;
			$data['wc_booking_block_qty_multiplier']  = isset( $_POST['addon_wc_accommodation_booking_block_qty_multiplier'][ $i ] ) ? 1 : 0;
		}
		
		return $data;
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
}

new WC_Accommodation_Booking_Addons();
