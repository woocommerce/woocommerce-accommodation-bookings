# Real dependency integration tests

This suite is separate from the WP_Mock suite. It boots WordPress, WooCommerce,
Bookings and Product Add-ons, then checks database values after reloading objects.
The shared `fixtures.php` room factory can also be loaded with WP-CLI for browser
fixtures. No live calendar, payment or other remote services are used.

Install locked Composer dependencies. Use PHP with mysqli, curl, Subversion,
unzip and authenticated global `gh`; the latter downloads the licensed test
plugins from their repositories. Provision an empty private directory and a
**dedicated test database**. WordPress replaces its test tables on every run:

```bash
export ACCOM_TEST_DB_NAME=accommodation_test_current
export ACCOM_TEST_DB_USER=root
export ACCOM_TEST_DB_PASSWORD=your_local_test_password
export ACCOM_TEST_DB_HOST=127.0.0.1:3306
./tests/bin/setup-integration.sh /tmp/accommodation-current 7.0 11.1.0 3.10.0 8.4.2
./tests/bin/run-integration.sh /tmp/accommodation-current
./tests/bin/run-integration.sh /tmp/accommodation-current --filter test_historical_migrations
```

The run command resets all fixture rows by reinstalling the test prefix and each
case's transaction rolls back products, bookings, orders, refunds and options.
Do not point this suite at a store database. Choose a new directory and database
for each supported version combination. Replace `3.10.0` with `2.2.8` to exercise
the older flat block list; current Bookings uses timestamp-keyed block counts.
The runner prints installed versions. Missing required plugins fail at startup.
Lifecycle cases run against both HPOS and legacy order storage. They warm and
read the real slot cache before payment, then check persisted booking status and
the availability datastore query after cancellation/refund. Bookings may retain
its options cache until the request ends; the browser cancellation journey checks
restored capacity in the next customer request. The suite does not flush caches
to make that same-request behavior pass.

The payment-status matrix also runs on Bookings 2.2.8, where Bookings' completion callback excludes accommodation products and Accommodation supplies their completion behavior; Bookings 3.0.0+ handles these products itself. Bookings versions before 3.10.0 do not enforce the cancellation cutoff or disabled cancellation setting in the native request handler. A full 2.2.8 run has four expected failures in that denial matrix, across both order stores. Those assertions remain active to expose this historical dependency gap; use current Bookings for the complete passing suite.

For an existing isolated WordPress test installation, set `WP_TESTS_DIR`, `WC_DIR`,
`BOOKINGS_DIR` and `ADDONS_DIR` to its actual paths and run
`vendor/bin/phpunit -c phpunit-integration.xml`. The repository's existing wp-env
browser environment remains available; use its private port override and load the
same fixture factory after activating the dependencies.

Historical pricing is the Accommodation 1.1.2 `_wc_booking_pricing` shape with
`override_block` and `_wc_booking_base_cost`; 1.1.3 converts each override to a
relative nightly adjustment. The legacy separate check-in/out options move to
`woocommerce_accommodation_bookings_times_settings`. Both real migrations run
twice while an existing booking, linked order and merchant metadata remain intact.
The Product Add-ons cases submit the legacy Accommodation per-night flag, now
mapped to Bookings' block multiplier, and the modern Bookings flag through the
real cart and checkout, and check that the later Add-ons price adjustment cannot
charge the add-on a second time.

`test-dependencies.php` covers start-up when Bookings is inactive, and when it is listed
as active but its files are missing. Bookings cannot be unloaded from the PHPUnit process,
so each case runs `dependency-request.php` in a separate PHP process: one storefront or
wp-admin request with WordPress, WooCommerce and this plugin on the same test tables.
That request rolls back its own transaction.

With Xdebug, add `--coverage-html coverage/integration --path-coverage` and set
`XDEBUG_MODE=coverage`. PCOV supports the HTML report without `--path-coverage`.
The report includes unexecuted plugin PHP files; no report is produced without a
coverage driver. Generated reports belong under the ignored `coverage` directory.
