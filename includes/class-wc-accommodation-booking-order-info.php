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

		if ( 'accommodation-booking' !== $product->get_type() ) {
			return;
		}

		$booking_date_key = __( 'Booking Date', 'woocommerce-accommodation-bookings' );
		$booking_date     = ! empty( $item[ $booking_date_key ] ) ? $item[ $booking_date_key ] : null;

		if ( ! $booking_date ) {
			return;
		}

		$check_in  = get_option( 'woocommerce_accommodation_bookings_check_in', '' );
		$check_out = get_option( 'woocommerce_accommodation_bookings_check_out', '' );
		?>
		<p>
		<strong><?php esc_html_e( 'Check-in', 'woocommerce-accommodation-bookings' ); ?> </strong>
		<?php echo esc_html( $booking_date );
		if ( ! empty( $check_in ) ) {
			esc_html_e( ' at ', 'woocommerce-accommodation-bookings');
			echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_in ) ) );
		}

		$duration_key = __( 'Duration', 'woocommerce-accommodation-bookings' );
		$duration     = intval( $item[ $duration_key ] );
		$end_date_ts  = $this->get_end_date_timestamp( $booking_date, $duration );
		if ( ! $end_date_ts ) {
			return;
		}
		?>

		<br />

		<strong><?php esc_html_e( 'Check-out', 'woocommerce-accommodation-bookings' ); ?> </strong>
		<?php
		$end_date = date_i18n( get_option( 'date_format'), $end_date_ts );
		echo esc_html( $end_date );
		if ( ! empty( $check_out ) ) {
			esc_html_e( ' at ', 'woocommerce-accommodation-bookings');
			echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_out ) ) );
		}
		?>
		</p>
		<?php
	}

	/**
	 * Get timestamp of checkout time based on given `$start_date` and `$duration`.
	 *
	 * @param  string $start_date
	 * @param  int $duration
	 * @return int | boolean
	 */
	private function get_end_date_timestamp( $start_date, $duration ) {
		$datetime = DateTime::createFromFormat( get_option( 'date_format' ) . ' H:i:s', $start_date . ' 00:00:00' );
		if ( ! $datetime ) {
			return false;
		}
		$datetime->add( new DateInterval( 'P' . $duration . 'D' ) );

		return $datetime->getTimestamp();
	}

}

new WC_Accommodation_Booking_Order_Info;
