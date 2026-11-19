#!/usr/bin/env bash

set -euo pipefail

script=$1
shift

if [[ "$script" == install.sh && "${RUN_UNIT_TESTS:-}" == 0 ]]; then
	exit 0
fi

version=1.3.0
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
	sha512=eb5629f90d1a4fea2f055d5bbf78153c4209a52f04aaf7b9efc42047bbe1aacf8cc456461bfd8d277a95c16dbbff9d15080eff7831246fc76f5d5d2032b0ee47
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
