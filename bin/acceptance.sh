#!/usr/bin/env bash

# Playwright E2E runner for the wp-env tests site (.wp-env.test.json).
#
# The same file runs every Playwright extension: only the REPO SETTINGS block
# below differs between repos, so copy the rest unchanged.
#
# USAGE
#   bash ./bin/acceptance.sh [ setup | run [playwright args] | reset | help ]
#
#   (no subcommand)  Full pipeline: setup + run the whole suite headless + stop
#                    the tests site (also on failure).
#   setup            Start the tests site, reset its database and seed it
#                    (npm run env:test:e2e:seed). Leaves a ready-to-`run` site.
#   run              Run Playwright against the running tests site (fails fast
#                    if it is down). Extra arguments go to `playwright test`.
#   reset            Reset just the database and seed it again.
#
# Focused subcommands leave the site UP; only the full pipeline stops it.
#
# SELECTION KNOBS (env vars; `run` only, the full pipeline clears them)
#   E2E_TARGET=<file>       Spec files to run: a path or part of a file name
#                           (Playwright's positional filter).
#   E2E_FILTER=<regex>      Test titles or tags to run (--grep).
#   E2E_SHOW_UI=1           Headed run: show the browser (--headed).
#
# The tests site URL comes from the port wp-env is actually running on, so
# worktrees on other ports work without extra setup.
#
# Before running this script you need a built plugin and Playwright's browser
# (`./node_modules/.bin/playwright install chromium`).
set -uo pipefail

# REPO SETTINGS: the only lines that differ between repos.
# - E2E_URL_VAR:   env var the Playwright config reads the tests site URL from.
# - E2E_CONFIG:    the Playwright config file.
# - E2E_SETUP_CMD: run by `setup` before starting the tests site, for example to
#                  fetch private plugins it mounts; empty when there is nothing to do.
# The seed itself is the repo's `npm run env:test:e2e:seed`.
E2E_URL_VAR=ACCOM_E2E_URL
E2E_CONFIG=tests/e2e/playwright.config.js
E2E_SETUP_CMD='bash ./tests/e2e/bin/install-plugins.sh'

cd "$(dirname "$0")/.." || exit 1

# Only emit ANSI colors to an interactive terminal so piped/CI logs stay clean.
if [ -t 1 ]; then
	GREEN='\033[0;32m'
	RED='\033[0;31m'
	GREY='\033[0;33m'
	CYAN='\033[0;36m'
	BOLD='\033[1m'
	NC='\033[0m'
else
	GREEN='' RED='' GREY='' CYAN='' BOLD='' NC=''
fi

die() {
	echo -e "${RED}Error: $*${NC}" >&2
	exit 1
}

