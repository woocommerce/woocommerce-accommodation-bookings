#!/usr/bin/env bash
#
# Prints the plugin's tooling metadata: package.json `config.sww` with every default applied.
# Shared scripts and workflows read per-repository values only through this script, so the
# defaults live in one place. docs/config-sww.md in the shared set documents each key.
#
# Usage:
#   bash bin/sww-config.sh                   Normalized config as JSON.
#   bash bin/sww-config.sh '<jq filter>'     One value, e.g. '.zip.root' (raw output).
#   bash bin/sww-config.sh --github-output   Writes `json=<config>` to $GITHUB_OUTPUT.
#   bash bin/sww-config.sh --phpunit-matrix  PHPUnit legs as a JSON array, for the versions in
#                                            REQUESTED_WP, REQUESTED_WC and REQUESTED_PHP.
set -euo pipefail

cd "$(dirname "$0")/.."

config=$(jq -ce '
	# A key the object has, else the default. Unlike `//`, keeps false, 0 and "".
	def opt($key; $default): if type == "object" and has($key) then .[$key] else $default end;
	(.config.sww // {}) as $c
	| .name as $name
	| ($c | opt("mainFile"; $name + ".php")) as $main
	| ($c | opt("capabilities"; {})) as $cap
	| ($c | opt("zip"; {})) as $zip
	| ($c | opt("pluginCheck"; {})) as $pc
	| ($c | opt("qit"; {})) as $qit
	| ($c | opt("e2e"; {})) as $e2e
	| ($c | opt("phpunit"; {})) as $unit
	| ($c | opt("release"; {})) as $rel
	| ($c | opt("phpLint"; {})) as $lint
	| {
		name: $name,
		version: .version,
		mainFile: $main,
		productName: ($c | opt("productName"; $name)),
		capabilities: {
			js: ($cap | opt("js"; true)),
			jsTests: ($cap | opt("jsTests"; false)),
			buildTests: ($cap | opt("buildTests"; false)),
			e2e: ($cap | opt("e2e"; false)),
			phpunit: ($cap | opt("phpunit"; true)),
			isolated: ($cap | opt("isolated"; false)),
			integration: ($cap | opt("integration"; false)),
			qit: ($cap | opt("qit"; true)),
			pluginCheck: ($cap | opt("pluginCheck"; true))
		},
		zip: {
			root: ($zip | opt("root"; $name)),
			exclude: ($zip | opt("exclude"; "exclude.lst")),
			build: ($zip | opt("build"; "build")),
			composerProduction: ($zip | opt("composerProduction"; false)),
			php: ($zip | opt("php"; "8.4"))
		},
		versionPatterns: [ ($c | opt("versionPatterns"; []))[] | { file: opt("file"; $main), pattern: .pattern } ],
		pluginCheck: {
			excludeChecks: ($pc | opt("excludeChecks"; [])),
			excludeDirectories: ($pc | opt("excludeDirectories"; [])),
			excludeFiles: ($pc | opt("excludeFiles"; [])),
			ignoreCodes: ($pc | opt("ignoreCodes"; [])),
			ignoreWarnings: ($pc | opt("ignoreWarnings"; false)),
			strict: ($pc | opt("strict"; false)),
			timeoutMinutes: ($pc | opt("timeoutMinutes"; 10))
		},
		phpLint: {
			php: ($lint | opt("php"; [ "8.4" ])),
			isolatedPhp: ($lint | opt("isolatedPhp"; [ "8.4" ]))
		},
		qit: {
			extension: ($qit | opt("extension"; $name)),
			plugins: ($qit | opt("plugins"; "")),
			derivativeExclude: ($qit | opt("derivativeExclude"; "")),
			apiFuzz: ($qit | opt("apiFuzz"; true)),
			timeoutMinutes: ($qit | opt("timeoutMinutes"; 100))
		},
		e2e: {
			projects: ($e2e | opt("projects"; [ "" ])),
			timeoutMinutes: ($e2e | opt("timeoutMinutes"; 20)),
			privatePlugins: ($e2e | opt("privatePlugins"; false))
		},
		phpunit: {
			timeoutMinutes: ($unit | opt("timeoutMinutes"; 10)),
			phpInput: ($unit | opt("phpInput"; "ignore")),
			ini: ($unit | opt("ini"; "")),
			scheduledWc: ($unit | opt("scheduledWc"; "")),
			legs: ($unit | opt("legs"; [
				{ php: "7.4", wp: "latest", wc: "latest" },
				{ php: "8.4", wp: "latest", wc: "latest", coverage: true }
			]))
		},
		integration: {
			timeoutMinutes: ($c | opt("integration"; {}) | opt("timeoutMinutes"; 20)),
			legs: ($c | opt("integration"; {}) | opt("legs"; []))
		},
		privatePlugins: [ ($c | opt("privatePlugins"; []))[] | {
			slug: .slug,
			source: opt("source"; "release"),
			suites: opt("suites"; [ "unit" ]),
			dirEnv: opt("dirEnv"; ""),
			requireEnv: opt("requireEnv"; ""),
			hookOnly: opt("hookOnly"; false),
			minimumPattern: opt("minimumPattern"; "")
		} ],
		pullRequests: {
			privatePlugins: ($c | opt("pullRequests"; {}) | opt("privatePlugins"; false))
		},
		release: {
			target: ($rel | opt("target"; "woocommerce.com")),
			productId: ($rel | opt("productId"; "")),
			readmeChangelog: ($rel | opt("readmeChangelog"; false)),
			wpOrgSlug: ($rel | opt("wpOrgSlug"; ""))
		}
	}
' package.json) || {
	echo 'package.json must have a "name" and a valid "config.sww" object.' >&2
	exit 1
}

if [[ ! "$(jq -r .name <<< "$config")" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]]; then
	echo 'package.json "name" must be the plugin slug.' >&2
	exit 1
fi

# Turns config.sww.phpunit.legs into concrete jobs. A leg's `wp` and `wc` are `latest`,
# `previous` (WordPress only: the newest release of the previous major series, from
# $PREVIOUS_WP), `minimum` (the main file's "Requires at least" / "WC requires at least") or a
# version. A requested WordPress or WooCommerce version replaces `latest` and `previous`. A
# requested PHP version replaces every leg's PHP when phpunit.phpInput is "replace", except on
# legs with a `minimum` or fixed WordPress version. Legs with `"defaultOnly": true` run only
# when no WordPress or PHP version is requested, and legs with `"scheduledOnly": true` only when
# SCHEDULED=true (Cron CI). A leg's `plugins` picks a private plugin release: `latest`, `minimum`
# (that plugin's minimumPattern in the main file), `below-minimum` (the newest release older than
# the minimum) or a tag; the leg's `pluginVersions` carries it to the download step (`<x.y.z` for
# below-minimum). Identical legs are merged. SUITE=integration reads config.sww.integration.legs.
phpunit_matrix() {
	local main_file wp_minimum wc_minimum minimums='{}' slug pattern prefix suffix line version
	main_file=$(jq -r .mainFile <<< "$config")
	wp_minimum=$(awk -F ': ' '/^[[:space:]]*\* Requires at least:/{print $2; exit}' "$main_file" | tr -d '\r')
	wc_minimum=$(awk -F ': ' '/^[[:space:]]*\* WC requires at least:/{print $2; exit}' "$main_file" | tr -d '\r')
	[[ ! "$wc_minimum" =~ ^[0-9]+\.[0-9]+$ ]] || wc_minimum="$wc_minimum.0"
	while IFS=$'\t' read -r slug pattern; do
		prefix=${pattern%%%s*}
		suffix=${pattern#*%s}
		line=$(grep -F -m1 -- "$prefix" "$main_file") || { echo "No minimumPattern match for $slug in $main_file." >&2; return 1; }
		version=${line#*"$prefix"}
		version=${version%%"$suffix"*}
		[[ "$version" =~ ^[0-9]+(\.[0-9]+)*$ ]] || { echo "Invalid minimum $slug version '$version' in $main_file." >&2; return 1; }
		minimums=$(jq -c --arg slug "$slug" --arg version "$version" '. + { ($slug): $version }' <<< "$minimums")
	done < <(jq -r '.privatePlugins[] | select(.minimumPattern != "") | [ .slug, .minimumPattern ] | @tsv' <<< "$config")
	jq -c \
		--arg wp "${REQUESTED_WP:-}" --arg wc "${REQUESTED_WC:-}" --arg php "${REQUESTED_PHP:-}" \
		--arg previous "${PREVIOUS_WP:-}" --arg wpmin "$wp_minimum" --arg wcmin "$wc_minimum" \
		--arg suite "${SUITE:-unit}" --arg scheduled "${SCHEDULED:-false}" --argjson minimums "$minimums" '
		.phpunit.phpInput as $phpInput
		| (if $suite == "integration" then .integration else .phpunit end) as $u
		| ($wp != "" or $php != "") as $requested
		| [ .privatePlugins[].slug ] as $private
		| [ $u.legs[]
			| select(($requested and (.defaultOnly // false)) | not)
			| select((.scheduledOnly // false) | not or $scheduled == "true")
			| (.plugins // {}) as $plugins
			| ($plugins | to_entries | map(select(.value != "latest"))) as $pinned
			| ($pinned | map(.key as $slug | .value as $spec | { key: $slug, value: (
				if ($private | any(. == $slug) | not) then error("Leg plugin \($slug) is not in config.sww.privatePlugins")
				elif $spec == "minimum" or $spec == "below-minimum" then
					($minimums[$slug] // error("config.sww.privatePlugins \($slug) needs a minimumPattern"))
					| if $spec == "below-minimum" then "<" + . else . end
				else $spec end) }) | from_entries) as $pluginVersions
			| (.wp // "latest") as $legwp
			| (.wc // "latest") as $legwc
			| ($legwp == "latest" or $legwp == "previous") as $floating
			| {
				php: (if $php != "" and $phpInput == "replace" and $floating then $php else .php end),
				wp: (if $legwp == "minimum" then $wpmin
					elif $floating and $wp != "" then $wp
					elif $legwp == "previous" then $previous
					else $legwp end),
				wc: (if $legwc == "minimum" then $wcmin
					elif $legwc == "latest" and $wc != "" then $wc
					else $legwc end),
				script: (.script // "test:php:unit"),
				provision: (.provision // "install"),
				coverage: (.coverage // false),
				env: (.env // {}),
				pluginVersions: $pluginVersions,
				floor: ($legwp == "minimum")
			}
			| . + { label: "PHP \(.php) / WP \(.wp) / WC \(.wc)\(if (.env | length) > 0 then " / " + (.env | to_entries | map("\(.key)=\(.value)") | join(", ")) else "" end)\($pinned | map(" / \(.key) \(.value)") | join(""))\(if .script != "test:php:unit" then " / " + .script else "" end)" }
		]
		| reduce .[] as $leg ([]; if any(.[]; .label == $leg.label) then . else . + [ $leg ] end)
	' <<< "$config"
}

case "${1:-}" in
	'')
		printf '%s\n' "$config"
		;;
	--github-output)
		printf 'json=%s\n' "$config" >> "$GITHUB_OUTPUT"
		;;
	--phpunit-matrix)
		phpunit_matrix
		;;
	*)
		jq -r "$1" <<< "$config"
		;;
esac
