<?php
/**
 * Tests for the accommodation rates fields admin view.
 *
 * @package woocommerce-accommodation-bookings
 */

use PHPUnit\Framework\TestCase;

/**
 * Pricing rules with a modifier must render without a fatal error.
 *
 * @version x.x.x
 */
class TestAccommodationRatesFieldsView extends TestCase {

	/**
	 * Set up WP_Mock and the WordPress functions the view calls.
	 */
	public function setUp(): void {
		parent::setUp();
		WP_Mock::setUp();
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', __DIR__ . '/' );
		}
		WP_Mock::passthruFunction( '__' );
		WP_Mock::passthruFunction( 'esc_attr' );
		WP_Mock::passthruFunction( 'esc_html' );
		WP_Mock::userFunction( 'esc_html_e', array( 'return' => null ) );
		WP_Mock::userFunction( 'selected', array( 'return' => '' ) );
		WP_Mock::userFunction( 'get_post_meta', array( 'return' => '100' ) );
	}

	/**
	 * Tear down WP_Mock.
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * Modifier rows render the base cost adjusted by the stored cost, even when that cost is not numeric.
	 *
	 * @dataProvider modifier_rates
	 *
	 * @param string $modifier Stored modifier.
	 * @param mixed  $cost     Stored cost.
	 * @param string $expected Expected block cost field value.
	 */
	public function test_modifier_rate_renders_block_cost( $modifier, $cost, $expected ) {
		$rate    = array(
			'type'     => 'custom',
			'modifier' => $modifier,
			'cost'     => $cost,
		);
		$post_id = 1;

		ob_start();
		include __DIR__ . '/../../includes/admin/views/html-accommodation-booking-rates-fields.php';
		$html = ob_get_clean();

		$this->assertStringContainsString(
			'name="wc_accommodation_booking_pricing_block_cost[]" value="' . $expected . '"',
			$html
		);
	}

	/**
	 * Stored modifier and cost pairs, including the non-numeric costs a blank block cost leaves behind.
	 *
	 * @return array
	 */
	public function modifier_rates() {
		return array(
			'minus empty cost'         => array( 'minus', '', '100' ),
			'plus empty cost'          => array( 'plus', '', '100' ),
			'minus blank cost'         => array( 'minus', ' ', '100' ),
			'minus non-numeric cost'   => array( 'minus', 'abc', '100' ),
			'minus comma decimal cost' => array( 'minus', '10,5', '90' ),
			'minus numeric cost'       => array( 'minus', '20', '80' ),
			'plus numeric cost'        => array( 'plus', '20.5', '120.5' ),
			'minus null cost'          => array( 'minus', null, '' ),
		);
	}
}
