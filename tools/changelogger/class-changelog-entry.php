<?php
/**
 * Changelog_Entry class
 *
 * @package  WooCommerce Accommodation Bookings
 * @since    x.x.x
 */

namespace SomewhereWarm\Changelogger;

use Automattic\Jetpack\Changelog\ChangelogEntry;

/**
 * A changelog entry that remembers the date string it was parsed from.
 *
 * Every heading in `changelog.txt` is canonically formatted today, so this changes
 * nothing for the file as it stands. It is kept because re-formatting a parsed
 * timestamp would silently rewrite any heading that is not, and rewriting a decade of
 * history is exactly what the round-trip test exists to prevent. Entries created by
 * `changelogger write` have no raw string and are formatted canonically.
 *
 * @version x.x.x
 */
class Changelog_Entry extends ChangelogEntry {

	/**
	 * Date string this entry was parsed from, if any.
	 *
	 * @var string|null
	 */
	protected $raw_timestamp = null;

	/**
	 * Get the raw date string.
	 *
	 * @return string|null
	 */
	public function getRawTimestamp() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Matches the camelCase API of the parent class.
		return $this->raw_timestamp;
	}

	/**
	 * Set the raw date string.
	 *
	 * @param string|null $timestamp Date string as it appeared in the changelog, or null to format the parsed timestamp instead.
	 * @return $this
	 */
	public function setRawTimestamp( $timestamp ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Matches the camelCase API of the parent class.
		$this->raw_timestamp = null === $timestamp ? null : (string) $timestamp;
		return $this;
	}
}
