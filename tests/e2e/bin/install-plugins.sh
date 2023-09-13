#!/bin/bash

if [[ -z "$BOT_GITHUB_TOKEN" ]]; then
	echo "Set the BOT_GITHUB_TOKEN secret"
	exit 1
fi

# Define a function to download latest release zip from a GitHub repo
download_latest_release() {
    local OWNER=$1
    local REPO=$2

    # Get the latest release URL from GitHub API
    local LATEST_RELEASE_URL=$(curl -s \
      -H "Accept: application/vnd.github+json" \
      -H "Authorization: Bearer $BOT_GITHUB_TOKEN" \
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
      -H "Authorization: Bearer $BOT_GITHUB_TOKEN" \
      -H "Accept: application/octet-stream" \
      -H "X-GitHub-Api-Version: 2022-11-28"
}

# Install WooCommerce Bookings
download_latest_release "woocommerce" "woocommerce-bookings"
