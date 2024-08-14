<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Sets up our "write" panels for accommodations products.
 */
class WC_Accommodation_Booking_Admin_Panels {

	/**
	 * Hook into WordPress and WooCommerce
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles_and_scripts' ) );

		add_filter( 'product_type_options', array( $this, 'product_type_options' ), 15 );

		add_filter( 'wc_bookings_product_duration_fallback', array( $this, 'get_product_duration' ), 10, 3 );

		add_action( 'woocommerce_product_data_panels', array( $this, 'panels' ) );

		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'general_product_data' ) );

		add_action( 'woocommerce_product_data_tabs', array( $this, 'add_tabs' ), 5 );
	}

	/**
	 * Filters product unit to display.
	 *
	 * @param string $duration_unit_default Default fallback duration
	 * @param string $duration_unit         Current duration unit
	 * @param int    $duration              Duration of booking
	 *
	 * @return string
	 */
	public function get_product_duration( $duration_unit_default, $duration_unit, $duration ) {
		if ( 'night' === $duration_unit ) {
			return _n( 'night', 'nights', $duration, 'woocommerce-accommodation-bookings' );
		}
		return $duration_unit_default;
	}

	/**
	 * Displays the main accommodation booking settings/data view
	 */
	public function general_product_data() {
		global $post;
		$post_id = $post->ID;
		include( 'views/html-accommodation-booking-data.php' );
	}

	/**
	 * Loads any CSS or JS necessary for the admin
	 */
	public function admin_styles_and_scripts() {

		$screen = get_current_screen();

		// only load it on products
		if ( 'product' === $screen->id ) {
			wp_enqueue_script( 'wc_accommodation_bookings_writepanel_js', WC_ACCOMMODATION_BOOKINGS_PLUGIN_URL . '/build/js/admin/writepanel.js', array( 'jquery' ), WC_ACCOMMODATION_BOOKINGS_VERSION, true );
		}
	}

	/**
	 * Loads our panels related to accommodation bookings
	 * @version  1.0.11
	 */
	public function panels() {
		global $post, $bookable_product;
		$post_id = $post->ID;

		/**
		 * Day restrictions added to Bookings 1.10.7
		 * @todo  Remove version compare ~Aug 2018
		 */
		if ( version_compare( WC_BOOKINGS_VERSION, '1.10.7', '>=' ) ) {

			if ( empty( $bookable_product ) || $bookable_product->get_id() !== $post->ID ) {
				$bookable_product = new WC_Product_Booking( $post->ID );
			}

			$restricted_meta = $bookable_product->get_restricted_days();

			for ( $i=0; $i < 7; $i++) {

				if ( $restricted_meta && in_array( $i, $restricted_meta ) ) {
					$restricted_days[ $i ] = $i;
				} else {
					$restricted_days[ $i ] = false;
				}
			}
		}

		include( 'views/html-accommodation-booking-rates.php' );
		include( 'views/html-accommodation-booking-availability.php' );
	}


	/**
	 * Hides the "virtal" option for accommodations
	 * @param  array $options
	 * @return array
	 */
	public function product_type_options( $options ) {
		$options['virtual']['wrapper_class'] .= ' show_if_accommodation-booking';
		$options['wc_booking_has_resources']['wrapper_class'] .= ' show_if_accommodation-booking';
		$options['wc_booking_has_persons']['wrapper_class'] .= ' show_if_accommodation-booking';
		return $options;
	}

	/**
	 * Add tab entries definition
	 *
	 * @param array $tabs List of tabs.
	 * @return array
	 */
	public function add_tabs( $tabs ) {
		$tabs['accommodation_bookings_pricing']      = array(
			'label'    => __( 'Rates', 'woocommerce-accommodation-bookings' ),
			'target'   => 'accommodation_bookings_rates',
			'class'    => array( 'show_if_accommodation-booking', 'accommodation_bookings_tab', 'bookings_pricing_tab', 'advanced_options' ),
			'priority' => 80,
		);
		$tabs['accommodation_bookings_availability'] = array(
			'label'    => __( 'Availability', 'woocommerce-accommodation-bookings' ),
			'target'   => 'accommodation_bookings_availability',
			'class'    => array( 'show_if_accommodation-booking', 'accommodation_bookings_tab', 'bookings_availability_tab', 'advanced_options' ),
			'priority' => 80,
		);

		return $tabs;
	}
}

new WC_Accommodation_Booking_Admin_Panels();
