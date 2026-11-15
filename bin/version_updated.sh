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

# Iterate over all PHP files passed as arguments
for FILE in "$@"; do
  # Ensure the file exists before proceeding
  if [ ! -f "$FILE" ]; then
    echo "⚠️ Warning: Skipping '$FILE' because it does not exist."
    continue
  fi

  # Check if the file contains a class, trait, or interface definition
  # POSIX classes instead of \s and \w, so BSD (macOS), GNU and mawk read the pattern alike.
  if grep -E '^[[:space:]]*(abstract[[:space:]]+|final[[:space:]]+)?(class|trait|interface|enum)[[:space:]]+[[:alnum:]_]+' "$FILE" > /dev/null; then
    # Extract the docblock before the first class/trait/interface definition
    DOCBLOCK=$(awk '/^[[:space:]]*(abstract[[:space:]]+|final[[:space:]]+)?(class|trait|interface|enum)[[:space:]]+[[:alnum:]_]+/ {exit} {print}' "$FILE")

    # Check if the docblock contains @version x.x.x
    if ! echo "$DOCBLOCK" | grep -E '@version[[:space:]]+x\.x\.x' > /dev/null; then
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
