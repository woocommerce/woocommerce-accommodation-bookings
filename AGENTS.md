# AGENTS.md — WooCommerce Accommodation Bookings

Guide for coding agents: repository contracts, daily development, and validation. Parent SWW workspace instructions remain authoritative.

## Project map

An accommodation product extends WooCommerce Bookings with nightly stays and check-in/out times. WooCommerce and WooCommerce Bookings must both be available; the dependency check lives in `includes/class-wc-accommodation-dependencies.php`.

The plugin header requires PHP 7.4+, WordPress 7.0+, and WooCommerce 11.1+. Check the header when those floors change. Use `nvm use` for the Node version pinned in `.nvmrc`; `package.json` records the Node requirement and the pinned pnpm version. Keep syntax compatible with the supported PHP floor.

| Path | Purpose |
| --- | --- |
| `woocommerce-accommodation-bookings.php` | Bootstrap and version constant. |
| `includes/class-wc-accommodation-bookings-plugin.php` | Main plugin hooks, assets, and version-gated installation. |
| `includes/class-wc-product-accommodation-booking.php` | Accommodation product behavior on top of `WC_Product_Booking`. |
| `includes/admin/`, `includes/integrations/` | Admin screens and integration code. |
| `src/js/`, `src/css/` | Source assets; webpack writes generated files under `build/`. |
| `tests/phpunit/` | Isolated PHPUnit/WP_Mock tests. |
| `tests/isolated/` | Composer-only PHPUnit tests (the changelog formatter); no WordPress or WP_Mock. |
| `tests/e2e/` | Playwright specs, fixtures, and configuration. |
| `tests/playground/` | WordPress Playground preview behind the "Test the build" pull request comment. Its README covers the local run and where to turn on in-progress features. |
| `changelog/`, `tools/changelogger/` | Pending change files and the Jetpack Changelogger formatter that compiles them into `changelog.txt`. |

## Commands

