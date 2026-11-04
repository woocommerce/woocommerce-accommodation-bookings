<?php
/**
 * Tests for the accommodation rates fields admin view.
 *
 * @package woocommerce-accommodation-bookings
 */

use PHPUnit\Framework\TestCase;

/**
 * Legacy modifier rates must render without a fatal error.
 */
class TestAccommodationRatesFieldsView extends TestCase {

	/**
	 * Legacy modifier rows render the base cost adjusted by the stored cost, even when that cost is not numeric.
	 *
	 * @dataProvider legacy_modifier_rates
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @param string $modifier Stored modifier.
	 * @param mixed  $cost     Stored cost.
	 * @param string $expected Expected block cost field value.
	 */
	public function test_legacy_modifier_rate_renders_block_cost( $modifier, $cost, $expected ) {
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

		$rate    = array(
			'type'     => 'custom',
			'modifier' => $modifier,
			'cost'     => $cost,
		);
		$post_id = 1;

		ob_start();
		include __DIR__ . '/../../includes/admin/views/html-accommodation-booking-rates-fields.php';
		$html = ob_get_clean();

		$this->assertMatchesRegularExpression(
			'/name="wc_accommodation_booking_pricing_block_cost\[\]" value="\s*' . preg_quote( $expected, '/' ) . '"/',
			$html
		);
		WP_Mock::tearDown();
	}

	/**
	 * Stored modifier and cost pairs, including the non-numeric costs older rates can hold.
	 *
	 * @return array
	 */
	public function legacy_modifier_rates() {
		return array(
			'minus empty cost'   => array( 'minus', '', '100' ),
			'plus empty cost'    => array( 'plus', '', '100' ),
			'minus numeric cost' => array( 'minus', '20', '80' ),
			'plus numeric cost'  => array( 'plus', '20.5', '120.5' ),
		);
	}
}
