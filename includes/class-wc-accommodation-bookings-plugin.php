<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Accommodation Bookings class
 */
class WC_Accommodation_Bookings_Plugin {

	/**
	 * Main plugin's file.
	 *
	 * @var string
	 */
	public $plugin_file;

	/**
	 * Plugin's version.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Deactivate notice message.
	 *
	 * @var string
	 */
	public $deactivate_notice_message;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file Path to main plugin's file
	 * @param string $version     Plugin's version
	 */
	public function __construct( $plugin_file, $version ) {
		$this->plugin_file = $plugin_file;
		$this->version     = $version;
	}

	/**
	 * Run the plugin.
	 */
	public function run() {
		$this->_define_constants();
		$this->_register_hooks();
	}

	/**
	 * Define plugin's constants.
	 *
	 * @return void
	 */
	private function _define_constants() {
		define( 'WC_ACCOMMODATION_BOOKINGS_VERSION', $this->version );
		define( 'WC_ACCOMMODATION_BOOKINGS_INCLUDES_PATH', untrailingslashit( plugin_dir_path( $this->plugin_file ) ) . '/includes/' );
		define( 'WC_ACCOMMODATION_BOOKINGS_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( $this->plugin_file ) ) . '/templates/' );
		define( 'WC_ACCOMMODATION_BOOKINGS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( $this->plugin_file ) ), basename( $this->plugin_file ) ) ) );
		define( 'WC_ACCOMMODATION_BOOKINGS_MAIN_FILE', $this->plugin_file );
	}

	/**
	 * Register to hooks.
	 *
	 * @return void
	 */
	private function _register_hooks() {
		register_activation_hook( $this->plugin_file, array( $this, 'activate' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_deactivate' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'woocommerce_loaded', array( $this, 'includes' ), 20 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'booking_form_styles' ) );

		if ( is_admin() ) {
			$this->admin_includes();
		}
	}

	/**
	 * Action to perform when plugin is activated.
	 *
	 * Callback for `register_activation_hook`.
	 *
	 * @return void
	 */
	public function activate() {
		$result = $this->check_dependencies();
		if ( is_wp_error( $result ) ) {
			trigger_error( $result->get_error_message() );
			die;
		}
	}

	/**
	 * Check dependencies.
	 *
	 * @return bool|WP_Error Return true if dependencies are satisfied, otherwise WP_Error
	 */
	public function check_dependencies() {
		require_once( WC_ACCOMMODATION_BOOKINGS_INCLUDES_PATH . 'class-wc-accommodation-dependencies.php' );
		try {
			WC_Accommodation_Dependencies::check_dependencies();
		} catch ( Exception $e ) {
			return new WP_Error( 'unsatisfied_dependencies', $e->getMessage() );
		}

		return true;
	}

	/**
	 * Maybe deactivate plugin if dependencies are not satisfied.
	 *
	 * Hooked to `plugins_loaded`
	 *
	 * @return void
	 */
	public function maybe_deactivate() {
		$result = $this->check_dependencies();
		if ( is_wp_error( $result ) ) {
			deactivate_plugins( plugin_basename( $this->plugin_file ) );

			$this->deactivate_notice_message = $result->get_error_message();
			add_action( 'admin_notices', array( $this, 'deactivate_notice' ) );
		}
	}

	/**
	 * Admin notice when plugin is automatically deactivated.
	 *
	 * @return void
	 */
	public function deactivate_notice() {
		echo wp_kses_post( sprintf( '<div class="error">%s %s</div>', wpautop( esc_html( $this->deactivate_notice_message ) ), wpautop( 'Plugin <strong>deactivated</strong>.' ) ) );
	}

	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-accommodation-bookings' );
		$dir    = trailingslashit( WP_LANG_DIR );

		load_textdomain( 'woocommerce-accommodation-bookings', $dir . 'woocommerce-accommodation-bookings/woocommerce-accommodation-bookings-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-accommodation-bookings', false, dirname( plugin_basename( $this->plugin_file ) ) . '/languages/' );
	}

	/**
	 * Load Classes
	 */
	public function includes() {
		include( WC_ACCOMMODATION_BOOKINGS_INCLUDES_PATH . 'class-wc-product-accommodation-booking.php' );
		include( WC_ACCOMMODATION_BOOKINGS_INCLUDES_PATH . 'class-wc-accommodation-booking.php' );
		include( WC_ACCOMMODATION_BOOKINGS_INCLUDES_PATH . 'class-wc-accommodation-booking-cart-manager.php' );
		include( WC_ACCOMMODATION_BOOKINGS_INCLUDES_PATH . 'class-wc-accommodation-booking-date-picker.php' );
		include( WC_ACCOMMODATION_BOOKINGS_INCLUDES_PATH . 'class-wc-accommodation-booking-product-tabs.php' );
		include( WC_ACCOMMODATION_BOOKINGS_INCLUDES_PATH . 'class-wc-accommodation-booking-order-info.php' );
	}

	/**
	 * Include admin
	 */
	public function admin_includes() {
		include( WC_ACCOMMODATION_BOOKINGS_INCLUDES_PATH . 'admin/class-wc-accommodation-booking-admin-panels.php' );
		include( WC_ACCOMMODATION_BOOKINGS_INCLUDES_PATH . 'admin/class-wc-accommodation-booking-admin-product-settings.php' );
	}

	/**
	 * Frontend booking form scripts
	 */
	public function booking_form_styles() {
		wp_enqueue_style( 'wc-accommodation-bookings-styles', WC_ACCOMMODATION_BOOKINGS_PLUGIN_URL . '/assets/css/frontend.css', null, WC_ACCOMMODATION_BOOKINGS_VERSION );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @access	public
	 * @param	mixed $links Plugin Row Meta
	 * @param	mixed $file  Plugin Base file
	 * @return	array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file == plugin_basename( WC_ACCOMMODATION_BOOKINGS_MAIN_FILE ) ) {
			$row_meta = array(
				'docs'		=>	'<a href="' . esc_url( apply_filters( 'woocommerce_accommodation_bookings_docs_url', 'https://docs.woothemes.com/document/woocommerce-accommodation-bookings/' ) ) . '" title="' . esc_attr( __( 'View Documentation', 'woocommerce-accommodation-bookings' ) ) . '">' . __( 'Docs', 'woocommerce-accommodation-bookings' ) . '</a>',
				'support'	=>	'<a href="' . esc_url( apply_filters( 'woocommerce_accommodation_bookings_support_url', 'http://support.woothemes.com/' ) ) . '" title="' . esc_attr( __( 'Visit Premium Customer Support Forum', 'woocommerce-accommodation-bookings' ) ) . '">' . __( 'Premium Support', 'woocommerce-accommodation-bookings' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}
}
