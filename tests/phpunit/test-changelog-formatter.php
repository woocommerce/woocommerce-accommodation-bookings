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
	 * rewrites a decade of history in the release pull request.
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
		$this->assertGreaterThan( 50, count( $changelog->getEntries() ) );

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
			. "2026.08.19 - version 3.1.4\n"
			. "* Fix - Fixed a thing.\n"
			. "* Tweak - Tweaked a thing.\n"
		);

		$entries = $changelog->getEntries();

		$this->assertCount( 1, $entries );
		$this->assertSame( '3.1.4', $entries[0]->getVersion() );
		$this->assertSame( '2026-08-19', $entries[0]->getTimestamp()->format( 'Y-m-d' ) );

		$changes = $entries[0]->getChanges();

		$this->assertCount( 2, $changes );
		$this->assertSame( 'Fix', $changes[0]->getSubheading() );
		$this->assertSame( 'Fixed a thing.', $changes[0]->getContent() );
		$this->assertSame( 'Tweak', $changes[1]->getSubheading() );
	}

	/**
	 * Only the first ' - ' separates the type from the entry. The file carries several
	 * entries that use it again inside their text.
	 */
	public function test_entry_content_may_contain_the_separator() {
		$changelog = $this->formatter->parse(
			"*** Title ***\n\n2023.05.12 - version 1.1.40\n* Fix - Fully booked days show as partially booked - Day after booking shows partially booked.\n"
		);

		$change = $changelog->getEntries()[0]->getChanges()[0];

		$this->assertSame( 'Fix', $change->getSubheading() );
		$this->assertSame( 'Fully booked days show as partially booked - Day after booking shows partially booked.', $change->getContent() );
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
	 * Every case below is taken from a shape `changelog.txt` actually contains, except
	 * the unpadded date: this file happens to be padded throughout, but the formatter
	 * is shared across plugins whose files are not, so the tolerance is covered here.
	 *
	 * @return array
	 */
	public function provide_legacy_shapes() {
		return array(
			'bullet with no type'  => array( "*** Title ***\n\n2015.12.21 - version 1.0\n* Initial version.\n" ),
			'retired type'         => array( "*** Title ***\n\n2016.05.26 - version 1.0.4\n* Feature - Add support for Persons\n" ),
			'retired type, second' => array( "*** Title ***\n\n2026.07.29 - version 1.3.11\n* Update - Calendar display option text to clarify user interaction.\n" ),
			'several entries'      => array( "*** Title ***\n\n2026.08.19 - version 3.1.4\n* Fix - Second.\n\n2026.07.22 - version 3.1.3\n* New - First.\n" ),
			'unpadded date'        => array( "*** Title ***\n\n2015.3.3 - version 1.0.1\n* Fix - Fixed a thing.\n" ),
		);
	}

	/**
	 * Writing a new entry must not reformat the dates of the entries below it.
	 */
	public function test_new_entry_leaves_legacy_dates_alone() {
		$changelog = $this->formatter->parse( "*** Title ***\n\n2015.3.3 - version 1.0.1\n* Fix - Fixed a thing.\n" );

		$changelog->addEntry(
			$this->formatter->newChangelogEntry(
				'3.1.5',
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
			"*** Title ***\n\n2026.09.01 - version 3.1.5\n* New - Added a thing.\n\n2015.3.3 - version 1.0.1\n* Fix - Fixed a thing.\n",
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
				'3.1.5',
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
			"*** Changelog ***\n\n2026.09.01 - version 3.1.5\n* Tweak - Tweaked a thing.\n",
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
				'1.0.0',
				array(
					'timestamp' => new DateTime( '2026-09-01', new DateTimeZone( 'UTC' ) ),
					'changes'   => array( $this->formatter->newChangeEntry( array( 'content' => 'Initial Release' ) ) ),
				)
			)
		);

		$this->assertSame(
			"*** Changelog ***\n\n2026.09.01 - version 1.0.0\n* Initial Release\n",
			$this->formatter->format( $changelog )
		);
	}

	/**
	 * A bullet ahead of the first heading means the file is malformed. Absorbing it
	 * into the title would delete a release section on the next write.
	 */
	public function test_parse_rejects_a_bullet_before_the_first_heading() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n* Fix - Orphaned entry.\n\n2026.08.19 - version 3.1.4\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * A heading with a placeholder date is not a valid heading, and must not be
	 * quietly folded into the title either.
	 */
	public function test_parse_rejects_a_placeholder_date() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n2026.xx.xx - version 3.1.4\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * A heading with a placeholder version is not a valid heading either.
	 */
	public function test_parse_rejects_a_placeholder_version() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n2026.08.19 - version {$this->placeholder_version()}\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * An entry with no release date is round-tripped, but not with a placeholder version.
	 */
	public function test_parse_rejects_a_placeholder_version_on_an_unreleased_heading() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\nunreleased - version {$this->placeholder_version()}\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * The heading the previous release flow left on top of the file, with both
	 * placeholders, must not be quietly folded into the title. The date alone rejects
	 * it; this pins the exact legacy string.
	 */
	public function test_parse_rejects_the_legacy_placeholder_heading() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n2026.xx.xx - version {$this->placeholder_version()}\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * A near-miss heading below the first release must not be read as one of that
	 * release's changes: its own changes would merge upward and the section would be
	 * dropped on the next write.
	 */
	public function test_parse_rejects_a_placeholder_heading_after_the_first_release() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n2026.08.19 - version 3.1.4\n* Fix - Fixed a thing.\n\n2026.07.01 - version {$this->placeholder_version()}\n* Fix - Fixed another thing.\n" );
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
		$this->formatter->parse( "*** Title ***\n\n2026.02.30 - version 3.1.4\n* Fix - Fixed a thing.\n" );
	}
}
