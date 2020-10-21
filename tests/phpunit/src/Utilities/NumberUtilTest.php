<?php

namespace Automattic\WooCommerceAccommodationBookings\Tests\Utilities;

use Automattic\WooCommerceAccommodationBookings\Utilities\NumberUtil;
use PHPUnit\Framework\TestCase;

/**
 * A collection of tests for the string utility class.
 */
class NumberUtilTest extends TestCase {

	/**
	 * @testdox `abs` should work as the built-in function of the same name when passing a number.
	 */
	public function test_abs_when_passing_a_number() {
		$actual   = NumberUtil::abs( -1234.5 );
		$expected = 1234.5;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `abs` should work as the built-in function of the same name when passing a number-like string.
	 */
	public function test_abs_when_passing_a_string() {
		$actual   = NumberUtil::abs( '-1234.5' );
		$expected = 1234.5;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `abs` should work as the built-in function of the same name when passing a number-like string with spaces.
	 */
	public function test_abs_when_passing_a_string_with_spaces() {
		$actual   = NumberUtil::abs( '  -1234.5  ' );
		$expected = 1234.5;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Data provider for the `abs` tests for non-numeric values.
	 *
	 * @return array Values to test.
	 */
	public function data_provider_for_test_abs_when_passing_a_non_number_like_string() {
		return array(
			array( null ),
			array( '' ),
			array( 'foobar' ),
			array( array() ),
			array( false ),
		);
	}

	/**
	 * @testdox `abs` should return 0 when passing a non-numeric value except 'true'.
	 *
	 * @dataProvider data_provider_for_test_abs_when_passing_a_non_number_like_string
	 *
	 * @param mixed $value Value to test.
	 */
	public function test_abs_when_passing_a_non_number_like_string( $value ) {
		$actual   = NumberUtil::abs( $value );
		$expected = 0;
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `abs` should return 1 when passing the boolean 'true'.
	 */
	public function test_abs_when_passing_the_boolean_true() {
		$actual   = NumberUtil::abs( true );
		$expected = 1;
		$this->assertEquals( $expected, $actual );
	}
}