require_cmds() {
	local missing=() c
	for c in "$@"; do
		command -v "$c" >/dev/null 2>&1 || missing+=("$c")
	done
	if [ ${#missing[@]} -gt 0 ]; then
		die "missing required tools: ${missing[*]}"
	fi
}

# Export the tests site URL (and its port, so later wp-env:test calls keep it)
# from the port wp-env is actually running on. Returns 1 when the site is down.
resolve_e2e_url() {
	local port
	port=$(npm run --silent wp-env:test -- status --json 2>/dev/null | node -p 'JSON.parse(require("fs").readFileSync(0, "utf8")).ports?.development || ""' 2>/dev/null)
	[ -n "$port" ] || return 1
	export WP_ENV_TESTS_PORT="$port"
	export "$E2E_URL_VAR=http://localhost:$port"
	echo -e "${GREEN}- Tests site: http://localhost:$port${NC}"
}

require_wpenv() {
	if ! resolve_e2e_url; then
		die "the tests site is not running; run 'npm run env:test:e2e:setup' first."
	fi
}

usage() {
	echo -e "${BOLD}Playwright E2E runner${NC}"
	echo -e "${GREY}Runs the suite against the wp-env tests site (.wp-env.test.json).${NC}"
	echo
	echo -e "${BOLD}USAGE${NC}"
	echo -e "  bash ./bin/acceptance.sh [ setup | run [playwright args] | reset | help ]"
	echo -e "  npm run env:test:e2e[ :setup | :run | :run:ui | :reset | :help ]"
	echo -e "  ${GREY}   plus wp-env lifecycle: env:test:start | env:test:stop | env:test:destroy${NC}"
	echo
	echo -e "${BOLD}SUBCOMMANDS${NC}"
	echo -e "  ${GREEN}(none)${NC}   Full pipeline: setup + whole suite headless, then stop the tests site."
	echo -e "  ${GREEN}setup${NC}    Start the tests site, reset and seed it. Ready to run."
	echo -e "  ${GREEN}run${NC}      Playwright against a set-up site (fails fast if it is down)."
	echo -e "  ${GREEN}reset${NC}    Reset just the DB and seed it again."
	echo -e "  ${GREEN}help${NC}     Show this message."
	echo
	echo -e "${BOLD}SELECTION KNOBS${NC} ${GREY}(env vars; compose with 'run')${NC}"
	echo -e "  ${CYAN}E2E_TARGET${NC}=<file>     Spec files: a path or part of a file name."
	echo -e "  ${CYAN}E2E_FILTER${NC}=<regex>    Test titles or tags (--grep)."
	echo -e "  ${CYAN}E2E_SHOW_UI${NC}=1         Headed run: show the browser (--headed)."
	echo -e "  ${GREY}Anything after 'run' goes to playwright test, e.g. -- --project=serial or -- --debug.${NC}"
	echo
	echo -e "${BOLD}EXAMPLES${NC}"
	echo -e "  ${GREY}# Full run, as CI${NC}"
	echo -e "  npm run env:test:e2e"
	echo
	echo -e "  ${GREY}# Iterate: set up once, then run one spec file repeatedly${NC}"
	echo -e "  npm run env:test:e2e:setup"
	echo -e "  ${CYAN}E2E_TARGET${NC}=<file> npm run env:test:e2e:run"
	echo
	echo -e "  ${GREY}# Tagged tests, browser visible${NC}"
	echo -e "  ${CYAN}E2E_FILTER${NC}=@<tag> npm run env:test:e2e:run:ui"
	echo
	echo -e "${BOLD}GETTING STARTED${NC}"
	echo -e "  ${GREY}1. Build the plugin${NC}             see AGENTS.md"
	echo -e "  ${GREY}2. Install the browser${NC}          ./node_modules/.bin/playwright install chromium"
	echo -e "  ${GREY}3. Provision the site (once)${NC}    npm run env:test:e2e:setup"
	echo -e "  ${GREY}4. Run everything, headed${NC}       ${CYAN}npm run env:test:e2e:run:ui${NC}"
	echo -e "  ${GREY}   ...the site stays up, so re-run step 4 anytime; refresh the DB with npm run env:test:e2e:reset.${NC}"
}

phase_setup() {
	if [ -n "$E2E_SETUP_CMD" ]; then
		echo -e "${GREEN}- ${E2E_SETUP_CMD}${NC}"
		bash -c "$E2E_SETUP_CMD" || die "'${E2E_SETUP_CMD}' failed."
	fi
	echo -e "${GREEN}- Ensuring the tests site is running...${NC}"
	bash ./bin/env-start.sh || die "starting the tests site failed."
	resolve_e2e_url || die "could not read the tests site port from wp-env; did it start?"
}

phase_provision() {
	echo -e "${GREEN}- Resetting the database...${NC}"
	npm run --silent wp-env:test -- reset >/dev/null || die "resetting the tests site failed; is it running?"
	echo -e "${GREEN}- Seeding the tests site...${NC}"
	npm run --silent env:test:e2e:seed || die "seeding the tests site failed (npm run env:test:e2e:seed)."
}

phase_run() {
	local args=( test --config "$E2E_CONFIG" )
	[ -n "${E2E_TARGET:-}" ] && args+=( "$E2E_TARGET" )
	[ -n "${E2E_FILTER:-}" ] && args+=( --grep "$E2E_FILTER" )
	[ -n "${E2E_SHOW_UI:-}" ] && args+=( --headed )
	args+=( "$@" )
	echo -e "${GREEN}- playwright ${args[*]}${NC}"
	./node_modules/.bin/playwright "${args[@]}"
}

phase_stop() {
	npm run --silent wp-env:test -- stop
}

case "${1:-}" in
	setup)
		require_cmds npm node
		phase_setup
		phase_provision
		;;
	reset)
		require_cmds npm node
		require_wpenv
		phase_provision
		;;
	run)
		shift
		require_cmds npm node
		require_wpenv
		phase_run "$@"
		;;
	"")
		unset E2E_TARGET E2E_FILTER E2E_SHOW_UI
		require_cmds npm node
		phase_setup
		phase_provision
		phase_run
		status=$?
		phase_stop
		exit "$status"
		;;
	help | -h | --help)
		usage
		;;
	*)
		echo -e "${RED}Unknown subcommand: '$1'${NC}" >&2
		echo >&2
		usage >&2
		exit 1
		;;
esac
