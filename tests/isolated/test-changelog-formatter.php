<?php
// phpcs:ignoreFile -- Unit test files use the test-*.php convention, which conflicts with the class-file-name sniff.
/**
 * Changelog formatter tests.
 *
 * @package WooCommerce Accommodation Bookings
 */

use Automattic\Jetpack\Changelog\Changelog;
use PHPUnit\Framework\TestCase;
use SomewhereWarm\Changelogger\Changelog_Formatter;

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

/**
 * Tests the Jetpack Changelogger formatter that reads and writes changelog.txt.
 *
 * The formatter rewrites the whole file on every release, so the tests that matter
 * are the ones asserting that nothing but the new entry changes.
 *
 * @version x.x.x
 */
class WC_Accommodation_Bookings_Changelog_Formatter_Tests extends TestCase {

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
		$this->formatter = new Changelog_Formatter( array( 'title' => '*** Changelog ***' ) );
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
		$changelog = $this->formatter->parse( file_get_contents( $this->changelog_path() ) );

		$this->assertSame( '*** Changelog ***', $changelog->getPrologue() );
		$this->assertGreaterThan( 70, count( $changelog->getEntries() ) );

		$latest = $changelog->getLatestEntry();

		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', $latest->getVersion() );
		$this->assertNotEmpty( $latest->getChanges() );
	}

	/**
	 * A heading carries the release date and the version; a bullet carries its type.
	 */
	public function test_parses_headings_and_typed_entries() {
		$changelog = $this->formatter->parse(
			"*** Changelog ***\n\n"
			. "= 1.3.12 - 2026-08-26 =\n"
			. "* Fix - Fixed a thing.\n"
			. "* Tweak - Tweaked a thing.\n"
		);

		$entries = $changelog->getEntries();

		$this->assertCount( 1, $entries );
		$this->assertSame( '1.3.12', $entries[0]->getVersion() );
		$this->assertSame( '2026-08-26', $entries[0]->getTimestamp()->format( 'Y-m-d' ) );

		$changes = $entries[0]->getChanges();

		$this->assertCount( 2, $changes );
		$this->assertSame( 'Fix', $changes[0]->getSubheading() );
		$this->assertSame( 'Fixed a thing.', $changes[0]->getContent() );
		$this->assertSame( 'Tweak', $changes[1]->getSubheading() );
	}

	/**
	 * Only the first ' - ' separates the type from the entry.
	 */
	public function test_entry_content_may_contain_the_separator() {
		$changelog = $this->formatter->parse(
			"*** Title ***\n\n= 1.3.12 - 2026-08-26 =\n* Fix - Booked nights - and their rates - are restored.\n"
		);

		$change = $changelog->getEntries()[0]->getChanges()[0];

		$this->assertSame( 'Fix', $change->getSubheading() );
		$this->assertSame( 'Booked nights - and their rates - are restored.', $change->getContent() );
	}

	/**
	 * Legacy shapes the file still carries must round-trip untouched: bullets with no
	 * type at all, and types this plugin no longer issues.
	 *
	 * @dataProvider provide_legacy_shapes
	 *
	 * @param string $changelog Changelog contents.
	 */
	public function test_legacy_shapes_round_trip_unchanged( $changelog ) {
		$this->assertSame( $changelog, $this->round_trip( $changelog ) );
	}

	/**
	 * Legacy changelog shapes.
	 *
	 * @return array
	 */
	public function provide_legacy_shapes() {
		return array(
			'bullet with no type'    => array( "*** Title ***\n\n= 1.0 - 2016-01-04 =\n* Initial version.\n" ),
			'retired type Add'       => array( "*** Title ***\n\n= 1.1.15 - 2020-03-06 =\n* Add - Add basic unit tests suite.\n" ),
			'retired type Update'    => array( "*** Title ***\n\n= 1.3.11 - 2026-07-29 =\n* Update - Calendar display option text to clarify user interaction.\n" ),
			'retired type Feature'   => array( "*** Title ***\n\n= 1.0.4 - 2016-05-26 =\n* Feature - Add support for Persons\n" ),
			'retired type Fixed'     => array( "*** Title ***\n\n= 1.1.34 - 2022-11-01 =\n* Fixed - PHP 8.0/8.1 Compatibility issue fixed: Critical error when cost in range is empty if Standard room rate is empty as well.\n" ),
			'retired type Remove'    => array( "*** Title ***\n\n= 1.1.6 - 2019-04-17 =\n* Remove - partially booked days styling.\n" ),
			'several entries'        => array( "*** Title ***\n\n= 1.3.12 - 2026-08-26 =\n* Fix - Second.\n\n= 1.3.11 - 2026-07-29 =\n* New - First.\n" ),
		);
	}

	/**
	 * A new release gets a zero-padded date, and writing it must not reformat the
	 * headings below it.
	 */
	public function test_new_entry_leaves_legacy_dates_alone() {
		$changelog = $this->formatter->parse( "*** Title ***\n\n= 1.0 - 2016-01-04 =\n* Fix - Fixed a thing.\n" );

		$changelog->addEntry(
			$this->formatter->newChangelogEntry(
				'1.3.13',
				array(
					'timestamp' => new DateTime( '2026-09-01', new DateTimeZone( 'UTC' ) ),
					'changes'   => array(
						$this->formatter->newChangeEntry(
							array(
								'subheading' => 'New',
								'content'    => 'Added a thing.',
							)
						),
					),
				)
			)
		);

		$this->assertSame(
			"*** Title ***\n\n= 1.3.13 - 2026-09-01 =\n* New - Added a thing.\n\n= 1.0 - 2016-01-04 =\n* Fix - Fixed a thing.\n",
			$this->formatter->format( $changelog )
		);
	}

	/**
	 * A change file may carry an empty entry when its significance is 'patch'. It must
	 * compile to no bullet at all, rather than to a dangling '* Fix - ' that the next
	 * parse would read back as an untyped change.
	 */
	public function test_a_change_with_no_content_writes_no_line() {
		$changelog = new Changelog();

		$changelog->addEntry(
			$this->formatter->newChangelogEntry(
				'1.3.13',
				array(
					'timestamp' => new DateTime( '2026-09-01', new DateTimeZone( 'UTC' ) ),
					'changes'   => array(
						$this->formatter->newChangeEntry(
							array(
								'subheading' => 'Fix',
								'content'    => '',
							)
						),
						$this->formatter->newChangeEntry(
							array(
								'subheading' => 'Tweak',
								'content'    => 'Tweaked a thing.',
							)
						),
					),
				)
			)
		);

		$this->assertSame(
			"*** Changelog ***\n\n= 1.3.13 - 2026-09-01 =\n* Tweak - Tweaked a thing.\n",
			$this->formatter->format( $changelog )
		);
	}

	/**
	 * With no changelog file to read a title from, the configured one is written.
	 */
	public function test_configured_title_is_used_when_the_changelog_is_empty() {
		$changelog = new Changelog();

		$changelog->addEntry(
			$this->formatter->newChangelogEntry(
				'1.0',
				array(
					'timestamp' => new DateTime( '2016-01-04', new DateTimeZone( 'UTC' ) ),
					'changes'   => array( $this->formatter->newChangeEntry( array( 'content' => 'Initial release.' ) ) ),
				)
			)
		);

		$this->assertSame(
			"*** Changelog ***\n\n= 1.0 - 2016-01-04 =\n* Initial release.\n",
			$this->formatter->format( $changelog )
		);
	}

	/**
	 * A bullet ahead of the first heading means the file is malformed. Absorbing it
	 * into the title would delete a release section on the next write.
	 */
	public function test_parse_rejects_a_bullet_before_the_first_heading() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n* Fix - Orphaned entry.\n\n= 1.3.12 - 2026-08-26 =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * A heading with a placeholder date is not a valid heading, and must not be
	 * quietly folded into the title either.
	 */
	public function test_parse_rejects_a_placeholder_date() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= 1.3.12 - 2026-xx-xx =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * A heading with a placeholder version is not a valid heading either.
	 */
	public function test_parse_rejects_a_placeholder_version() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= {$this->placeholder_version()} - 2026-08-19 =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * An entry with no release date is round-tripped, but not with a placeholder version.
	 */
	public function test_parse_rejects_a_placeholder_version_on_an_unreleased_heading() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= {$this->placeholder_version()} - unreleased =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * The heading the previous release flow left on top of the file, with both
	 * placeholders, must not be quietly folded into the title. The date alone rejects
	 * it; this pins the exact legacy string.
	 */
	public function test_parse_rejects_the_legacy_placeholder_heading() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= {$this->placeholder_version()} - 2026-xx-xx =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * A near-miss heading below the first release must not be read as one of that
	 * release's changes: its own changes would merge upward and the section would be
	 * dropped on the next write.
	 */
	public function test_parse_rejects_a_placeholder_heading_after_the_first_release() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= 1.3.12 - 2026-08-26 =\n* Fix - Fixed a thing.\n\n= {$this->placeholder_version()} - 2026-07-01 =\n* Fix - Fixed another thing.\n" );
	}

	/**
	 * The version placeholder, assembled at runtime: the release version bump rewrites
	 * the literal wherever it appears in the repository, fixture data included.
	 *
	 * @return string
	 */
	private function placeholder_version() {
		return implode( '.', array( 'x', 'x', 'x' ) );
	}

	/**
	 * A heading whose date does not exist is an error, not a date rolled over into
	 * the next month.
	 */
	public function test_parse_rejects_an_impossible_date() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= 1.3.12 - 2026-02-30 =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * An entry with no release date is written with `unreleased` in place of the date,
	 * and read back as one.
	 */
	public function test_an_entry_with_no_release_date_is_written_unreleased() {
		$changelog = new Changelog();

		$changelog->addEntry(
			$this->formatter->newChangelogEntry(
				'1.3.13',
				array(
					'timestamp' => null,
					'changes'   => array(
						$this->formatter->newChangeEntry(
							array(
								'subheading' => 'Fix',
								'content'    => 'Fixed a thing.',
							)
						),
					),
				)
			)
		);

		$written = $this->formatter->format( $changelog );

		$this->assertSame( "*** Changelog ***\n\n= 1.3.13 - unreleased =\n* Fix - Fixed a thing.\n", $written );

		$entry = $this->formatter->parse( $written )->getLatestEntry();

		$this->assertSame( '1.3.13', $entry->getVersion() );
		$this->assertNull( $entry->getTimestamp() );
		$this->assertSame( $written, $this->round_trip( $written ) );
	}

	/**
	 * A title starting with `==`, like a readme section, is a title, not a heading.
	 */
	public function test_a_title_starting_with_equals_signs_is_not_a_heading() {
		$changelog = "== Changelog ==\n\n= 1.0 - 2016-01-04 =\n* Initial release.\n";

		$this->assertSame( '== Changelog ==', $this->formatter->parse( $changelog )->getPrologue() );
		$this->assertSame( $changelog, $this->round_trip( $changelog ) );
	}

	/**
	 * A line that looks like a version heading but is not one must fail, not be read as
	 * a change of the release above it.
	 *
	 * @dataProvider provide_malformed_headings
	 *
	 * @param string $heading Malformed heading.
	 */
	public function test_parse_rejects_a_malformed_heading( $heading ) {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= 1.3.12 - 2026-08-26 =\n* Fix - Fixed a thing.\n\n$heading\n* Fix - Fixed another thing.\n" );
	}

	/**
	 * Malformed headings: broken WordPress.org ones, and the WooCommerce.com shape the
	 * paid extensions use.
	 *
	 * @return array
	 */
	public function provide_malformed_headings() {
		return array(
			'no date'                         => array( '= 1.1.0 =' ),
			'no separator'                    => array( '= 1.1.0 2026-09-01 =' ),
			'unpadded date'                   => array( '= 1.1.0 - 2026-9-1 =' ),
			'dotted date'                     => array( '= 1.1.0 - 2026.09.01 =' ),
			'placeholder date'                => array( '= 1.1.0 - 2026-xx-xx =' ),
			'impossible date'                 => array( '= 1.1.0 - 2026-02-30 =' ),
			'placeholder version'             => array( "= {$this->placeholder_version()} - 2026-09-01 =" ),
			'placeholder version, unreleased' => array( "= {$this->placeholder_version()} - unreleased =" ),
			'dotted WooCommerce.com heading'  => array( '2026.09.01 - version 1.1.0' ),
			'dashed WooCommerce.com heading'  => array( '2026-09-01 - version 1.1.0' ),
		);
	}
}
