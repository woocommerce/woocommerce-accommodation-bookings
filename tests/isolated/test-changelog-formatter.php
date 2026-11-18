<?php
// phpcs:ignoreFile -- Unit test files use the test-*.php convention, which conflicts with the class-file-name sniff.
/**
 * Changelog formatter tests.
 *
 * @package WooCommerce Accommodation Bookings
 */

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\ExtensionTools\Changelogger\Changelog_Formatter;

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

/**
 * Tests the Jetpack Changelogger formatter that reads and writes changelog.txt.
 *
 * The formatter rewrites the whole file on every release, so the tests that matter
 * are the ones asserting that nothing but the new entry changes.
 *
 * @version x.x.x
 */
class SomewhereWarm_Changelog_Formatter_Tests extends TestCase {

	/**
	 * The formatter under test.
	 *
	 * @var Changelog_Formatter
	 */
	private $formatter;

	/**
	 * Set up the formatter with the same title the plugin configures.
	 */
	public function setUp(): void {
		parent::setUp();
		$settings        = $this->formatter_settings();
		$class           = $settings['class'];
		$this->formatter = new $class( $settings );
	}

	/**
	 * Formatter settings under `extra.changelogger` in composer.json.
	 *
	 * @return array
	 */
	private function formatter_settings() {
		$composer = json_decode( file_get_contents( dirname( __DIR__, 2 ) . '/composer.json' ), true );

		return $composer['extra']['changelogger']['formatter'];
	}

	/**
	 * Path to the real changelog.
	 *
	 * @return string
	 */
	private function changelog_path() {
		return dirname( __DIR__, 2 ) . '/changelog.txt';
	}

	/**
	 * Parse then format, for round-trip assertions.
	 *
	 * @param string $changelog Changelog contents.
	 * @return string
	 */
	private function round_trip( $changelog ) {
		return $this->formatter->format( $this->formatter->parse( $changelog ) );
	}

	/**
	 * The shipped changelog must survive a parse/format cycle byte for byte.
	 *
	 * Everything else here is a special case of this: if it fails, the next release
	 * rewrites years of history in the release pull request.
	 */
	public function test_real_changelog_round_trips_unchanged() {
		$changelog = file_get_contents( $this->changelog_path() );

		$this->assertNotEmpty( $changelog );
		$this->assertSame( $changelog, $this->round_trip( $changelog ) );
	}

	/**
	 * The shipped changelog must parse into entries, not silently into one blob.
	 */
	public function test_real_changelog_parses_into_entries() {
		$contents  = file_get_contents( $this->changelog_path() );
		$changelog = $this->formatter->parse( $contents );

		$this->assertSame( $this->formatter_settings()['title'], $changelog->getPrologue() );
		$this->assertNotEmpty( $changelog->getEntries() );
		$this->assertCount( preg_match_all( '/^= /m', $contents ), $changelog->getEntries() );

		$latest = $changelog->getLatestEntry();

		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', $latest->getVersion() );
		$this->assertNotEmpty( $latest->getChanges() );
	}
}
