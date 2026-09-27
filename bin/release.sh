#!/usr/bin/env bash

# HELPERS.
BLUE='\033[0;32m'
COLOR='\033[0;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

parse_changelog() {
    changelog=""
    record=0
    input="./changelog.txt"
    while IFS= read -r line; do
        if [[ $record == 1 && $line == *"- version"* ]]; then
            record=0
        fi
        if [[ $record == 1 && ${#line} -gt 0 ]]; then
            if [[ ${#changelog} -gt 0 ]]; then
                changelog="$changelog"$'\n'"$line"
            else
                changelog="$line"
            fi
        fi
        if [[ $line == *"- version $1"* ]]; then
            record=1
        fi
    done < "$input"
    echo "$changelog"
}

if [ ! -f package.json ]; then
    echo -e "${RED}Error: package.json not found.${NC}"
    exit 1
fi

if [ ! -f changelog.txt ]; then
    echo -e "${RED}Error: changelog.txt not found.${NC}"
    exit 1
fi

# Define variables.
PACKAGE_VERSION=$(jq -r .version package.json)
PACKAGE_BASE=$(basename "$(git rev-parse --show-toplevel)")

if ! command -v gh &> /dev/null; then
    echo -e "${RED}Error: GitHub CLI (gh) is not installed. Exiting...${NC}"
    exit 1
fi

# Ensure the user is authenticated with GitHub
if ! gh auth status &> /dev/null; then
    echo -e "${RED}Error: Not authenticated with GitHub CLI. Run 'gh auth login'. Exiting...${NC}"
    exit 1
fi

# Parse the changelog for the releasing version.
CHANGES=$(parse_changelog "$PACKAGE_VERSION" | sed 's/"/\\"/g')

# Create a GitHub release.
echo -e "${BLUE}Creating a GitHub release for v${PACKAGE_VERSION}...${NC}"
if ! gh release create "${PACKAGE_VERSION}" --title "v${PACKAGE_VERSION}" --notes "${CHANGES}" --target "$GH_RELEASE_COMMIT"; then
    echo -e "${RED}Error: Failed to create GitHub release. Exiting...${NC}"
    exit 1
fi

# Check if the zip file exists before uploading
if [ -f "${PACKAGE_BASE}.zip" ]; then
    echo -e "${BLUE}Uploading ${PACKAGE_BASE}.zip as a release asset...${NC}"
    if ! gh release upload "${PACKAGE_VERSION}" "${PACKAGE_BASE}.zip"; then
        echo -e "${RED}Error: Failed to upload release asset. Exiting...${NC}"
        exit 1
    fi
else
    echo -e "${RED}Error: ${PACKAGE_BASE}.zip not found. Skipping asset upload.${NC}"
    exit 1
fi

echo -e "\n${COLOR}Release successful!${NC}"
exit 0
