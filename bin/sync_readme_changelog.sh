#!/usr/bin/env bash

# Copies a release's entries from changelog.txt into the readme.txt changelog,
# which is where WordPress.org reads the changelog from. Does nothing when
# readme.txt has no "== Changelog ==" section.
#
# Usage: bin/sync_readme_changelog.sh <version>

set -e

VERSION=$1

if [[ -z $VERSION ]]; then
	echo "Usage: $0 <version>"
	exit 1
fi

if ! grep -q '^== Changelog ==' readme.txt; then
	echo "readme.txt has no changelog section. Nothing to sync."
	exit 0
fi

HEADING="= ${VERSION} - "

if [[ -n $( awk -v heading="$HEADING" 'index($0, heading) == 1' readme.txt ) ]]; then
	echo "readme.txt already lists ${VERSION}. Nothing to sync."
	exit 0
fi

# The release's heading and entries, up to the blank line that ends them.
ENTRY=$( awk -v heading="$HEADING" 'index($0, heading) == 1 {found=1} found && /^$/ {exit} found' changelog.txt )

if [[ -z $ENTRY ]]; then
	echo "No changelog.txt entry found for ${VERSION}."
	exit 1
fi

# Passed through the environment because awk -v would expand backslashes in the entries.
ENTRY="$ENTRY" awk '{print} /^== Changelog ==/ && !done {print ""; print ENVIRON["ENTRY"]; done=1}' readme.txt > readme.txt.tmp
mv readme.txt.tmp readme.txt

echo "Added ${VERSION} to the readme.txt changelog."
