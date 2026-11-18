#!/usr/bin/env bash

validator="$(dirname "$0")/../vendor/bin/version_updated.sh"
if [[ -x "$validator" ]]; then
    exec "$validator" "$@"
fi

# Preserve the direct-trunk skip on a checkout without Composer dependencies.
if [[ -z ${GITHUB_BASE_REF:-} ]]; then
    if [[ ${GITHUB_ACTIONS:-false} == true ]]; then
        current_branch=${GITHUB_REF_NAME:-}
    else
        current_branch=$(git rev-parse --abbrev-ref HEAD 2>/dev/null)
    fi
    if [[ "$current_branch" == trunk ]]; then
        echo "✅ Skipping version validation on direct commits to trunk."
        exit 0
    fi
fi

echo "Run 'composer install' to install the shared extension checks." >&2
exit 127
