#!/usr/bin/env bash

validator="$(dirname "$0")/../vendor/bin/check_changelog.sh"
if [[ -x "$validator" ]]; then
    exec "$validator" "$@"
fi

# Keep help available on a checkout without Composer dependencies.
# Keep these options in sync with the pinned validator.
for argument in "$@"; do
    case "$argument" in
        --all|--optional) ;;
        -h|--help)
            cat <<'USAGE'
Usage: bash bin/check_changelog.sh [--all] [--optional] [<base-ref>]

  --all       Check every change file under changelog/, not just the ones this
              branch adds.
  --optional  Do not fail when there is no change file. Anything that is there
              is still validated.
  <base-ref>  Ref to diff against. Defaults to origin/trunk, or to the
              GITHUB_BASE_REF branch when running in GitHub Actions.
USAGE
            exit 0
            ;;
        -*) break ;;
    esac
done

echo "Run 'composer install' to install the shared extension checks." >&2
exit 127
