#!/usr/bin/env bash
#
# sync_readme_changelog.sh - Copy a release's changelog.txt entries into readme.txt.
#
# WordPress.org shows the changelog from readme.txt, not changelog.txt. Run after
# `changelogger write` has compiled the release into changelog.txt.
#
# Usage: bash bin/sync_readme_changelog.sh <version>

set -euo pipefail

VERSION="${1:-}"

if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	echo "Usage: bash bin/sync_readme_changelog.sh X.Y.Z"
	exit 1
fi

# The heading reads `YYYY.MM.DD - version X.Y.Z`. Dots in the version are escaped for awk.
VERSION_PATTERN="${VERSION//./\\.}"
HEADING=$(awk -v pattern="^[0-9]{4}\\\\.[0-9]{2}\\\\.[0-9]{2} - version ${VERSION_PATTERN}\$" '$0 ~ pattern { print; exit }' changelog.txt)

if [[ -z "$HEADING" ]]; then
	echo "No changelog.txt entry for ${VERSION}."
	exit 1
fi

if grep -q "^= ${VERSION_PATTERN} - " readme.txt; then
	echo "readme.txt already has a changelog entry for ${VERSION}."
	exit 1
fi

DATE=$(echo "${HEADING%% *}" | tr '.' '-')
ENTRIES=$(awk -v heading="$HEADING" '$0 == heading { found = 1; next } found && / - version / { exit } found && NF { print }' changelog.txt)

if [[ -z "$ENTRIES" ]]; then
	echo "The changelog.txt entry for ${VERSION} has no changes."
	exit 1
fi

BLOCK_FILE=$(mktemp)
OUTPUT_FILE=$(mktemp)
trap 'rm -f "$BLOCK_FILE" "$OUTPUT_FILE"' EXIT

printf '= %s - %s =\n%s\n\n' "$VERSION" "$DATE" "$ENTRIES" > "$BLOCK_FILE"

# Insert the block below `== Changelog ==` and the blank line that follows it.
awk -v block_file="$BLOCK_FILE" '
	inserted == 0 && seen == 1 && /^$/ { print; while ( ( getline line < block_file ) > 0 ) print line; inserted = 1; next }
	/^== Changelog ==$/ { seen = 1 }
	{ print }
	END { if ( inserted == 0 ) exit 1 }
' readme.txt > "$OUTPUT_FILE" || { echo "No '== Changelog ==' section in readme.txt."; exit 1; }

cat "$OUTPUT_FILE" > readme.txt

echo "Added the ${VERSION} changelog to readme.txt."
