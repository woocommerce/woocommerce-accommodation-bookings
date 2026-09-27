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

```bash
nvm use
npm install
npm run build:dev
```

## npm scripts

```bash
# Development build
npm run build:dev      # Install composer deps, build JS/CSS, generate language files

# Watch mode
npm run start:webpack  # Rebuild JS/CSS on file changes

# Production build
npm run build          # Production build + language files + zip
npm run build:zip      # Release zip, as Release CI builds it

# Tests
npm run env:start      # Start the wp-env local test environment
npm run test:e2e       # Run all E2E tests with Playwright
npm run test:e2e-foundational    # Run only @foundational tagged tests
npm run test:e2e-debug           # Run E2E tests in debug mode

# Quality
npm run phpcompat      # Builds/checks release ZIP; run composer install afterward
npm run lint:js        # Authored JavaScript and formatting (same as CI)
npm run lint:style     # Stylelint on CSS/SCSS
```

## PHP unit tests

Install PHP 7.4 or newer and Composer, then run these commands from a fresh checkout:

```bash
composer install
composer test
composer test -- --filter testTimestampKeepsTheDate
```

This suite uses PHPUnit and WP_Mock. It does not need Docker, a database, WordPress,
WooCommerce, or a Bookings ZIP. If `vendor/bin/phpunit` is missing, run
`composer install` first. Mocked functions and the timezone reset between cases;
rerunning the suite needs no fixture cleanup.

To collect local line and branch coverage, install a PHP-compatible Xdebug driver
and enable its coverage mode:

```bash
XDEBUG_MODE=coverage composer test -- --path-coverage --coverage-html coverage/unit --coverage-text
```

PCOV can collect line coverage with `php -d pcov.enabled=1 vendor/bin/phpunit
--coverage-html coverage/unit --coverage-text`; it does not provide branch coverage.
An absent or disabled driver produces PHPUnit's coverage-driver warning.
`coverage/unit/index.html` includes unexecuted PHP in `includes/` and the plugin
entry point. Tests, mocks, dependencies and generated files are outside its source
scope. Coverage is optional and does not change the normal test command.

## Changelog

`changelog.txt` is generated. Never edit it by hand. Each pull request adds a change file under [`changelog/`](changelog/) instead, and [Jetpack Changelogger](https://github.com/Automattic/jetpack-changelogger) compiles them into `changelog.txt` at release time.

```bash
npm run changelog add          # Interactive: significance, type, and the entry
npm run changelog validate     # Check every change file under changelog/
npm run changelog:check        # What CI runs: this branch has a valid change file
```

The `Changelog / Check changelog` check requires an added change file on every pull request. Label the pull request `no changelog` for changes that need no entry (CI, tooling, docs).

## Release

Releases are started with the `Start Release` workflow and shipped by merging the release PR it creates.

Before starting a release, make sure that:

- Everything you want to ship has been merged into `trunk`, and each of those pull requests left a change file under [`changelog/`](changelog/).
- An open [milestone](https://github.com/woocommerce/woocommerce-accommodation-bookings/milestones) titled after the version (e.g. `1.3.13`) exists. The workflow refuses to start without one.

To start the release, run the [Start Release workflow](https://github.com/woocommerce/woocommerce-accommodation-bookings/actions/workflows/release-start.yml) from the Actions tab, or locally with:

```bash
bin/release_start.sh
```

Run it with no arguments to be prompted for the version and the WP/WC "tested up to" values (press enter to keep the current ones), or pass them directly: `bin/release_start.sh X.Y.Z --wp A.B --wc C.D`. The script dispatches the workflow, watches it, and prints the release PR URL when it's done.

On a `release/X.Y.Z` branch, the workflow bumps the version and tested-up-to headers, compiles the change files under `changelog/` into `changelog.txt` and deletes the ones it consumed, copies the new entries into the `readme.txt` changelog that WordPress.org shows, then opens a pull request against `trunk`. It also posts a comment on the PR comparing the changelog entries with the issues in the milestone. Review that comment to make sure nothing is missing.

While the release PR is open, `trunk` is under code freeze: the `Release Freeze / Check release freeze` required check fails on all other pull requests, and flips back automatically once the release PR is merged or closed.

A smoke test workflow runs on the release branch ([ci-release-smoke-test.yml](.github/workflows/ci-release-smoke-test.yml)), and the release PR goes through the regular PR CI and review like any other PR.

Merging the release PR into `trunk` triggers the release workflow ([ci-release.yml](.github/workflows/ci-release.yml)), which runs the test suites, builds the zip, creates the GitHub release and tag, and deploys the plugin to WordPress.org. Progress is posted in the `#team-somewherewarm-releases` Slack channel.

After a successful release, the workflow closes the released milestone and creates one for the next patch version (rename it if the next release will be a minor/major).

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
