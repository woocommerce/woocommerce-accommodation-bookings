# AGENTS.md — WooCommerce Accommodation Bookings

Guide for coding agents: repository contracts, daily development, and validation. Parent SWW workspace instructions remain authoritative.

## Project map

An accommodation product extends WooCommerce Bookings with nightly stays and check-in/out times. WooCommerce and WooCommerce Bookings must both be available; the dependency check lives in `includes/class-wc-accommodation-dependencies.php`.

The plugin header requires PHP 7.4+, WordPress 6.9+, and WooCommerce 10.9+. Check the header when those floors change. Use `nvm use` for the Node version pinned in `.nvmrc`; `package.json` records Node/npm engine requirements. Keep syntax compatible with the supported PHP floor.

| Path | Purpose |
| --- | --- |
| `woocommerce-accommodation-bookings.php` | Bootstrap and version constant. |
| `includes/class-wc-accommodation-bookings-plugin.php` | Main plugin hooks, assets, and version-gated installation. |
| `includes/class-wc-product-accommodation-booking.php` | Accommodation product behavior on top of `WC_Product_Booking`. |
| `includes/admin/`, `includes/integrations/` | Admin screens and integration code. |
| `src/js/`, `src/css/` | Source assets; webpack writes generated files under `build/`. |
| `tests/phpunit/` | Isolated PHPUnit/WP_Mock tests. |
| `tests/e2e/` | Playwright specs, fixtures, and configuration. |

## Commands

### Setup and assets

```bash
nvm use
npm ci
composer install
npm run build:dev       # Composer dependencies, webpack, and translations
npm run start:webpack   # Watch source assets
npm run lint:js
npm run lint:style
```

`webpack.config.js` maps `src/js/booking-form.js`, `src/js/writepanel.js`, and `src/css/frontend.scss` to `build/`. Edit these sources and rebuild; do not hand-edit generated assets. `npm run build` also packages a ZIP: its prebuild replaces `vendor`, and prearchive removes it. Run `composer install` again before PHP checks after packaging.

### PHP checks

```bash
vendor/bin/phpcs       # Coding standards from phpcs.xml.dist
npm run phpcompat     # Separate PHP compatibility ruleset
```

CI uses `vendor/bin/phpcs-changed -s --git --git-base origin/trunk` with the changed PHP paths (`.github/workflows/phpcs.yml`). Supply the paths being reviewed for an equivalent focused local run; a full PHPCS run can include existing violations. The compatibility script uses `phpcs-compat.xml.dist`, not the coding-standard ruleset. Run checks for the changed files and report their scope.

### Unit tests

```bash
composer test
```

`phpunit.xml` loads `tests/phpunit/bootstrap.php`, which boots WP_Mock, defines a stub `WC_VERSION`, and loads `WC_Accommodation_Booking`. This is an isolated suite, not a full WordPress/WooCommerce/Bookings integration environment. Use it for supported isolated cases and Playwright or manual integration checks for behavior involving Bookings products, dates, availability, cart, or orders.

### End-to-end tests and worktrees

Docker is required. Before the first `env:start`, put the Bookings release ZIP in the worktree root: `tests/e2e/bin/initialize.sh` installs `woocommerce-bookings.zip` from the mapped plugin directory. With authorized access, use the global CLI:

```bash
gh release download --repo woocommerce/woocommerce-bookings --pattern 'woocommerce-bookings.zip' --dir .
```

Use the Bookings version needed for the task and state the version tested. The existing `env:install-plugins` script uses a separate bot token; the CLI download avoids that local requirement.

```bash
npx playwright install chromium
npm run env:start              # Starts wp-env and runs postenv:start fixtures
npm run test:e2e-foundational   # Existing @foundational scenarios
npm run test:e2e -- --grep 'scenario title'
npm run test:e2e                # Full Playwright suite
npm run env:stop                # Preserve environment data
npm run env:destroy             # Remove this environment's containers and volumes
```

Run these from the active worktree after installing dependencies and building the plugin. The fixtures in `tests/e2e/bin/initialize.sh` create users, pages, and store settings; use a disposable test environment. Extend the existing Playwright specs in `tests/e2e/specs/` and report which scenarios ran. A foundational or focused run is not full-suite coverage.

`tests/e2e/config/index.js` fixes the base URL to `http://localhost:8889`. Run one default-port wp-env environment at a time across worktrees; stop the other worktree's environment from that worktree before starting this one. Changing wp-env ports alone does not update Playwright's URL. Keep each worktree's dependencies and fixtures separate, and destroy its test environment before removing it. Configurable parallel ports are follow-up tooling work.

