#!/usr/bin/env bash

set -o pipefail

# HELPERS.
RED='\033[0;31m'
NC='\033[0m' # No Color

parse_changelog() {
    changelog=""
    record=0
    input="./changelog.txt"
    while IFS= read -r line; do
        if [[ $record == 1 && $line == "= "* ]]; then
            record=0
        fi
        if [[ $record == 1 && ${#line} -gt 0 ]]; then
            if [[ ${#changelog} -gt 0 ]]; then
                changelog="$changelog"$'\n'"$line"
            else
                changelog="$line"
            fi
        fi
        if [[ $line == "= $1 - "* ]]; then
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

archive="${PACKAGE_BASE}.zip"
if [[ ! -s "$archive" ]]; then
    echo "Release archive $archive is missing or empty."
    exit 1
fi
checksum=$(sha256sum "$archive") || exit 1

# Verify the tag itself. targetCommitish does not constrain an existing tag.
tag_path="refs/tags/${PACKAGE_VERSION}"
tag=$(gh api "repos/$GITHUB_REPOSITORY/git/matching-refs/tags/$PACKAGE_VERSION" |
    jq -r --arg ref "$tag_path" '.[] | select(.ref == $ref) | .ref') || exit 1
if [[ -z "$tag" ]]; then
    gh api -X POST "repos/$GITHUB_REPOSITORY/git/refs" -f "ref=$tag_path" -f "sha=$GH_RELEASE_COMMIT" > /dev/null || exit 1
fi
target=$(gh api "repos/$GITHUB_REPOSITORY/commits/refs%2Ftags%2F$PACKAGE_VERSION" --jq .sha) || exit 1
if [[ "$target" != "$GH_RELEASE_COMMIT" ]]; then
    echo "Release tag $PACKAGE_VERSION points to $target, expected $GH_RELEASE_COMMIT."
    exit 1
fi

if release=$(gh release view "$PACKAGE_VERSION" --json assets 2>/dev/null); then
    digest=$(jq -r --arg name "$archive" '.assets[] | select(.name == $name) | .digest // "unknown"' <<< "$release")
    if [[ -z "$digest" ]]; then
        gh release upload "$PACKAGE_VERSION" "$archive" || exit 1
    elif [[ "$digest" != "sha256:${checksum%% *}" ]]; then
        echo "The existing release ZIP differs. Re-run the failed jobs to reuse the tested artifact."
        exit 1
    fi
else
    gh release create "$PACKAGE_VERSION" "$archive" --title "v$PACKAGE_VERSION" --notes "$CHANGES" --verify-tag || exit 1
fi

echo "GitHub release $PACKAGE_VERSION is ready."
