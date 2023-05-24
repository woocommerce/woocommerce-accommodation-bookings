<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Settings screen under WooCommerce > Settings > Products > Accommodations
 */
class WC_Accommodation_Booking_Admin_Product_Settings extends WC_Settings_API {
	/**
	 * The single instance of the class.
	 *
	 * @var $_instance
	 * @since 1.13.0
	 */
	protected static $_instance = null;

	/**
	 * Name for nonce to update times settings.
	 *
	 * @since 1.13.0
	 * @var string self::NONCE_NAME
	 */
	const NONCE_NAME   = 'bookings_times_settings_nonce';

	/**
	 * Action name for nonce to update times settings.
	 *
	 * @since 1.13.0
	 * @var string self::NONCE_ACTION
	 */
	const NONCE_ACTION = 'submit_bookings_times_settings';

	/**
	 * Constructor.
	 *
	 * @since 1.13.0
	 */
	public function __construct() {
		$this->plugin_id = "woocommerce_accommodation_bookings_";
		$this->id = "times";

		$this->maybe_migrate();

		// Initialize settings and form data.
		$this->init_times_settings();

		add_action( 'admin_init', array( $this, 'maybe_save_settings' ) );
		add_filter( 'woocommerce_bookings_settings_page', array( $this, 'add_accommodation_settings' ) );
	}

	/**
	 * Maybe migrate data from old format to new one.
	 */
	public function maybe_migrate() {
		$check_in  = get_option( 'woocommerce_accommodation_bookings_check_in' );

		if ( $check_in ) {
			delete_option( 'woocommerce_accommodation_bookings_check_in' );
		}

		$check_out = get_option( 'woocommerce_accommodation_bookings_check_out' );

		if ( $check_out ) {
			delete_option( 'woocommerce_accommodation_bookings_check_out' );
		}

		if ( $check_in || $check_out ) {
			update_option( $this->plugin_id . $this->id . '_settings', array(
				'check_in' => $check_in,
				'check_out' => $check_out,
			) );
		}
	}

	/**
	 * Initialize settings by using Bookings filter.
	 *
	 * @param array $tabs_metadata Tabs metadata.
	 *
	 * @return array Modified metadata that includes Accommodation.
	 */
	public function add_accommodation_settings( $tabs_metadata ) {
		$tabs_metadata['accommodation'] = array(
			'name'          => __( 'Accommodation', 'woocommerce-bookings' ),
			'href'          => admin_url( 'edit.php?post_type=wc_booking&page=wc_bookings_settings&tab=accommodation' ),
			'capability'    => 'manage_options',
			'generate_html' => 'WC_Accommodation_Booking_Admin_Product_Settings::generate_form_html',
		);

		return $tabs_metadata;
	}

	/**
	 * Initialize settings and form data.
	 *
	 * @since 1.13.0
	 * @return void
	 */
	public function init_times_settings() {
		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
	}

	/**
	 * Update settings values from form.
	 *
	 * @since 1.13.0
	 * @return void
	 */
	public function maybe_save_settings() {
		if ( isset( $_POST['Submit'] )
			&& isset( $_POST[ self::NONCE_NAME ] )
			&& wp_verify_nonce( wc_clean( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
				$this->process_admin_options();

			echo '<div class="updated"><p>' . esc_html__( 'Settings saved', 'woocommerce-bookings' ) . '</p></div>';

			do_action( 'wc_bookings_times_settings_on_save', $this );
		}
	}

	/**
	 * Defines settings fields.
	 *
	 * @since 1.13.0
	 * @return void
	 */
	public function init_form_fields() {
		global $wp_locale;

		$this->form_fields = array(
			'check_in' => array(
				'title'   => __( 'Check-in time', 'woocommerce-accommodation-bookings' ),
				'desc'    => __( 'Check-in time for reservations.', 'woocommerce-accommodation-bookings' ),
				'default' => '14:00',
				'type'    => 'accommodation_time',
			),
			'check_out' => array(
				'title'   => __( 'Check-out time', 'woocommerce-accommodation-bookings' ),
				'desc'    => __( 'Check-out time for reservations.', 'woocommerce-accommodation-bookings' ),
				'default' => '12:00',
				'type'    => 'accommodation_time',
			),
		);
	}

	/**
	 * Returns true if settings exist in database.
	 *
	 * @since 1.13.0
	 * @return bool
	 */
	public static function exists_in_db() {
		$maybe_settings = get_option( self::instance()->get_option_key(), null );
		return is_array( $maybe_settings );
	}

	/**
	 * Generates full HTML form for the instance settings.
	 *
	 * @since 1.13.0
	 * @return void
	 */
	public static function generate_form_html() {
		?>
			<form method="post" action="" id="bookings_settings">
				<?php self::instance()->admin_options(); ?>
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'woocommerce-bookings' ); ?>" />
					<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
				</p>
			</form>
		<?php
	}

	/**
	 * Returns WC_Bookings_Timezone_Settings singleton
	 *
	 * Ensures only one instance of WC_Bookings_Timezone_Settings is created.
	 *
	 * @since 1.13.0
	 * @return WC_Bookings_Timezone_Settings - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Retrieves value for the provided option key.
	 *
	 * @since 1.13.0
	 * @param string $key Option key.
	 * @return mixed Option value.
	 */
	public static function get( $key ) {
		return self::instance()->get_option( $key );
	}

	/**
	 * Outputs a time selector input box
	 * @param  array $value The "setting" info from init_form_fields.
	 */
	public function generate_accommodation_time_html( $key, $value ) {
		$field_key    = $this->get_field_key( $key );
		$type         = $value['type'];
		$option_value = get_option( $this->plugin_id . $this->id . '_settings' );
		$option_value = isset( $option_value[ $key ] ) ? $option_value[ $key ] : '';
		ob_start();

		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ) ?>">
				<input
					name="<?php echo esc_attr( $field_key ); ?>"
					id="<?php echo esc_attr( $field_key ); ?>"
					type="time"
					value="<?php echo esc_attr( $option_value ); ?>"
					/> <?php echo esc_html( $value['desc'] ); ?>
			</td>
		</tr><?php

		return ob_get_clean();
	}
}

new WC_Accommodation_Booking_Admin_Product_Settings();
