woocommerce-accommodation-bookings
====================

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

# Tests
npm run env:start      # Start the wp-env local test environment
npm run test:e2e       # Run all E2E tests with Playwright
npm run test:e2e-foundational    # Run only @foundational tagged tests
npm run test:e2e-debug           # Run E2E tests in debug mode

# Quality
npm run phpcompat      # PHP compatibility check
npm run lint:js        # ESLint on JS source
npm run lint:style     # Stylelint on CSS/SCSS
```

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
