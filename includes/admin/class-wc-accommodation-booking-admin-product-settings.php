<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Settings screen under WooCommerce > Settings > Products > Accommodations
 */
class WC_Accommodation_Booking_Admin_Product_Settings {

	public $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init_form_fields();

		add_action( 'woocommerce_get_sections_products', array( $this, 'add_settings_tab' ) );
		add_action( 'woocommerce_get_settings_products', array( $this, 'add_settings_section' ), 10, 2 );
		add_action( 'woocommerce_admin_field_accommodation_time', array( $this, 'time_input' ) );
	}

	/**
	 * Adds our global / product settings to the WooCommerce settings admin.
	 */
	public function init_form_fields() {
		$this->settings = apply_filters( 'woocommerce_accommodation_bookings_settings_fields', array(
			array(
				'name' => __( 'Accommodation Settings', 'woocommerce-accommodation-bookings' ),
				'type' => 'title',
				'id' => 'accommodations',
			),

			array(
				'name' 		=> __( 'Check-in time', 'woocommerce-accommodation-bookings' ),
				'desc' 		=> __( 'Check-in time for reservations.', 'woocommerce-accommodation-bookings' ),
				'id' 		=> 'woocommerce_accommodation_bookings_check_in',
				'type' 		=> 'accommodation_time',
				'class'		=> 'time-picker',
			),

			array(
				'name' 		=> __( 'Check-out time', 'woocommerce-accommodation-bookings' ),
				'desc' 		=> __( 'Check-out time for reservations.', 'woocommerce-accommodation-bookings' ),
				'id' 		=> 'woocommerce_accommodation_bookings_check_out',
				'type' 		=> 'accommodation_time',
				'class'		=> 'time-picker',
			),

			array( 'type' => 'sectionend', 'id' => 'accommodations' ),
		) );
	}

	/**
	 * Adds a new settings tab to the WooCommerce product settings tab
	 * @param array $sections Product sections/tabs
	 */
	public function add_settings_tab( $sections ) {
		$sections = array_merge( $sections, array(
			'accommodation_booking' => esc_html__( 'Accommodations', 'woocommerce-accommodation-bookings' )
		) );
		return $sections;
	}

	/**
	 * Let's WooCommerce actually know about our settings when we are on the correct
	 * accommodation settings page.
	 * @param array $settings
	 * @param string $current_section
	 */
	public function add_settings_section ( $settings, $current_section ) {
		if ( 'accommodation_booking' === $current_section ) {
			$settings = $this->settings;
		}
		return $settings;
	}

	/**
	 * Outputs a time selector input box
	 * @param  array $value The "setting" info from init_form_fields.
	 */
	public function time_input( $value ) {
		$type         = $value['type'];
		$option_value = get_option( $value['id'], $value['default'] );
		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="time"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo esc_attr( $option_value ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					/> <?php echo esc_html( $value['desc'] ); ?>
			</td>
		</tr><?php
	}
}

new WC_Accommodation_Booking_Admin_Product_Settings();