Use the [SWW command contract](https://linear.app/a8c/document/sww-developer-command-and-build-lifecycle-contract-dfe7a270b983). Run `pnpm run lint:php` for PHPCS, `pnpm run phpstan` for static analysis and `pnpm run test:php` for the host PHP suite. Run `pnpm run lint:php:syntax` for syntax checks.

### Setup and assets

```bash
nvm use
pnpm install --frozen-lockfile
composer install
pnpm run build:dev       # Composer dependencies, webpack, and translations
pnpm run start:webpack   # Watch source assets
pnpm run lint:js         # Authored JavaScript and formatting (same as CI)
pnpm run lint:js-fix     # Fix lint and WordPress formatting findings
pnpm run lint:style
```

`webpack.config.js` maps `src/js/booking-form.js`, `src/js/writepanel.js`, and `src/css/frontend.scss` to `build/`. Edit these sources and rebuild; do not hand-edit generated assets. `pnpm run build` also packages a ZIP: its prebuild replaces `vendor`, and prearchive removes it. Run `composer install` again before PHP checks after packaging.

### PHP checks

```bash
vendor/bin/phpcs       # Coding standards from phpcs.xml.dist
pnpm run phpcompat     # Builds/checks release ZIP; run composer install afterward
```

CI uses `composer check:php` for the full-source PHP syntax and PHPCS baseline checks. See `DEVELOPER.md` for the PHP 8.4 tooling setup. Run the same command locally; `composer lint:phpcs` shows the full inventory, including existing violations. The compatibility script uses the separate `phpcs-compat.xml.dist` ruleset.

PHPStan (`composer phpstan`) also needs the stubs of the plugins Accommodation Bookings integrates with. They come from the public `woocommerce/php-stubs` package, installed separately:

```bash
composer install --working-dir=tools/php-stubs
```

`phpstan.neon.dist` includes one `.neon` per plugin. WooCommerce Bookings is pinned to the minimum in `WC_Accommodation_Dependencies`: when you raise it, move its pin too. The PHPStan job runs `php tools/php-stubs/vendor/woocommerce/php-stubs/check-pins.php`, which fails until they match (the `# minimum:` comment above the pin says where to read it). `tools/php-stubs/composer.lock` is not committed, so CI always installs the newest generated stubs; locally, `composer update --working-dir=tools/php-stubs` picks them up.

### Unit tests

```bash
composer test:php
composer test:isolated   # tests/isolated: changelog formatter round-trip, no WordPress
```

`phpunit.xml` loads `tests/phpunit/bootstrap.php`, which boots WP_Mock, defines a stub `WC_VERSION`, and loads `WC_Accommodation_Booking`. This is an isolated suite, not a full WordPress/WooCommerce/Bookings integration environment. Use it for supported isolated cases and Playwright or manual integration checks for behavior involving Bookings products, dates, availability, cart, or orders.

These suites and the integration suite (`tests/integration/README.md`, with its own WordPress install and database) run on the host, not on wp-env, so there is no `env:test:unit`.

### wp-env

wp-env (requires Docker) runs two sites, each from its own config: the **development** site (`.wp-env.json`, `http://localhost:$WP_ENV_PORT/`, default 8888) for manual testing, and the **tests** site (`.wp-env.test.json`, `http://localhost:$WP_ENV_TESTS_PORT/`, default 8889) for E2E. Every `env:*` script follows these variables; in a git worktree, export other ports before any `env:*` command (see [Worktrees](#worktrees)). `ACCOM_E2E`, `wp-cli.yml` and the E2E mu-plugins are on the tests site only.

A `.wp-env.override.json` left by an older e2e run merges into the development site; delete it if you didn't write it.

`pnpm run wp-env:test <command>` runs a raw wp-env command against the tests site (`--config .wp-env.test.json` plus the tests port), for example `pnpm run wp-env:test run cli wp plugin list`. Plain `./node_modules/.bin/wp-env` targets the development site.

```bash
pnpm run env:start          # Start the development site
pnpm run env:stop           # Stop its containers (preserves data)
pnpm run env:destroy        # Remove its containers and data
pnpm run env:test:start     # Start the tests site (E2E)
pnpm run env:test:stop      # Stop its containers (preserves data)
pnpm run env:test:destroy   # Remove its containers and data
```

`pnpm run env:test:e2e:setup` downloads the latest private `woocommerce-bookings.zip` and `woocommerce-bookings-availability.zip` into the checkout root with `ORG_DOWNLOADS`, falling back to the GitHub CLI login (`gh auth token`). To test another Bookings version, put its release ZIP there yourself (for example `gh release download <tag> --repo woocommerce/woocommerce-bookings --pattern woocommerce-bookings.zip --dir .`), then run `pnpm run env:test:start` and `pnpm run env:test:e2e:reset`, since `setup` downloads the latest again. State the version tested.

### Development Site

Requires a running development site (`pnpm run env:start`).

```bash
pnpm run env:dev:seed   # Seed a browsable store (runs once)
pnpm run env:dev:reset   # Wipe the development site and seed it again
```

- Logins: `admin` / `password`, `customer` / `password`.
- Seeds store settings, Dummy payments, shipping, tax and WooCommerce sample products. When `woocommerce-bookings.zip` is in the checkout root, it also installs Bookings and adds one accommodation product, "Sea View Room"; without it, the seed skips both.

### E2E Tests

The Playwright suite runs against the tests site; logins `admin` / `password`, `customer` / `password`. First build the plugin (`pnpm run build:dev`) and install the browser (`./node_modules/.bin/playwright install chromium`).

```bash
pnpm run env:test:e2e:setup                        # Once: download the Bookings plugins, start, reset and seed the tests site (leaves it up)
E2E_TARGET=product pnpm run env:test:e2e:run       # Inner loop: spec files matching a path or name
E2E_FILTER=@foundational pnpm run env:test:e2e:run # Tests whose title or tag matches (--grep)
pnpm run env:test:e2e:run:ui                       # Same, with the browser visible
pnpm run env:test:e2e:reset                        # Reset and re-seed just the DB
pnpm run env:test:e2e                              # Full pipeline, as CI (setup, whole suite, stop)
pnpm run env:test:e2e:help                         # All subcommands, E2E_* knobs and examples
```

- `bin/acceptance.sh` exports the tests site's URL as `ACCOM_E2E_URL` for `tests/e2e/config/index.js`; arguments after `run` go to `playwright test` (for example `pnpm run env:test:e2e:run --debug`).
- The tests site is seeded by `pnpm run env:test:e2e:seed` (`tests/e2e/bin/initialize.sh`: Bookings and Bookings Availability from their ZIPs, users, pages, store settings and the E2E mu-plugins). It fails until `woocommerce-bookings.zip` and `woocommerce-bookings-availability.zip` are in the checkout root.
- The regression journeys (`pnpm run test:e2e:reset`, then `pnpm run test:e2e:regression`) are described in `tests/e2e/README.md`.
- Extend the existing Playwright specs in `tests/e2e/specs/` and report which scenarios ran. A foundational or focused run is not full-suite coverage; identify behavior that still needs manual verification.

### Worktrees

Each concurrently running worktree needs a unique port pair, or its wp-env containers collide
with the main checkout or another worktree. Export them once per shell, before any `env:*` command:

```bash
export WP_ENV_PORT=8890 WP_ENV_TESTS_PORT=8891
pnpm run env:start
pnpm run env:test:start
```

Site URLs follow the ports. `bin/acceptance.sh` reads the tests site's port from wp-env and exports it
as `ACCOM_E2E_URL`, so worktrees can run E2E side by side. Re-export the ports in every new shell:
`env:test:e2e:run` and `env:test:e2e:reset` find the running port by themselves, but `env:test:start` and
`env:test:e2e:setup` without them move the tests site to port 8889. Destroy both sites before removing
the worktree (the semicolon is deliberate: a site that was never started exits 1):

```bash
pnpm run env:destroy --force; pnpm run env:test:destroy --force    # with the same ports exported
```

### Changelog

`changelog.txt` is **generated**. Never edit it by hand. Each pull request drops a change file
into `changelog/` instead, and [Jetpack Changelogger](https://github.com/Automattic/jetpack-changelogger)
compiles them into `changelog.txt` at release time.

```bash
pnpm run changelog add          # Interactive: significance, type, and the entry
pnpm run changelog validate     # Check every change file under changelog/
pnpm run changelog:check        # What CI runs: this branch has a valid change file
```

`changelog:check` also counts a change file that is only staged or still untracked, and says
so in its listing. CI diffs commits, so an uncommitted change file is one CI will never see.

`changelog add` names the file after the current git branch. Commit it with the rest of the
pull request:

```text
Significance: patch
Type: fix

Validate person-record ownership and edit permissions before saving accommodation person types.
```

| Field | Values | Notes |
| --- | --- | --- |
| `Significance` | `patch`, `minor`, `major` | Only `patch` may have an empty entry. Versions are set at release time, so this does not pick the next version. |
| `Type` | `add`, `fix`, `tweak`, `dev` | Becomes the `* Fix - ...` prefix in `changelog.txt`. Entries are grouped by type in that order. |
| Entry | One line | Multi-line entries are rejected: every line after the first is re-read as its own entry when the changelog is next parsed. Put extra detail in a `Comment:` header instead, which is not compiled into `changelog.txt`. |

The `Changelog / Check changelog` CI job requires an added change file on every pull request.
Label the pull request **`no changelog`** for changes that need no entry (CI, tooling, docs). The
label waives the requirement, not the format: a change file added anyway is still validated.
Release branches and Dependabot pull requests are skipped entirely.

The formatter that preserves the `changelog.txt` format lives in `tools/changelogger/`, and is
configured under `extra.changelogger` in `composer.json`.

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

These safeguards complement the repository-specific guidance. In the SWW workspace, the parent `AGENTS.md` remains authoritative for approvals, GitHub writes, and Linear workflow. Local instructions do not relax it.

### Compatibility and extension contracts

- Preserve existing public classes, interfaces, functions, methods, constants, signatures, hooks, hook timing, CSS classes, externally consumed file paths, templates, saved markup, and persisted formats. Assume unseen consumers in extensions, themes, and merchant snippets. Treat changes to any exposed surface as high-risk: state what changes, who could consume it, and why it is safe or how consumers can migrate in the PR description. When in doubt, assume the surface is exposed. If that impact cannot be established, stop and flag it for review before changing it.
- Deprecate instead of removing or renaming a public contract in place. Mark the old symbol `@deprecated` and keep it working alongside its replacement for a migration window. Append hook arguments; do not remove or reorder existing ones, or change when or whether a hook fires without assessing consumers. Retire hooks through `do_action_deprecated()` or `apply_filters_deprecated()`.
- Adding a required interface method breaks existing implementers and must be flagged explicitly. Prefer a compatible concrete-class addition, a separate interface, or a default implementation in an existing abstract base where that fits the extension contract. Removing an interface requirement does not make an implementation's extra method invalid, but it changes the contract available to consumers. Assess both callers and implementers.
- Public and protected overrides are contracts, including whether they run. A fast path that skips an overridable method can disable third-party behavior without changing a signature. Preserve those calls or treat the change as breaking.
- Adding or tightening parameter/return types can reject previously accepted values or break subclasses. Check actual inputs, including `null`, `false`, empty values from metadata, and numeric-string IDs; PHP still coerces some scalar values in weak mode. Adding `declare(strict_types=1)` changes scalar checks on calls made from that file. Tightening a comparison to `===` or adding a strict `instanceof` check can also reject values that shipped code accepted.
- Do not add parameter or return types to filter callbacks, or parameter types to action callbacks. Validate values in the body before passing them to typed code. A filter must preserve an unexpected value unchanged rather than discard another extension's customization. Action return values are ignored.
- Registered script/style handles are public contracts, including handles registered incidentally. Preserve old handles during renames as aliases depending on the new handle; do not load the same file twice.
- Guard global and lifecycle dependencies in admin, REST, CLI, cron, AJAX, webhook, and frontend contexts. Do not assume `$post`, `$wp_query`, a session, or a cart exists. Use `function_exists()` / `class_exists()` for optional symbols, `isset()` for variables, and `did_action()` for lifecycle state. Verify that `WC()` and the required component are initialized before dereferencing them.
- Account for multisite storage: site versus network options (`get_option()` / `get_site_option()`), per-site tables, roles, capabilities, and upload paths. For changes that read or write site state, state whether multisite behavior was verified and report when it was not tested.
- Support subdirectory installs, relocated `wp-content`, and reverse proxies. Derive paths and URLs with WordPress APIs such as `plugins_url()`, `plugin_dir_path()`, and `wp_upload_dir()`; preserve the distinction between `home_url()` and `site_url()`.

### Upgrades and persistent data

When a change introduces or alters persistent state:

- Add a stored version and a version-gated migration reachable by existing installs. Fresh-install setup alone is insufficient. Never edit or reuse an already-shipped migration version.
- Make migrations idempotent and batch large updates, using Action Scheduler where appropriate. New code must read both old and new formats until the migration completes, including requests before cron runs. A synchronous update over an unbounded table can time out on a large store.
- Preserve compatibility with the previous release after migration so rolling back does not fatal or corrupt data. Retain readable old formats for a transition period.
- Do not silently change defaults for existing stores; gate new defaults to new installs.
- Preserve cron/action names with queued jobs and stored option/meta keys, or provide a migration and transition path. Do not assume removing code removes stored data; keep its read path or clean it up through a migration that preserves rollback compatibility.

### Core APIs, security, and defensive coding

Use WordPress/WooCommerce APIs and existing repository abstractions before adding helpers. Hand-written replacements can lose HPOS compatibility, filters, caching, and theme overrides.

| Instead of | Use |
| --- | --- |
| `curl_*` or `file_get_contents()` on a URL | `wp_remote_get()`, `wp_remote_post()` |
| Hand-built database queries | `wc_get_orders()`, `wc_get_products()`, `WP_Query`; use `$wpdb->prepare()` when direct SQL is required |
| Direct product or order metadata reads | The relevant WooCommerce CRUD getters and data stores |
| Manual price, decimal, or date formatting | `wc_price()`, `wc_format_decimal()`, `wc_get_price_to_display()`, `date_i18n()` |
| Ad-hoc statics or options used as a cache | Transients, `wp_cache_*`, `WC_Cache_Helper` |
| Custom `wp_cron` plumbing | Action Scheduler |
| Custom regex or `strip_tags()` sanitizing | `wc_clean()`, `sanitize_text_field()`, `wp_kses_post()`, `absint()` |

Build on existing extension points such as `WC_Data`, `WC_Data_Store_WP`, `WC_Settings_Page`, `WC_Integration`, `WC_Email`, `WP_List_Table`, and `WP_REST_Controller`, or the repository's own base classes. Check existing helpers before adding a parallel implementation.

- Validate filter results before indexing or passing them to typed APIs, including filterable WooCommerce helpers such as `wc_get_image_size()`. Use a meaningful supported default; an arbitrary `0`, `''`, or `[]` can break image dimensions, prices, or quantities.
- Check optional methods with `method_exists()` and optional functions/classes with `function_exists()` / `class_exists()` across supported versions. Branch on `is_wp_error()` explicitly; `WP_Error` is truthy. Handle or propagate a returned error rather than silently discarding it. Validate array/object shapes before accessing keys that may be absent; use `isset()` or `array_key_exists()` as appropriate when `null` is meaningful.
- Before changing filter registrations, inspect `has_filter()` and retain the callback and priority. Restore only state this operation changed; cleanup must not remove registrations owned by the caller or restore state on a path that never changed it.
- Require authorization/capability checks such as `current_user_can()` for state changes and appropriate nonce checks (`check_admin_referer()`, `check_ajax_referer()`, `wp_verify_nonce()`) for cookie-authenticated requests. Admin location alone is not protection. REST routes need a real `permission_callback`, never `__return_true` on a write, plus argument `validate_callback` / `sanitize_callback` definitions.
- Apply `wp_unslash()` to slash-escaped WordPress input, such as `$_GET`, `$_POST`, and `$_REQUEST`, before sanitizing with the appropriate API. Do not unslash already-decoded REST/JSON values again. Escape at output for its context (`esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`); escaping at assignment before concatenation does not protect the final output. Prepare interpolated SQL with `$wpdb->prepare()`; `%i` supports identifiers on WordPress 6.2+, and identifiers still need an allowlist where appropriate.
- Do not pass untrusted input to raw `unserialize()` or allow untrusted objects to be instantiated; use an existing safe decoder where the format requires one. Do not evaluate input as code, construct callables from user input, or allow unrestricted uploads. Use `wp_safe_redirect()` for user-influenced redirects and keep secrets and personal data out of source and logs.
- Avoid unbounded queries/updates, repeated queries in loops, `'posts_per_page' => -1` on unbounded sets, and `meta_query` on an unindexed key over a large table. Keep expensive work off unconditional `init` / `plugins_loaded` paths when it belongs behind a condition, cache, or admin guard. Reuse existing caches, invalidate them on writes, and batch expensive work with a bounded memory footprint.

### Validation and change scope

- Keep changes focused; preserve existing conventions and supported runtimes. Edit the actual source files, not generated output, using the repository's asset map.
- For behavior fixes, reproduce the failure at the relevant test layer and verify the result. Cover the happy path and boundary/error inputs, `null` / `false` / empty values, filtered values, existing data, and affected integrations. Give migrations, capability/nonce checks, and price/quantity calculations particular attention because silent failures are costly.
- Extend an existing test instead of adding a near-duplicate. Use meaningful assertions: a test that only repeats a mock's configured return value, or still passes when the fix is reverted, does not establish the fix. If the repository has no fixture for a layer, report it as untested instead of writing a hollow test.
- Use the repository's existing test frameworks. Run checks appropriate to the changed files and report failures, skipped checks, and untested layers accurately. Documentation changes need command/path and formatting verification, not invented runtime tests.
- Use isolated worktrees and environment ports where the repository supports them. Follow Linear-generated branch names when working from an issue. Keep commits small and stage only intended paths; never bypass hooks to force a commit through.
- Follow the current PR template. Changelog exemptions and milestone automation differ by repository: describe the actual workflow and missing automation rather than inventing checkboxes, changing labels without authorization, or claiming checks passed.

## Contribution and tooling notes

- Follow the parent SWW instructions for Git/Linear work and the repository's `.github/PULL_REQUEST_TEMPLATE.md`. Use the global authenticated `gh` CLI for GitHub operations; commit, push, and draft-PR creation each need their own authorization.
- **Always add a changelog file.** Run `pnpm run changelog add` and commit the file it creates under `changelog/`, one per PR. Never edit `changelog.txt` directly - it is compiled from the change files at release time. See [Changelog](#changelog).
- The template has no auto-assign-milestone checkbox. Report that missing control instead of inventing a checked box. The release workflows require and roll over the release milestone, but pull requests are not assigned to it automatically.
- QIT runs twice a week: four tests on the stable versions in Cron CI on Mondays, and the full set on the pre-release versions in Cron QIT on Wednesdays. QIT PHPStan stays off because the repo's own PHPStan (level 8) is the gate.
- Releases are started via the `Start Release` GitHub Actions workflow (Actions UI, or locally with `bin/release_start.sh [X.Y.Z] [--wp A.B] [--wc C.D]`, which dispatches it). It bumps versions, compiles `changelog/` into `changelog.txt` and copies the release's entries into the `readme.txt` changelog on a `release/X.Y.Z` branch, then opens a release PR against `trunk`. Run `Publish release` from `trunk` with the release branch as input. It builds the ZIP, tags the release, deploys that ZIP to WordPress.org through `woocommerce/woo-product-deploy` (`config.wp_org_slug` in `package.json` names the plugin slug), and rolls the milestone over. Building a ZIP does not authorize starting a release.
- **Set `@version x.x.x` on the class docblock** when modifying a class, trait, or interface. The release bump replaces `x.x.x` with the released version. Enforced in CI by `check-version.yml`. Review rules for PHP DocBlock version tags live in `.github/instructions/php.instructions.md`.
