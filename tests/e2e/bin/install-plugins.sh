#!/bin/bash

# CI downloads with the ORG_DOWNLOADS organization secret. Locally, set the same name or log in with the GitHub CLI.
ORG_DOWNLOADS=${ORG_DOWNLOADS:-$(gh auth token 2>/dev/null)}
if [[ -z "$ORG_DOWNLOADS" ]]; then
	echo "Set ORG_DOWNLOADS to a GitHub token that can read the private plugin repositories, or log in with 'gh auth login'"
	exit 1
fi

# Define a function to download latest release zip from a GitHub repo
download_latest_release() {
    local OWNER=$1
    local REPO=$2

    # Get the latest release URL from GitHub API
    local LATEST_RELEASE_URL=$(curl -s \
      -H "Accept: application/vnd.github+json" \
      -H "Authorization: Bearer $ORG_DOWNLOADS" \
      -H "X-GitHub-Api-Version: 2022-11-28" \
      "https://api.github.com/repos/$OWNER/$REPO/releases/latest" \
      | jq -r '.assets[0].url')
    
    if [ "$LATEST_RELEASE_URL" = "null" ]; then
      echo "ERROR: Error in find release URL"
      exit 1
    fi;

    # Remove file
    rm -f "$REPO.zip"

    # Download ZIP
    curl -f -O -J -L $LATEST_RELEASE_URL \
      -H "Authorization: Bearer $ORG_DOWNLOADS" \
      -H "Accept: application/octet-stream" \
      -H "X-GitHub-Api-Version: 2022-11-28"
}

# Install WooCommerce Bookings
download_latest_release "woocommerce" "woocommerce-bookings"
