#!/usr/bin/env bash

# The main plugin file is package.json config.sww.mainFile, or the package.json name (the plugin slug) plus .php, never the checkout directory.
MAIN_PLUGIN_FILE="$(bash "$(dirname "$0")/sww-config.sh" .mainFile)"
if [[ ! -f "$MAIN_PLUGIN_FILE" ]]; then
	echo "Missing main plugin file: $MAIN_PLUGIN_FILE"
	exit 1
fi

# HELPERS.
BLUE='\033[0;32m'
NC='\033[0m' # No Color
UNDERLINE_START='\e[4m'
UNDERLINE_STOP='\e[0m'

# GET BASE PHP VERSIONS.
PHP_HEADER_VERSION=$( awk '/\* *Version/ {print}' "$MAIN_PLUGIN_FILE" | sed 's/[^0-9.]*\([0-9.]*\).*/\1/' )
PHP_IN_VERSION=$PHP_HEADER_VERSION
# Every config.sww.versionPatterns entry (literal text with %s for the version, a leading ^
# anchoring it at the start of a line) must carry the header version.
while IFS=$'\t' read -r file pattern; do
	anchored=false
	if [[ "$pattern" == ^* ]]; then
		anchored=true
		pattern=${pattern#^}
	fi
	before=${pattern%%%s*}
	after=${pattern#*%s}
	found=''
	while IFS= read -r line; do
		[[ "$anchored" == false || "$line" == "$before"* ]] || continue
		value=${line#*"$before"}
		[[ "$value" == *"$after"* ]] || continue
		if [[ -n "$after" ]]; then
			found=${value%%"$after"*}
		else
			found=${value%%[[:space:]]*}
		fi
		break
	done < <(grep -F -- "$before" "$file" || true)
	if [[ "$found" != "$PHP_HEADER_VERSION" ]]; then
		echo "\"$pattern\" in $file has version '$found', the main file header has '$PHP_HEADER_VERSION'... Exiting with error."
		exit 1
	fi
done < <(bash "$(dirname "$0")/sww-config.sh" '.versionPatterns[] | [.file, .pattern] | @tsv')
echo -e "${BLUE}- Main PHP file versions: OK${NC}"

README_VERSION=$( awk '/Stable tag/ {print}' readme.txt | sed 's/[^0-9.]*\([0-9.]*[-dev]*\).*/\1/' )
PACKAGE_VERSION=$( awk '/"version":/ {print}' package.json | sed 's/[^0-9.]*\([0-9.]*[-dev]*\).*/\1/' )
if [[ $PACKAGE_VERSION != $PHP_IN_VERSION ]]; then
	echo "Package.json version does not match with main file... Exiting with error."
	exit 1
else
	echo -e "${BLUE}- Package.json version: OK${NC}"
fi

if [[ $README_VERSION != $PHP_IN_VERSION ]]; then
	echo "Readme.txt version does not match with main file... Exiting with error."
	exit 1
else
	echo -e "${BLUE}- Readme.txt version: OK${NC}"
fi

# WordPress.org reads the changelog from readme.txt (config.sww.release.readmeChangelog).
if [[ "$(bash "$(dirname "$0")/sww-config.sh" .release.readmeChangelog)" == true ]]; then
	README_CHANGELOG_EXIST=$( awk -v heading="= $PACKAGE_VERSION - " 'index($0, heading) == 1' readme.txt )
	if [[ -z $README_CHANGELOG_EXIST ]]; then
		echo "No readme.txt changelog entry found... Exiting with error."
		exit 1
	else
		echo -e "${BLUE}- Readme.txt changelog version: OK${NC}"
	fi
fi

CHANGELOG_EXIST=$( awk -v heading="= $PACKAGE_VERSION - " 'index($0, heading) == 1' changelog.txt )
if [[ -z $CHANGELOG_EXIST ]]; then
	echo "No changelog entry found... Exiting with error."
	exit 1
else
	date -d "$(echo $CHANGELOG_EXIST | awk '{print $4}')" +'%Y-%m-%d'
	if [[ $? -ne 0 ]]; then
		echo "Invalid date format found in changelog... Exiting with error."
		exit 1
	fi
	echo -e "${BLUE}- Changelog version: OK${NC}"
fi

echo -e "${UNDERLINE_START}File versions checked. Moving on...${UNDERLINE_STOP}"
exit 0
