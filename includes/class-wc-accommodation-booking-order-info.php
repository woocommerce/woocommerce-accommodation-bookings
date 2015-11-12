<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds some additional info (such as check-in/check-out time) to the order info line item
 */
class WC_Accommodation_Booking_Order_Info {

	/**
	 * Hook into WooCommerce..
	 */
	public function __construct() {
		add_action( 'woocommerce_order_item_meta_start', array( $this, 'add_checkinout_info_to_order_email' ), -10, 3 );
	}

	/**
	 * Adds check-in/check-out info to the WooCommerce order line item area
	 */
	public function add_checkinout_info_to_order_email( $item_id, $item, $order ) {
		$product = wc_get_product( $item['product_id'] );

		if ( 'accommodation-booking' !== $product->product_type ) {
			return;
		}

		$check_in = get_option( 'woocommerce_accommodation_bookings_check_in', '' );
		$check_out = get_option( 'woocommerce_accommodation_bookings_check_out', '' );
		?>
		<p>
		<strong><?php esc_html_e( 'Check-in', 'woocommerce-accommodation-bookings' ); ?> </strong>
		<?php echo esc_html( $item['Booking Date'] );
		if ( ! empty( $check_in ) ) {
			esc_html_e( ' at ', 'woocommerce-accommodation-bookings');
			echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_in ) ) );
		}
		?>

		<br />

		<strong><?php esc_html_e( 'Check-out', 'woocommerce-accommodation-bookings' ); ?> </strong>
		<?php
		$duration = intval( $item['Duration'] );
		$end_date   = strtotime( "+{$duration} day", strtotime( $item['Booking Date'] ) );
		echo esc_html( date_i18n( get_option( 'date_format'), $end_date ) );
		if ( ! empty( $check_out ) ) {
			esc_html_e( ' at ', 'woocommerce-accommodation-bookings');
			echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_out ) ) );
		}
		?>
		</p>
		<?php
	}

}

new WC_Accommodation_Booking_Order_Info;
