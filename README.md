woocommerce-accommodation-bookings
====================

An accommodations add-on for the WooCommerce Bookings extension.

## NPM Scripts

WooCommerce Accommodation Bookings utilizes npm scripts for task management utilities.

`npm run build` - Runs the tasks necessary for a release. These include building JavaScript, SASS, CSS minification, and language files.

## Adding code

New code should go in classes following [the PSR-4 convention](https://www.php-fig.org/psr/psr-4/), in the `src` directory and with a root namespace of `Automattic\WooCommerceAccommodationBookings`.

Similarly, unit tests for classes in `src` should go in the `tests/phpunit/src` directory, with a root namespace of `Automattic\WooCommerceAccommodationBookings\Tests` and be named `<class being tested>Test`.
