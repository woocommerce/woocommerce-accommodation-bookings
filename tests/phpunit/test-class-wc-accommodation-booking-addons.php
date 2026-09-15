<?php
/**
 * Add-on field rendering regressions.
 *
 * @package WooCommerce_Accommodation_Bookings
 */

/**
 * Verify the shared Bookings options are not duplicated on global add-ons.
 *
 * @version x.x.x
 */
class TestWCAccommodationBookingAddons extends \PHPUnit\Framework\TestCase {
	/** Set up WordPress function doubles. */
	public function setUp(): void {
		\WP_Mock::setUp();
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', __DIR__ . '/' );
		}
		\WP_Mock::userFunction( 'esc_attr', array( 'return_arg' => 0 ) );
		\WP_Mock::userFunction( '__', array( 'return_arg' => 0 ) );
		\WP_Mock::userFunction( 'esc_html_e', array( 'return' => null ) );
		\WP_Mock::userFunction( 'checked', array( 'return' => '' ) );
		\WP_Mock::userFunction( 'wc_help_tip', array( 'return' => '' ) );
		require_once __DIR__ . '/../../includes/integrations/class-wc-accommodation-booking-addons.php';
	}

	/** Clear function doubles. */
	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	/** Global add-ons already receive these fields from Bookings. */
	public function test_global_addons_do_not_render_duplicate_fields() {
		$integration = new WC_Accommodation_Booking_Addons();
		foreach ( array( null, false ) as $post ) {
			ob_start();
			$integration->addon_options( $post, array(), 0 );
			$this->assertSame( '', ob_get_clean() );
		}
	}

	/** Legacy global data must retain its checked state without a duplicate control. */
	public function test_legacy_global_multiplier_marks_the_shared_field_checked() {
		\WP_Mock::userFunction(
			'wp_json_encode',
			array(
				'args'   => array( 'addon_wc_booking_block_qty_multiplier_3' ),
				'return' => '"addon_wc_booking_block_qty_multiplier_3"',
			)
		);
		\WP_Mock::userFunction(
			'wp_add_inline_script',
			array(
				'times' => 1,
				'args'  => array( 'woocommerce_product_addons', 'jQuery( document.getElementById( "addon_wc_booking_block_qty_multiplier_3" ) ).prop( \'checked\', true );' ),
			)
		);
		$integration = new WC_Accommodation_Booking_Addons();
		ob_start();
		$integration->addon_options( null, array( 'wc_accommodation_booking_block_qty_multiplier' => 1 ), 3 );
		$this->assertSame( '', ob_get_clean() );
	}

	/** The accommodation product panel must retain both existing fields. */
	public function test_product_addons_keep_accommodation_fields() {
		$product = \Mockery::mock();
		$product->shouldReceive( 'get_type' )->andReturn( 'accommodation-booking' );
		\WP_Mock::userFunction(
			'wc_get_product',
			array(
				'args'   => array( 123 ),
				'return' => $product,
			)
		);
		$integration = new WC_Accommodation_Booking_Addons();
		ob_start();
		$integration->addon_options( (object) array( 'ID' => 123 ), array(), 2 );
		$html = ob_get_clean();
		$this->assertStringContainsString( 'name="addon_wc_booking_person_qty_multiplier[2]"', $html );
		$this->assertStringContainsString( 'name="addon_wc_booking_block_qty_multiplier[2]"', $html );
		$this->assertStringContainsString( 'show_if_accommodation-booking', $html );
	}
}
