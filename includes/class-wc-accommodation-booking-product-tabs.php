<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds some new tabs to the front end display of a accommodation product.
 * The new tab shows check-in and check-out times.
 * In the future, other accommodation specific tabs might show up here.
 */
class WC_Accommodation_Booking_Product_Tabs {

	/**
	 * Hook into WooCommerce..
	 */
	public function __construct() {
		add_action( 'woocommerce_product_tabs', array( $this, 'add_time_tab' ), 30 );
	}

	/**
	 * Let's us know if the admin actually filled out the check-in/check-out settings
	 * @return boolean
	 */
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

	/**
	 * Adds our time tab to the list of tabs, if the product is an accommodation product and
	 * the admin filled out the time setting fields.
	 *
	 * @param array $tabs List of WooCommerce product tabs
	 * @return array $tabs
	 */
	public function add_time_tab( $tabs = array() ) {
		global $post;

		if ( ! is_object( $post ) ) {
			return $tabs;
		}

		$product = wc_get_product( $post->ID );

		if ( 'accommodation-booking' !== $product->product_type ) {
			return $tabs;
		}

		if ( ! $this->are_time_fields_filled_out() ) {
			return $tabs;
		}

		$title = apply_filters( 'woocommerce_accommodation_booking_time_tab_title', esc_html__( 'Arriving/leaving', 'woocommerce-accommodation-bookings' ) );
		$tabs['accommodation_booking_time'] = array(
			'title'    => $title,
			'priority' => 10,
			'callback' => array( $this, 'add_time_tab_content' ),
		);

		return $tabs;
	}

	/**
	 * The actual content for our time tab.
	 */
	public function add_time_tab_content() {
		if ( ! $this->are_time_fields_filled_out() ) {
			return;
		}
		$check_in = get_option( 'woocommerce_accommodation_bookings_check_in', '' );
		$check_out = get_option( 'woocommerce_accommodation_bookings_check_out', '' );
		?>
		<h2><?php echo esc_html( apply_filters( 'woocommerce_accommodation_booking_time_tab_heading', __( 'Arriving/leaving', 'woocommerce-accommodation-bookings' ) ) ); ?></h2>
		<ul>
			<li><?php esc_html_e( 'Check-in time', 'woocommerce-accommodation-bookings' ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_in ) ) ); ?></li>
			<li><?php esc_html_e( 'Check-out time', 'woocommerce-accommodation-bookings' ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_out ) ) ); ?></li>
		</ul>
	<?php }

}

new WC_Accommodation_Booking_Product_Tabs;
