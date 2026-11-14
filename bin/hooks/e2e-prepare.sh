#!/usr/bin/env bash
# Called by bin/e2e.sh prepare: downloads the latest WooCommerce Bookings and Bookings Availability
# release ZIPs into deploy/e2e-dependencies/, which bin/link_plugins.js mounts for
# tests/e2e/bin/initialize.sh. CI passes ORG_DOWNLOADS in trusted runs; locally the GitHub CLI login is used.
set -euo pipefail

cd "$(dirname "$0")/../.."

if [[ -n "${ORG_DOWNLOADS:-}" ]]; then
	export GH_TOKEN=$ORG_DOWNLOADS
elif ! gh auth status > /dev/null 2>&1; then
	echo "Set ORG_DOWNLOADS to a GitHub token that can read the private plugin repositories, or log in with 'gh auth login'." >&2
	exit 1
fi

mkdir -p deploy/e2e-dependencies
for plugin in woocommerce-bookings woocommerce-bookings-availability; do
	gh release download --repo "woocommerce/$plugin" --pattern "$plugin.zip" --dir deploy/e2e-dependencies --clobber
	echo "Downloaded deploy/e2e-dependencies/$plugin.zip"
done
