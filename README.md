woocommerce-accommodation-bookings
====================

An accommodations add-on for the WooCommerce Bookings extension.

- [Product page](https://woocommerce.com/products/woocommerce-accommodation-bookings/)
- [Documentation](https://docs.woocommerce.com/document/woocommerce-accommodation-bookings/)

## Dependencies

- WooCommerce
- WooCommerce Bookings

## Development

### Prerequisites

- [Node.js 24](https://nodejs.org) (see `.nvmrc`; managed via [nvm](https://github.com/nvm-sh/nvm) or [fnm](https://github.com/Schniz/fnm))
- [Composer](https://getcomposer.org/doc/00-intro.md)

Docker is required to run the end-to-end test suite via `@wordpress/env`.

### Quick start

```bash
nvm use
npm install
composer install
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
npm run lint:css       # Stylelint on CSS/SCSS
```

## Known caveats

- Moderate-severity advisories remain in transitive dependencies of `@wordpress/env` (`@wp-playground`, `@php-wasm`). These packages are only used by the local test environment and never ship in the plugin.

## Compatibility

This extension is compatible with:
- [WooCommerce Blocks](https://woo.com/products/woocommerce-gutenberg-products-block/)
- [WooCommerce Payments](https://woocommerce.com/products/woopayments/)
