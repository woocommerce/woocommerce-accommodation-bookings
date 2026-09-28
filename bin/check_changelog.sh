#!/bin/bash
#
# check_changelog.sh - Verify a branch carries a valid changelog change file.
#
# Every pull request adds one or more files under changelog/ instead of editing
# changelog.txt by hand; `changelogger write` compiles them at release time.
# This checks that the change file is there and that it is well formed.
#
# Usage: bash bin/check_changelog.sh [--all] [--optional] [<base-ref>]
#
#   --all       Check every change file under changelog/, not just the ones this
#               branch adds. What the release compile needs: it consumes all of
#               them, whichever branch added them.
#   --optional  Do not fail when there is no change file. Anything that is there
#               is still validated. Used for pull requests labeled 'no changelog',
#               where the label waives the entry, not the format.
#   <base-ref>  Ref to diff against. Defaults to origin/trunk, or to the
#               GITHUB_BASE_REF branch when running in GitHub Actions.
#
# Exits non-zero when the branch has no change file or a change file is invalid.
# In GitHub Actions the findings are emitted as workflow annotations.

set -uo pipefail

IS_GITHUB_ACTION=${GITHUB_ACTIONS:-false}

CHANGES_DIR="changelog"
FAILED=0
CHECK_ALL=0
OPTIONAL=0
BASE_REF=""

usage() {
    cat <<'USAGE'
Usage: bash bin/check_changelog.sh [--all] [--optional] [<base-ref>]

  --all       Check every change file under changelog/, not just the ones this
              branch adds.
  --optional  Do not fail when there is no change file. Anything that is there
              is still validated.
  <base-ref>  Ref to diff against. Defaults to origin/trunk, or to the
              GITHUB_BASE_REF branch when running in GitHub Actions.
USAGE
}

fail() {
    local message="$1"
    local file="${2:-}"

    if [ "$IS_GITHUB_ACTION" = "true" ]; then
        if [ -n "$file" ]; then
            echo "::error file=${file}::${message}"
        else
            echo "::error::${message}"
        fi
    else
        echo "❌ ${file:+$file: }${message}"
    fi

    FAILED=1
}

while [ $# -gt 0 ]; do
    case "$1" in
        --all)
            CHECK_ALL=1
            shift
            ;;
        --optional)
            OPTIONAL=1
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        -*)
            echo "Unknown option: $1"
            usage
            exit 1
            ;;
        *)
            BASE_REF="$1"
            shift
            ;;
    esac
done

if [ ! -f package.json ]; then
    fail "Run this script from the repository root."
    exit 1
fi

# --all reads the directory, so it needs no base ref to diff against.
if [ "$CHECK_ALL" -eq 0 ]; then
    if [ -z "$BASE_REF" ]; then
        if [ "$IS_GITHUB_ACTION" = "true" ] && [ -n "${GITHUB_BASE_REF:-}" ]; then
            BASE_REF="origin/${GITHUB_BASE_REF}"
        else
            BASE_REF="origin/trunk"
        fi
    fi

    if ! git rev-parse --verify --quiet "$BASE_REF" > /dev/null; then
        fail "Base ref '$BASE_REF' not found. Fetch it first, or pass one explicitly."
        exit 1
    fi
fi

# Change files this branch adds, or every change file in the directory under --all.
collect_change_files() {
    if [ "$CHECK_ALL" -eq 1 ]; then
        find "$CHANGES_DIR" -maxdepth 1 -type f
        return
    fi

    # Only added files count. Editing somebody else's change file is not an entry
    # for this pull request, and a deleted one is what a release compile looks like.
    git diff --name-only --diff-filter=A "$BASE_REF"...HEAD -- "$CHANGES_DIR"

    # Locally the change file is usually not committed yet, so count what is
    # staged or still untracked too. CI only ever sees committed work.
    if [ "$IS_GITHUB_ACTION" != "true" ]; then
        git diff --cached --name-only --diff-filter=A -- "$CHANGES_DIR"
        git ls-files --others --exclude-standard -- "$CHANGES_DIR"
    fi
}

# Read into an array the long way round: `mapfile` needs bash 4, and macOS ships 3.2.
# Dotfiles are skipped: changelog/.gitkeep keeps the directory in git.
CHANGE_FILES=()
while IFS= read -r CHANGE_FILE; do
    [ -n "$CHANGE_FILE" ] && CHANGE_FILES+=( "$CHANGE_FILE" )
done < <(collect_change_files | grep -v "/\." | sort -u || true)

if [ ${#CHANGE_FILES[@]} -eq 0 ]; then
    if [ "$OPTIONAL" -eq 1 ]; then
        echo "No change file, and none is required here."
        exit 0
    fi

    if [ "$CHECK_ALL" -eq 1 ]; then
        fail "No change files under ${CHANGES_DIR}/. Nothing to compile."
    else
        fail "No changelog file found. Run 'npm run changelog add' and commit the file it creates under ${CHANGES_DIR}/, or label the pull request 'no changelog' if this change needs no entry."
    fi

    exit 1
fi

# Locally the list above can include work that is not committed yet. Say which,
# because CI diffs commits: an uncommitted change file is one CI will never see.
COMMITTED_FILES=""
ANNOTATE_UNCOMMITTED=0
if [ "$CHECK_ALL" -eq 0 ] && [ "$IS_GITHUB_ACTION" != "true" ]; then
    ANNOTATE_UNCOMMITTED=1
    COMMITTED_FILES=$(git diff --name-only --diff-filter=A "$BASE_REF"...HEAD -- "$CHANGES_DIR")
fi

echo "Found ${#CHANGE_FILES[@]} change file(s):"
for FILE in "${CHANGE_FILES[@]}"; do
    if [ "$ANNOTATE_UNCOMMITTED" -eq 1 ] && ! printf '%s\n' "$COMMITTED_FILES" | grep -qxF -- "$FILE"; then
        echo "  $FILE (not committed yet - commit it, or CI will not see it)"
    else
        echo "  $FILE"
    fi
done

# Validate the headers and the entry with changelogger itself.
if [ ! -x vendor/bin/changelogger ]; then
    fail "vendor/bin/changelogger not found. Run 'composer install' first."
    exit 1
fi

if [ "$IS_GITHUB_ACTION" = "true" ]; then
    php vendor/bin/changelogger validate --gh-action --basedir="$PWD" "${CHANGE_FILES[@]}" || FAILED=1
else
    php vendor/bin/changelogger validate --basedir="$PWD" "${CHANGE_FILES[@]}" || FAILED=1
fi

# changelogger accepts multi-line entries, but they break the one-entry-per-line
# shape of changelog.txt: every line after the first is re-read as its own
# entry the next time the changelog is parsed.
for FILE in "${CHANGE_FILES[@]}"; do
    # changelogger validate already reported anything unreadable.
    [ -f "$FILE" ] || continue

    ENTRY_LINES=$(awk 'body && NF { count++ } !body && /^[[:space:]]*$/ { body = 1 } END { print count + 0 }' "$FILE")

    if [ "$ENTRY_LINES" -gt 1 ]; then
        fail "The changelog entry must be a single line, found ${ENTRY_LINES}. Every line after the first is re-read as its own entry when changelog.txt is next parsed. Move the extra detail into a 'Comment:' header, which is not compiled into the changelog." "$FILE"
    fi
done

if [ "$FAILED" -ne 0 ]; then
    exit 1
fi

echo "✅ Changelog change file(s) OK."
exit 0
