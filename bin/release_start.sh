#!/bin/bash

# release_start.sh - Start a release from the command line.
#
# Thin wrapper around the "Start Release" GitHub Actions workflow
# (.github/workflows/release-start.yml), which does the actual work:
# version bump on a release/X.Y.Z branch and a PR against trunk.

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
error()   { echo -e "${RED}[ERROR]${NC} $1"; }

abort() {
    error "$1"
    exit 1
}

usage() {
    cat <<'USAGE'
Usage: bin/release_start.sh [<version>] [--wp <wp-version>] [--wc <wc-version>]

Start a release by dispatching the "Start Release" GitHub Actions workflow.

Arguments:
  <version>           Release version (X.Y.Z). Prompted if omitted.
  --wp <wp-version>   WP tested-up-to version (X.X). Omitted = no change.
  --wc <wc-version>   WC tested-up-to version (X.X). Omitted = no change.
  -h, --help          Show this help message and exit
USAGE
    exit 0
}

VERSION=""
WP_VERSION=""
WC_VERSION=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        -h|--help) usage ;;
        --wp) WP_VERSION="$2"; shift 2 ;;
        --wc) WC_VERSION="$2"; shift 2 ;;
        -*) abort "Unknown option: $1. Use --help for usage." ;;
        *)
            if [[ -z "$VERSION" ]]; then
                VERSION="$1"; shift
            else
                abort "Unexpected argument: $1. Use --help for usage."
            fi
            ;;
    esac
done

command -v gh >/dev/null 2>&1 || abort "GitHub CLI (gh) is not installed."
gh auth status >/dev/null 2>&1 || abort "Not authenticated with GitHub CLI. Run 'gh auth login' first."
command -v jq >/dev/null 2>&1 || abort "jq is not installed."
[[ -f package.json ]] || abort "package.json not found. Run this script from the repository root."

# Prompt interactively when no version was given, showing the current values.
if [[ -z "$VERSION" ]]; then
    CURRENT_VERSION=$(jq -r .version package.json)
    MAIN_PLUGIN_FILE="$(basename "$PWD").php"
    CURRENT_WP=$(grep '^Tested up to:' readme.txt | head -1 | sed 's/.*Tested up to:[[:space:]]*\([0-9.]*\).*/\1/')
    CURRENT_WC=$(grep 'WC tested up to:' "$MAIN_PLUGIN_FILE" | head -1 | sed 's/.*WC tested up to:[[:space:]]*\([0-9.]*\).*/\1/')

    info "Current version:         $CURRENT_VERSION"
    info "Current WP tested up to: ${CURRENT_WP:-N/A}"
    info "Current WC tested up to: ${CURRENT_WC:-N/A}"
    echo
    read -p "Release version (X.Y.Z): " VERSION
    read -p "WP tested up to [${CURRENT_WP}, enter to keep]: " WP_VERSION
    read -p "WC tested up to [${CURRENT_WC}, enter to keep]: " WC_VERSION
fi

[[ -z "$VERSION" ]] && abort "Version is required."
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || abort "Invalid version format '$VERSION'. Expected X.Y.Z."
[[ -n "$WP_VERSION" && ! "$WP_VERSION" =~ ^[0-9]+\.[0-9]+$ ]] && abort "Invalid WP version format '$WP_VERSION'. Expected X.X."
[[ -n "$WC_VERSION" && ! "$WC_VERSION" =~ ^[0-9]+\.[0-9]+$ ]] && abort "Invalid WC version format '$WC_VERSION'. Expected X.X."

echo
info "Starting release ${BOLD}${VERSION}${NC}${WP_VERSION:+ (WP tested up to $WP_VERSION)}${WC_VERSION:+ (WC tested up to $WC_VERSION)}"

gh workflow run release-start.yml \
    --ref trunk \
    -f "version=${VERSION}" \
    -f "wp_version=${WP_VERSION}" \
    -f "wc_version=${WC_VERSION}"

success "Workflow dispatched."

# The run takes a moment to appear in the API; poll briefly so we can watch it.
info "Waiting for the workflow run to start…"
RUN_ID=""
for _ in $(seq 1 10); do
    sleep 3
    RUN_ID=$(gh run list --workflow=release-start.yml --limit 1 --json databaseId,status \
        --jq '.[] | select(.status != "completed") | .databaseId' | head -1)
    [[ -n "$RUN_ID" ]] && break
done

if [[ -z "$RUN_ID" ]]; then
    info "Could not find the run yet. Check progress with: gh run list --workflow=release-start.yml"
    exit 0
fi

if ! gh run watch "$RUN_ID" --exit-status; then
    abort "Workflow failed. See details: $(gh run view "$RUN_ID" --json url --jq .url)"
fi

PR_URL=$(gh pr list --head "release/${VERSION}" --json url --jq '.[0].url')
echo
success "Release PR: ${PR_URL:-created (see gh pr list)}"
echo "Next steps: review the PR and the changelog vs milestone comment, wait for CI, then merge to ship."
echo "Trunk is under code freeze until the release PR is merged or closed."
