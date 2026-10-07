#!/bin/bash

# Flag to track if validation fails
FAILED=0
IS_GITHUB_ACTION=${GITHUB_ACTIONS:-false}
FAILED_FILES=()

# Detect if running on GitHub Actions
if [ "$IS_GITHUB_ACTION" = "true" ]; then
  TARGET_BRANCH=${GITHUB_BASE_REF}  # PR target branch
  CURRENT_BRANCH=${GITHUB_REF_NAME} # Current branch (for direct commits)
else
  TARGET_BRANCH=$(git rev-parse --abbrev-ref HEAD)  # Local branch name
  CURRENT_BRANCH=$TARGET_BRANCH
fi

# Skip validation if this is a direct commit to trunk
if [ "$CURRENT_BRANCH" = "trunk" ] && [ -z "$GITHUB_BASE_REF" ]; then
  echo "✅ Skipping version validation on direct commits to trunk."
  exit 0
fi

# Determine which `awk` is available (BSD or GNU)
AWK_CMD="awk"
if [[ "$(uname)" == "Darwin" ]]; then
  AWK_CMD="gawk" # macOS requires GNU awk for compatibility
  if ! command -v gawk &>/dev/null; then
    echo "⚠️ Warning: 'gawk' (GNU awk) is not installed. Falling back to standard awk, which may not be fully compatible."
    AWK_CMD="awk"
  fi
fi

# Iterate over all PHP files passed as arguments
for FILE in "$@"; do
  # Ensure the file exists before proceeding
  if [ ! -f "$FILE" ]; then
    echo "⚠️ Warning: Skipping '$FILE' because it does not exist."
    continue
  fi

  # Check if the file contains a class, trait, or interface definition
  if grep -E '^\s*(abstract\s+|final\s+)?(class|trait|interface)\s+\w+' "$FILE" > /dev/null; then
    # Extract the docblock before the first class/trait/interface definition
    DOCBLOCK=$($AWK_CMD '/^\s*(abstract\s+|final\s+)?(class|trait|interface)\s+\w+/ {exit} {print}' "$FILE")

    # Check if the docblock contains @version x.x.x
    if ! echo "$DOCBLOCK" | grep -E '@version\s+x\.x\.x' > /dev/null; then
      FAILED_FILES+=("$FILE")
      if [ "$IS_GITHUB_ACTION" = "true" ]; then
        # GitHub Actions annotation format
        echo "::error file=$FILE::A class, trait, or interface in this file was modified, but the '@version x.x.x' tag was not updated."
      else
        # Standard output for local usage (lint-staged)
        echo "❌ Error: A class, trait, or interface in '$FILE' was modified, but the '@version x.x.x' tag was not updated."
      fi
      FAILED=1
    fi
  fi
done

# Output only the failed files for GitHub Actions
if [ "$IS_GITHUB_ACTION" = "true" ]; then
  if [ ${#FAILED_FILES[@]} -gt 0 ]; then
    FAILED_FILES_STRING=$(printf "%s " "${FAILED_FILES[@]}")
    echo "FAILED_FILES=$FAILED_FILES_STRING" >> $GITHUB_ENV
  fi
fi

# If any file failed validation, exit with error
if [ "$FAILED" -ne 0 ]; then
  exit 1
fi

exit 0
