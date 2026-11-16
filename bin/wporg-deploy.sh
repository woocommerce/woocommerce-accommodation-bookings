#!/usr/bin/env bash

# Checks around the woo-product-deploy WordPress.org release. Run from the plugin root.
#
#   prepare  Compares tags/<version> on WordPress.org SVN with the released ZIP and writes
#            deploy=true to $GITHUB_OUTPUT when the tag is missing, or deploy=false when it
#            holds exactly the ZIP's files (a retry after the commit landed). Any other tag
#            content fails, because SVN tags are never overwritten.
#   confirm  Waits until WordPress.org offers <version>. With release confirmation enabled,
#            that happens only after a committer confirms the release.
#
# WPORG_SVN_URL and WPORG_API_URL let a local SVN repository and API stand in when testing.

ASSET="$(bash "$(dirname "$0")/woo-config.sh" .slug).zip"
# The workflow passes the slug it resolved from trunk's metadata, which decided this job runs.
SLUG=${WP_ORG_SLUG:-$(bash "$(dirname "$0")/woo-config.sh" .release.wpOrgSlug)}
SVN_URL=${WPORG_SVN_URL:-https://plugins.svn.wordpress.org}
API_URL=${WPORG_API_URL:-https://api.wordpress.org/plugins/info/1.2/}

mode=${1:-}
if [[ ! "$RELEASE_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "A release version in X.Y.Z format is required." >&2
  exit 1
fi
if [[ ! "$SLUG" =~ ^[a-z0-9-]+$ ]]; then
  echo "config.woo.release.wpOrgSlug must be the WordPress.org plugin slug." >&2
  exit 1
fi

if [[ "$mode" == confirm ]]; then
  # The plugin API refreshes a few minutes after a release; past that, confirmation is pending.
  deadline=$((SECONDS + ${WPORG_CONFIRM_SECONDS:-600}))
  while :; do
    version=$(curl --silent --show-error --fail --max-time 30 --get \
      --data-urlencode action=plugin_information --data-urlencode "request[slug]=$SLUG" \
      --data-urlencode 'request[fields][sections]=0' "$API_URL" | jq -r '.version | strings') || version=
    if [[ "$version" == "$RELEASE_VERSION" ]]; then
      echo "WordPress.org is offering version ${RELEASE_VERSION}."
      exit 0
    fi
    (( SECONDS < deadline )) || break
    sleep "${WPORG_CONFIRM_INTERVAL:-30}"
  done
  echo "WordPress.org offers version ${version:-unknown}, not ${RELEASE_VERSION}. The release is committed to SVN: if release confirmation is pending, confirm it from the email sent to the plugin committers, then re-run failed jobs." >&2
  exit 1
fi

if [[ "$mode" != prepare || ! "$RELEASE_SHA256" =~ ^[0-9a-fA-F]{64}$ ]]; then
  echo "Pass prepare with a release version and SHA-256, or confirm with a release version." >&2
  exit 1
fi

if [ ! -s "$ASSET" ] || ! printf '%s  %s\n' "$RELEASE_SHA256" "$ASSET" | sha256sum -c - > /dev/null; then
  echo "Released archive is missing or its checksum does not match." >&2
  exit 1
fi

# woorelease deploys to config.wp_org_slug; checking another repository would skip a real deploy.
if ! jq -e --arg slug "$SLUG" '.config.wp_org_slug == $slug' package.json > /dev/null; then
  echo "config.wp_org_slug must match config.woo.release.wpOrgSlug." >&2
  exit 1
fi

tag_url="$SVN_URL/$SLUG/tags/$RELEASE_VERSION"
if ! error=$(svn info --non-interactive "$tag_url" 2>&1 > /dev/null); then
  if [[ "$error" == *W170000* ]]; then
    echo "WordPress.org has no tag ${RELEASE_VERSION} yet."
    echo "deploy=true" >> "$GITHUB_OUTPUT"
    exit 0
  fi
  printf 'Could not check %s:\n%s\n' "$tag_url" "$error" >&2
  exit 1
fi

work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT
# Two empty listings would compare equal, so a failed export or unzip must stop here.
if ! svn export --quiet --non-interactive "$tag_url" "$work/tag" || ! unzip -q "$ASSET" -d "$work/zip"; then
  echo "Could not compare WordPress.org tag ${RELEASE_VERSION} with the released archive." >&2
  exit 1
fi

# The folder woorelease deploys: the one named after the slug, else a sole top-level folder,
# else the ZIP root. Its top-level dotfiles are left out, as woorelease moves `<folder>/*`.
release="$work/zip"
entries=("$release"/*)
if [[ -d "$release/$SLUG" ]]; then
  release="$release/$SLUG"
elif [[ ${#entries[@]} == 1 && -d "${entries[0]}" ]]; then
  release=${entries[0]}
fi
find "$release" -mindepth 1 -maxdepth 1 -name '.*' -exec rm -rf {} +

checksums() {
  (cd "$1" && find . -type f -print0 | LC_ALL=C sort -z | xargs -0 sha256sum)
}
if ! difference=$(diff <(checksums "$work/tag") <(checksums "$release")); then
  printf 'WordPress.org tag %s differs from the released archive (< tag, > archive):\n%s\n' \
    "$RELEASE_VERSION" "$(head -n 40 <<< "$difference")" >&2
  exit 1
fi

echo "WordPress.org tag ${RELEASE_VERSION} already holds the released archive."
echo "deploy=false" >> "$GITHUB_OUTPUT"
