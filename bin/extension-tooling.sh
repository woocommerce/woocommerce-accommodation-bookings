#!/usr/bin/env bash

set -euo pipefail

script=$1
shift

if [[ "$script" == install.sh && "${RUN_UNIT_TESTS:-}" == 0 ]]; then
	exit 0
fi

version=1.1.0
root="$(cd "$(dirname "$0")/.." && pwd)"
if [[ $(jq -r '.devDependencies["@woocommerce/extension-tooling"]' "$root/package.json") != "$version" ]]; then
	echo 'Update the extension tooling version and archive checksum together.' >&2
	exit 1
fi
tooling="$root/node_modules/@woocommerce/extension-tooling"

if [[ ! -f "$tooling/package.json" ]] || [[ $(jq -r .version "$tooling/package.json") != "$version" ]]; then
	# The shared PHP CI jobs call these scripts without Node or a pnpm install.
	work=$(mktemp -d)
	trap 'rm -rf "$work"' EXIT
	archive="$work/extension-tooling.tgz"
	curl --fail --silent --show-error --location --proto '=https' \
		--connect-timeout 10 --max-time 30 --retry 3 \
		--output "$archive" "https://registry.npmjs.org/@woocommerce/extension-tooling/-/extension-tooling-$version.tgz"
	sha512=86e27a87dc7ca627c5c5dfd4b75b82e3bdd28814ab8d33f4d06aa63623d03eca9ca9f69474944bf94f877da22a3340d3e37d76edea1551c1bcd48aafdc6ffe4f
	if ! printf '%s  %s\n' "$sha512" "$archive" | shasum -a 512 -c -s; then
		echo 'Extension tooling archive checksum does not match.' >&2
		exit 1
	fi
	tar -xzf "$archive" -C "$work"
	tooling="$work/package"
fi

if [[ "$script" == phpstan ]]; then
	EXTENSION_TOOLING_ROOT="$tooling" php vendor/bin/phpstan "$@"
else
	bash "$tooling/bin/$script" "$@"
fi
