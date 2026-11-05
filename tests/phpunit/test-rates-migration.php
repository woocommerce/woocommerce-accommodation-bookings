<?php
/**
 * Tests for the 1.1.3 rates migration in the installer.
 *
 * @package woocommerce-accommodation-bookings
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/class-wc-accommodation-bookings-plugin.php';

/**
 * Old override_block rates must convert without a fatal error.
 *
 * @version x.x.x
 */
class TestAccommodationRatesMigration extends TestCase {

	/**
	 * Pricing meta written by the migration, keyed by product ID.
	 *
	 * @var array
	 */
	private $saved = array();

	/**
	 * Set up WP_Mock and reset the saved meta.
	 */
	public function setUp(): void {
		parent::setUp();
		WP_Mock::setUp();
		$this->saved = array();
	}

	/**
	 * Tear down WP_Mock and the mocked $wpdb.
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	/**
	 * Run the installer below 1.1.3 against one product with the given pricing rows.
	 *
	 * @param array $pricing Stored `_wc_booking_pricing` rows.
	 * @return array Converted rows.
	 */
	private function migrate( array $pricing ) {
		$wpdb           = Mockery::mock( 'wpdb' );
		$wpdb->postmeta = 'wp_postmeta';
		$wpdb->shouldReceive( 'get_results' )->andReturn( array( (object) array( 'post_id' => 7 ) ) );
		$GLOBALS['wpdb'] = $wpdb;

		$product = Mockery::mock( 'WC_Product' );
		$product->shouldReceive( 'get_type' )->andReturn( 'accommodation-booking' );
		$product->shouldReceive( 'get_id' )->andReturn( 7 );

		WP_Mock::userFunction( 'get_option', array( 'return' => '1.1.2' ) );
		WP_Mock::userFunction(
			'absint',
			array(
				'return' => function ( $value ) {
					return abs( (int) $value );
				},
			)
		);
		WP_Mock::userFunction( 'wc_get_product', array( 'return' => $product ) );
		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return' => function ( $id, $key ) use ( $pricing ) {
					return '_wc_booking_pricing' === $key ? $pricing : '50';
				},
			)
		);
		WP_Mock::userFunction(
			'update_post_meta',
			array(
				'return' => function ( $id, $key, $value ) {
					$this->saved[ $id ] = $value;
				},
			)
		);
		WP_Mock::userFunction( 'update_option', array( 'times' => 1 ) );

		$plugin          = ( new ReflectionClass( 'WC_Accommodation_Bookings_Plugin' ) )->newInstanceWithoutConstructor();
		$plugin->version = '1.3.16';
		$plugin->install();

		return $this->saved[7];
	}

	/**
	 * Each old block cost becomes a modifier and cost relative to the base cost of 50.
	 *
	 * @dataProvider override_blocks
	 *
	 * @param mixed  $override_block Stored block cost.
	 * @param string $modifier       Expected modifier.
	 * @param int    $cost           Expected cost.
	 */
	public function test_override_block_converts( $override_block, $modifier, $cost ) {
		$rows = $this->migrate(
			array(
				array(
					'type'           => 'custom',
					'override_block' => $override_block,
				),
			)
		);

		$this->assertArrayNotHasKey( 'override_block', $rows[0] );
		$this->assertSame( $modifier, $rows[0]['modifier'] );
		$this->assertSame( $cost, $rows[0]['cost'] );
		$this->assertSame( $modifier, $rows[0]['base_modifier'] );
		$this->assertSame( 0, $rows[0]['base_cost'] );
	}

	/**
	 * Every old row of a product converts, and rows already in the new format are left alone.
	 */
	public function test_all_rows_convert() {
		$current = array(
			'modifier' => 'minus',
			'cost'     => 15,
		);
		$rows    = $this->migrate(
			array(
				array( 'override_block' => '' ),
				array( 'override_block' => '70' ),
				$current,
				'x',
			)
		);

		$this->assertSame( array( 'plus', 'plus', 'minus' ), array_column( $rows, 'modifier' ) );
		$this->assertSame( array( 0, 20, 15 ), array_column( $rows, 'cost' ) );
		$this->assertSame( $current, $rows[2] );
		$this->assertSame( 'x', $rows[3] );
	}

	/**
	 * Old block costs. Bookings skipped empty ones, so they become rules that change nothing; any other value replaced the block cost.
	 *
	 * @return array
	 */
	public function override_blocks() {
		return array(
			'higher'       => array( '80', 'plus', 30 ),
			'lower'        => array( '20', 'minus', 30 ),
			'equal'        => array( '50', 'minus', 0 ),
			'decimal'      => array( '80.5', 'plus', 30 ),
			'int'          => array( 80, 'plus', 30 ),
			'zero decimal' => array( '0.00', 'minus', 50 ),
			'blank'        => array( ' ', 'minus', 50 ),
			'null'         => array( null, 'plus', 0 ),
			'empty'        => array( '', 'plus', 0 ),
			'zero string'  => array( '0', 'plus', 0 ),
			'zero int'     => array( 0, 'plus', 0 ),
			'non-numeric'  => array( 'abc', 'minus', 50 ),
		);
	}
}
