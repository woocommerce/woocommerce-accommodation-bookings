<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * We need to change a couple things about how the cart manager works:
 * - The add-to-cart action for accommodation bookings should just call the booking action
 * - We should display check-in/check-out times on the cart
 */
class WC_Accommodation_Booking_Cart_Manager {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_accommodation-booking_add_to_cart', array( $this, 'add_to_cart' ), 30 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 20, 2 );
	}

	/**
	 * Fire the woocommerce_booking_add_to_cart action
	 */
	function add_to_cart() {
		do_action( 'woocommerce_booking_add_to_cart' );
	}

	/**
	 * Display our check-in/check-out info
	 *
	 * @param mixed $other_data
	 * @param mixed $cart_item
	 * @return array
	 */
	public function get_item_data( $other_data, $cart_item ) {
		if ( 'accommodation-booking' === $cart_item['data']->product_type ) {
			$check_in = get_option( 'woocommerce_accommodation_bookings_check_in', '' );
			$check_out = get_option( 'woocommerce_accommodation_bookings_check_out', '' );
			$end_date  = date_i18n( get_option( 'date_format'), $cart_item['booking']['_end_date'] );

			if ( ! empty( $check_in ) ) {
				$other_data[] = array(
					'name'    => __( 'Check-in', 'woocommerce-accommodation-bookings' ),
					'value'   => esc_html( $cart_item['booking']['date'] . __( ' at ', 'woocommerce-accommodation-bookings' ) . date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_in ) ) ),
					'display' => ''
				);
			}

			if ( ! empty( $check_out ) ) {
				$other_data[] = array(
					'name'    => __( 'Check-out', 'woocommerce-accommodation-bookings' ),
					'value'   => esc_html( $end_date . __( ' at ', 'woocommerce-accommodation-bookings' ) . date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_out ) ) ),
					'display' => '',
				);
			}
		}

		return $other_data;
	}
}

new WC_Accommodation_Booking_Cart_Manager();
