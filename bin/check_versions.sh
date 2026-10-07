#!/usr/bin/env bash

PACKAGE_BASE=$(echo "$GITHUB_REPOSITORY" | awk -F'/' '{print $2}' )

# HELPERS.
BLUE='\033[0;32m'
NC='\033[0m' # No Color
UNDERLINE_START='\e[4m'
UNDERLINE_STOP='\e[0m'

# GET BASE PHP VERSIONS.
PHP_HEADER_VERSION=$( awk '/\* *Version/ {print}' $PACKAGE_BASE.php | sed 's/[^0-9.]*\([0-9.]*\).*/\1/' )
PHP_IN_VERSION=$( awk "/const VERSION|define\\( *'[A-Z_]*_VERSION'/ {print}" $PACKAGE_BASE.php | sed 's/[^0-9.]*\([0-9.]*[-dev]*\).*/\1/' )
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

# WordPress.org reads the changelog from readme.txt, so a readme.txt that carries one must list this release.
if grep -q '^== Changelog ==' readme.txt; then
	README_CHANGELOG_EXIST=$( awk -v heading="= $PACKAGE_VERSION - " 'index($0, heading) == 1' readme.txt )
	if [[ -z $README_CHANGELOG_EXIST ]]; then
		echo "No readme.txt changelog entry found... Exiting with error."
		exit 1
	else
		echo -e "${BLUE}- Readme.txt changelog version: OK${NC}"
	fi
fi

# The WordPress.org deploy reads the plugin slug from this key, so a missing slug must stop the release.
WP_ORG_SLUG=$( jq -r '.config.wp_org_slug // empty' package.json )
if [[ -z $WP_ORG_SLUG ]]; then
	echo "No config.wp_org_slug found in package.json, the WordPress.org deploy needs it... Exiting with error."
	exit 1
else
	echo -e "${BLUE}- WordPress.org slug (${WP_ORG_SLUG}): OK${NC}"
fi

echo -e "${UNDERLINE_START}File versions checked. Moving on...${UNDERLINE_STOP}"
exit 0
