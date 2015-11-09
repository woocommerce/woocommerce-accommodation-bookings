<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Accommodation_Booking_Product_Tabs {

	public function __construct() {
		add_action( 'woocommerce_product_tabs', array( $this, 'add_time_tab' ), 30 );
	}

	public function are_time_fields_filled_out() {
		$check_in = get_option( 'woocommerce_accommodation_bookings_check_in', '' );
		$check_out = get_option( 'woocommerce_accommodation_bookings_check_in', '' );

		if ( empty( $check_in ) ) {
			return false;
		}

		if ( empty( $check_out ) ) {
			return false;
		}

		return true;
	}

	public function add_time_tab( $tabs = array() ) {
		global $post, $woocommerce;

		$product = get_product( $post->ID );

		if ( 'accommodation-booking' !== $product->product_type ) {
			return $tabs;
		}

		if ( ! $this->are_time_fields_filled_out() ) {
			return $tabs;
		}

		$title = apply_filters( 'woocommerce_accommodation_booking_time_tab_title', __( 'Arriving/leaving', 'wocommerce-accommodation-bookings' ) );
		$tabs['accommodation_booking_time'] = array(
			'title'    => $title,
			'priority' => 10,
			'callback' => array( $this, 'add_time_tab_content' )
		);

		return $tabs;
	}

	public function add_time_tab_content() {
		if ( ! $this->are_time_fields_filled_out() ) {
			return;
		}
		$check_in = get_option( 'woocommerce_accommodation_bookings_check_in', '' );
		$check_out = get_option( 'woocommerce_accommodation_bookings_check_out', '' );
		?>
		<h2><?php echo esc_html( apply_filters( 'woocommerce_accommodation_booking_time_tab_heading', __( 'Arriving/leaving', 'wocommerce-accommodation-bookings' ) ) ); ?></h2>
		<ul>
			<li>Check-in time: <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_in ) ) ); ?></li>
			<li>Check-out time: <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_out ) ) ); ?></li>
		</ul>
	<?php }

}

new WC_Accommodation_Booking_Product_Tabs;
