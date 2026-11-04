#!/usr/bin/env bash

# Starts the wp-env tests site (.wp-env.test.json). The development site is `npm run env:start`.
# Extra arguments go to `wp-env start`.
#
# The tests site bind-mounts tests/_output/debug.log, which is git-ignored,
# and Docker turns a missing mount source into a directory, so make sure it is a file first.
set -euo pipefail

cd "$(dirname "$0")/.."

rmdir tests/_output/debug.log 2>/dev/null || true
touch tests/_output/debug.log

# Installs that mounted a directory keep one at the mount point, and a file can't mount onto it.
install_path=$(npm run --silent wp-env:test -- status --json 2>/dev/null | node -p 'JSON.parse(require("fs").readFileSync(0, "utf8")).installPath' 2>/dev/null || true)
if [ -n "$install_path" ]; then
	rmdir "${install_path}/WordPress/wp-content/debug.log" 2>/dev/null || true
fi

npm run wp-env:test -- start "$@"
