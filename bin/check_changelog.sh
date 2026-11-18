#!/usr/bin/env bash

# Show the extension command in help, including before Composer setup.
# Keep these options in sync with the pinned validator.
print_usage() {
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

for argument in "$@"; do
    case "$argument" in
        --all|--optional) ;;
        -h|--help)
            print_usage
            exit 0
            ;;
        -*)
            printf 'Unknown option: %s\n' "$argument"
            print_usage
            exit 1
            ;;
    esac
done

# The shared check discovers and validates files for valid invocations.
validator="$(dirname "$0")/../vendor/bin/check_changelog.sh"
if [[ -x "$validator" ]]; then
    exec "$validator" "$@"
fi

echo "Run 'composer install' to install the shared extension checks." >&2
exit 127