## Repository compatibility contracts

Public and protected methods on the global `WC_Accommodation_*` and `WC_Product_Accommodation_Booking*` classes are extension contracts. Protected methods can be overridden by subclasses; they are not callable directly by arbitrary external code. Apply the shared compatibility rules below to every exposed surface.

**As a producer of public API.** This plugin exposes a surface that third parties consume:

- The `accommodation-booking` product type slug, the largest contract here. It is persisted as the `product_type` term on every accommodation product, and it derives the data store key `product-accommodation-booking`, the dynamic `woocommerce_accommodation-booking_add_to_cart` template hook, and the type checks that gate nearly every filter callback in the plugin. Renaming it orphans existing products on every installed site and is not possible without a data migration. Treat the `_wc_accommodation_booking_*` post meta keys the same way.
- The global-namespace `WC_Accommodation_*` and `WC_Product_Accommodation_Booking*` classes, including every `public` and `protected` method third-party code can call or override.
- The `woocommerce_accommodation_booking_*` and `woocommerce_accommodation_bookings_*` hooks this plugin fires.
- Registered script and style handles, such as `wc_accommodation_bookings_writepanel_js` on the product admin screens.

**As a consumer of upstream contracts.** This plugin is a guest inside WooCommerce Bookings: it subclasses `WC_Product_Booking` and `WC_Product_Booking_Resource`, hooks roughly twenty `woocommerce_bookings_*` filters, writes to Bookings' own tables, and enforces a minimum Bookings version in `WC_Accommodation_Dependencies::check_dependencies()`. Those are someone else's contracts. Verify behaviour against the installed Bookings version rather than assuming the contract is frozen, and treat raising the version floor as a breaking change for merchants below it, not a cleanup.

`WC_Product_Accommodation_Booking::get_check_times()` returns the result of `woocommerce_accommodation_booking_get_check_times`. Validate its value before using it as an `H:i` time. Bookings filters can also pass values reshaped by earlier callbacks; preserve other extensions' values when this plugin cannot handle them.

`WC_Accommodation_Bookings_Plugin::install()` runs on `shutdown`, including admin, REST, CLI, and cron contexts. Its version options are site-scoped; use the existing `WC_ACCOMMODATION_BOOKINGS_*` path/URL constants and avoid front-end global assumptions.

**Database migrations are one-shot and must survive a rollback.** `install()` in `WC_Accommodation_Bookings_Plugin` is this plugin's migration runner: data updates are gated on `version_compare()` against the stored `wc_accommodation_bookings_version` option, so a site that has updated past a gate never re-runs it. Gate a new migration on the exact version it ships in, and make sure a rollback to the previous release neither fatals nor corrupts - the old code will read the already-migrated data, and there is no down migration. Leave the old data shape readable for one release.

## Shared implementation safeguards

These safeguards complement the repository-specific guidance. In the SWW workspace,
the parent `AGENTS.md` remains authoritative for approvals, GitHub writes, and Linear
workflow. Local instructions do not relax it.

### Compatibility and extension contracts

- Preserve existing public symbols, signatures, hooks, hook timing, templates, and
  persisted formats. Assume unseen consumers in extensions, themes, and merchant
  snippets. State the compatibility impact in the PR when changing an exposed surface.
  If that impact cannot be established, stop and flag it for review before changing it.
- Deprecate instead of removing or renaming a public contract in place. Keep the old
  symbol working alongside its replacement for a migration window. Append hook
  arguments; do not remove or reorder existing ones. Retire hooks through
  `do_action_deprecated()` or `apply_filters_deprecated()`.
- Adding a required interface method breaks existing implementers. Removing an
  interface requirement does not make an implementation's extra method invalid, but
  it changes the contract available to consumers. Assess both callers and implementers.
- Public and protected overrides are contracts, including whether they run. A fast
  path that skips an overridable method can disable third-party behavior without
  changing a signature. Preserve those calls or treat the change as breaking.
- Adding or tightening parameter/return types can reject previously accepted values
  or break subclasses. PHP still coerces some scalar values in weak mode, so assess
  actual inputs rather than claiming every numeric string fails. Adding
  `declare(strict_types=1)` changes scalar checks on calls made from that file.
- Do not add parameter or return types to filter callbacks, or parameter types to
  action callbacks. Validate values in the body. A filter must preserve an unexpected
  value unchanged rather than discard another extension's customization.
- Registered script/style handles are public contracts. Preserve old handles during
  renames as aliases depending on the new handle; do not load the same file twice.
