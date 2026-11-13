# woocommerce-accommodation-bookings

An accommodations add-on for the WooCommerce Bookings extension.

- [Product page](https://woocommerce.com/products/woocommerce-accommodation-bookings/)
- [Documentation](https://docs.woocommerce.com/document/woocommerce-accommodation-bookings/)

## Dependencies

- WooCommerce
- [WooCommerce Bookings](https://woocommerce.com/products/woocommerce-bookings/)

## Getting started

### Prerequisites

- [Node.js 24](https://nodejs.org) (managed via [NVM](https://github.com/nvm-sh/nvm#installing-and-updating)): we recommend NVM to keep your Node version aligned with the development team. The repository contains an [`.nvmrc` file](.nvmrc) that pins the supported version.
- [PHP 8.4](https://www.php.net/manual/en/install.php): use for PHPCS and QIT tooling to match CI. The plugin still supports PHP 7.4+.
- [Composer](https://getcomposer.org/doc/00-intro.md): manages PHP dependencies and dev tooling.

Docker is required to run the end-to-end test suite via `@wordpress/env`.

### Quick start

Install the pnpm version pinned in `package.json`. With Corepack, run `corepack enable pnpm` after selecting the project’s Node version.

```bash
nvm use
pnpm install
pnpm run build:dev
```

## pnpm scripts

```bash
# Development build
pnpm run build:dev      # Install composer deps, build JS/CSS, generate language files

# Watch mode
pnpm run start:webpack  # Rebuild JS/CSS on file changes

# Production build
pnpm run build          # Production build + language files + zip
pnpm run build:zip      # Same build, with the zip moved to deploy/ (used by the release CI)

# Tests
pnpm run env:start                                 # Start the wp-env development site (see AGENTS.md for ports and worktrees)
pnpm run env:dev:seed                              # Seed it with a browsable store
pnpm run env:test:e2e:setup                        # Download the Bookings plugins, start and seed the wp-env tests site
pnpm run env:test:e2e                              # Full Playwright E2E pipeline: set up, run, stop
E2E_FILTER=@foundational pnpm run env:test:e2e:run # Run @foundational-tagged tests on a set-up tests site
pnpm run test:e2e-debug                            # Run E2E tests in debug mode
pnpm run env:test:e2e:help                         # All E2E subcommands and knobs

# Quality
pnpm run phpcompat      # Builds/checks release ZIP; run composer install afterward
pnpm run lint:js        # Authored JavaScript and formatting (same as CI)
pnpm run lint:style     # Stylelint on CSS/SCSS
```

## PHP unit tests

Install PHP 7.4 or newer and Composer, then run these commands from a fresh checkout:

```bash
composer install
composer test:php
composer test:php -- --filter testTimestampKeepsTheDate
```

This suite uses PHPUnit and WP_Mock. It does not need Docker, a database, WordPress,
WooCommerce, or a Bookings ZIP. If `vendor/bin/phpunit` is missing, run
`composer install` first. Mocked functions and the timezone reset between cases;
rerunning the suite needs no fixture cleanup.

To collect local line and branch coverage, install a PHP-compatible Xdebug driver
and enable its coverage mode:

```bash
XDEBUG_MODE=coverage composer test:php -- --path-coverage --coverage-html coverage/unit --coverage-text
```

PCOV can collect line coverage with `php -d pcov.enabled=1 vendor/bin/phpunit
--coverage-html coverage/unit --coverage-text`; it does not provide branch coverage.
An absent or disabled driver produces PHPUnit's coverage-driver warning.
`coverage/unit/index.html` includes unexecuted PHP in `includes/` and the plugin
entry point. Tests, mocks, dependencies and generated files are outside its source
scope. Coverage is optional and does not change the normal test command.

## Deployment

Run `Start Release` to prepare the release and `Publish release` to ship it.

Before starting a release, make sure that:

- Everything you want to ship has been merged into `trunk`, and each of those pull requests left a change file under [`changelog/`](https://github.com/woocommerce/woocommerce-accommodation-bookings/tree/trunk/changelog).
- An open [milestone](https://github.com/woocommerce/woocommerce-accommodation-bookings/milestones) titled after the version (e.g. `1.4.0`) exists. The workflow refuses to start without one.

To start the release, run the [Start Release workflow](https://github.com/woocommerce/woocommerce-accommodation-bookings/actions/workflows/release-start.yml) from the Actions tab, or locally with:

```bash
bin/release_start.sh
```

Run it with no arguments to be prompted for the version and the WP/WC "tested up to" values (press enter to keep the current ones), or pass them directly: `bin/release_start.sh X.Y.Z --wp A.B --wc C.D`. The script dispatches the workflow, watches it, and prints the release PR URL when it's done.

On a `release/X.Y.Z` branch, the workflow bumps the version and tested-up-to headers, compiles the change files under `changelog/` into `changelog.txt` and deletes the ones it consumed, copies the release's entries into the `readme.txt` changelog, then opens a pull request against `trunk`. It also posts a comment on the PR comparing the changelog entries with the issues in the milestone - review that comment to make sure nothing is missing.

A smoke test workflow runs on the release branch ([ci-release-smoke-test.yml](https://github.com/woocommerce/woocommerce-accommodation-bookings/blob/trunk/.github/workflows/ci-release-smoke-test.yml)). Review the release PR and wait for its required checks. Normal PRs can continue to merge into `trunk`. Assign changes merged after the branch cut to the next milestone that Start Release opens.

Run [Publish release](https://github.com/woocommerce/woocommerce-accommodation-bookings/actions/workflows/ci-release.yml) from `trunk` with the release branch name, or run:

```bash
gh workflow run ci-release.yml --ref trunk -f branch=release/X.Y.Z
```

The workflow tests, builds, and tags the branch head. It deploys the tested ZIP to WordPress.org, then merges the release PR. GitHub deletes the release branch after merging. The workflow also closes the shipped milestone and reports progress in `#team-somewherewarm-releases`.

If the deployment fails or GitHub blocks the merge, resolve the cause and re-run only the failed jobs. If the PR head changed or there is a conflict, finish the merge through the PR after checking the deployed tag and preserving new changelog fragments. If a release needs source changes after its version was tagged, coordinate recovery before starting another run. Keep the released tag and ZIP intact for any version that reached the store.

## AI code reviews

[CodeRabbit](https://docs.coderabbit.ai/platforms/github-com) requires its GitHub App
to have access to this repository. A WooCommerce organization owner must grant that
access; committing [`.coderabbit.yaml`](.coderabbit.yaml) does not install the app.

Once enabled, CodeRabbit reviews non-draft PRs targeting the default branch when
opened or marked ready, then reviews new commits. The configuration follows the
default branch if it is renamed. Reviews use the repository's agent guidance and
pause after five reviewed commits to limit repeated reviews.

Use these [PR comment commands](https://docs.coderabbit.ai/guides/commands):

- `@coderabbitai review`: review changes since the last review.
- `@coderabbitai full review`: review the whole PR again.
- `@coderabbitai pause`: pause automatic reviews.
- `@coderabbitai resume`: resume automatic reviews.

Add `@coderabbitai ignore` to the PR description to skip automatic reviews for that
PR. Human review and CI checks still apply.

## Compatibility

This extension is compatible with:

- [WooCommerce Blocks](https://woo.com/products/woocommerce-gutenberg-products-block/)
- [WooCommerce Payments](https://woocommerce.com/products/woopayments/)

### Database integration tests

See [the integration guide](tests/integration/README.md) for isolated setup,
selectable dependency versions, reset, full/focused runs and optional PHP coverage.
This suite uses real WordPress, WooCommerce, Bookings and Product Add-ons.

### Browser persistence regressions

See [tests/e2e/README.md](tests/e2e/README.md) for isolated setup, full and focused
commands, supported checkout modes and fixture reset.
