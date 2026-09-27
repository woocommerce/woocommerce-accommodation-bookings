#!/usr/bin/env bash

PACKAGE_BASE=$(echo "$GITHUB_REPOSITORY" | awk -F'/' '{print $2}' )

# HELPERS.
BLUE='\033[0;32m'
NC='\033[0m' # No Color
UNDERLINE_START='\e[4m'
UNDERLINE_STOP='\e[0m'

# GET BASE PHP VERSIONS.
PHP_HEADER_VERSION=$( awk '/\* *Version/ {print}' $PACKAGE_BASE.php | sed 's/[^0-9.]*\([0-9.]*\).*/\1/' )
PHP_IN_VERSION=$( awk "/define\( 'WC_ACCOMMODATION_BOOKINGS_VERSION'/ {print}" $PACKAGE_BASE.php | sed 's/[^0-9.]*\([0-9.]*[-dev]*\).*/\1/' )
if [[ $PHP_HEADER_VERSION != $PHP_IN_VERSION ]]; then
	echo "Different Versions in the main PHP file... Exiting with error."
	exit 1
else
	echo -e "${BLUE}- Main PHP file versions: OK${NC}"
fi

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

CHANGELOG_EXIST=$( awk "/- version $PACKAGE_VERSION/" changelog.txt )
if [[ -z $CHANGELOG_EXIST ]]; then
	echo "No changelog entry found... Exiting with error."
	exit 1
else
	date -d "$(echo $CHANGELOG_EXIST | awk '{print $1}' | sed 's/\./-/g')" +'%Y.%m.%d'
	if [[ $? -ne 0 ]]; then
		echo "Invalid date format found in changelog... Exiting with error."
		exit 1
	fi
	echo -e "${BLUE}- Changelog version: OK${NC}"
fi

echo -e "${UNDERLINE_START}File versions checked. Moving on...${UNDERLINE_STOP}"
exit 0