- Guard global and lifecycle dependencies in admin, REST, CLI, cron, AJAX, and frontend
  contexts. Check the required WooCommerce component before dereferencing it.
- Account for multisite storage and nonstandard install layouts. Derive paths and
  URLs with WordPress APIs. For state changes, report whether multisite was tested.

### Upgrades and persistent data

When a change introduces or alters persistent state:

- Add a version-gated migration reachable by existing installs. Fresh-install setup
  alone is insufficient. Never edit or reuse an already-shipped migration version.
- Make migrations idempotent and batch large updates. New code must read both old
  and new formats until the migration completes, including requests before cron runs.
- Preserve compatibility with the previous release after migration so rolling back
  does not fatal or corrupt data. Retain readable old formats for a transition period.
- Do not silently change defaults for existing stores; gate new defaults to new installs.
- Preserve cron/action names with queued jobs and stored option/meta keys, or provide
  a migration and transition path. Do not assume removing code removes stored data.

### Core APIs, security, and defensive coding

- Use WordPress/WooCommerce APIs and existing repository abstractions before adding
  helpers: HTTP APIs, CRUD/data stores, queries, templates, formatting, caches, and
  Action Scheduler. Preserve HPOS, filters, caching, and theme overrides.
- Validate filter results before indexing or passing them to typed APIs, including
  values from filterable WooCommerce helpers. Use a meaningful supported default,
  not an arbitrary zero or empty value.
- Check optional methods with `method_exists()` across supported WC versions. Exclude
  `WP_Error` explicitly; it is truthy. Validate array/object shapes before use.
- Before changing filter registrations, inspect `has_filter()` and retain the callback
  and priority. Restore only state this operation changed; cleanup must not remove
  registrations owned by the caller.
- Require authorization/capability checks for state changes and appropriate nonce
  checks for cookie-authenticated requests. REST routes need real permission and
  argument validation/sanitization callbacks. Admin location alone is not protection.
- Unslash then sanitize request data, escape at output for its context, and prepare
  interpolated SQL with `$wpdb->prepare()`; use an allowlist where needed for identifiers.
- Do not deserialize untrusted objects, evaluate input as code, construct callables
  from user input, or allow unrestricted uploads. Use safe redirects and keep secrets
  and personal data out of source and logs.
- Avoid unbounded queries/updates and repeated queries in loops. Reuse existing caches,
  invalidate them on writes, and batch expensive work with a bounded memory footprint.

### Validation and change scope

- Keep changes focused; preserve existing conventions and supported runtimes. Edit
  the actual source files, not generated output, using the repository's asset map.
- For behavior fixes, reproduce the failure at the relevant test layer and verify the
  result. Cover boundary/error inputs, filtered values, existing data, and affected
  integrations with meaningful assertions; do not merely mirror the implementation.
- Use the repository's existing test frameworks. Run checks appropriate to the changed
  files and report failures, skipped checks, and untested layers accurately. Documentation
  changes need command/path and formatting verification, not invented runtime tests.
- Use isolated worktrees and environment ports where the repository supports them.
  Follow Linear-generated branch names when working from an issue. Keep commits small
  and stage only intended paths; never bypass hooks to force a commit through.
- Follow the current PR template. Changelog exemptions and milestone automation differ
  by repository: describe the actual workflow and missing automation rather than
  inventing checkboxes, changing labels without authorization, or claiming checks passed.

## Contribution and tooling notes

- Follow the parent SWW instructions for Git/Linear work and the repository's `.github/PULL_REQUEST_TEMPLATE.md`. Use the global authenticated `gh` CLI for GitHub operations; commit, push, and draft-PR creation each need their own authorization.
- The current template keeps the changelog entry in the PR body, with `Add|Fix|Dev` prefixes. Its exemption label is `changelog: none`. In this workspace, record a docs-only exemption in the PR body; do not change GitHub labels without authorization. Keep the changelog heading so the template's fallback does not use the PR title as an entry.
- The template has no auto-assign-milestone checkbox. Report that missing control instead of inventing a checked box. Changelog-file and milestone automation remain separate alignment work.
- `.github/workflows/deploy.yml` uses the `Deploy Product` workflow and `woocommerce/woo-product-deploy`. Building a ZIP does not authorize dispatching a release. Keep the existing release tooling.
- Review rules for PHP DocBlock version tags live in `.github/instructions/php.instructions.md`. Ignore missing, incorrect, or placeholder `@version`/`@since` tags in review; continue following the configured coding standards when editing PHP.
