# Browser regressions

The existing Playwright runner now also checks saved accommodation data and two
independent customer sessions. The new journeys use classic and block checkout;
no customer is given administrator credentials. Their room factory comes from
`tests/integration/fixtures.php`.

Install Node 24, locked npm/Composer dependencies, Docker and a licensed Bookings
release (tested with 3.10.0). Extract Bookings outside this repository, then run:

```bash
BOOKINGS_DIR=/absolute/path/to/woocommerce-bookings npm run test:e2e:setup
ACCOM_E2E_URL=http://localhost:9021 npm run test:e2e:regression
ACCOM_E2E_URL=http://localhost:9021 npm run test:e2e:regression -- --grep 'independent customers'
npm run test:e2e:reset
```

Setup uses the existing wp-env runner on private ports 9020/9021 and refuses to
replace an existing override. With an existing **dedicated test store**, enable
`ACCOM_E2E` in wp-config, activate WooCommerce/Bookings/Accommodation and Storefront,
copy `fixtures/local.php` into `wp-content/mu-plugins/accommodation-e2e.php`, and
run the reset command.
Whenever `fixtures/local.php` changes, copy it into the test store again before running.
For a native local WordPress server, set
`ACCOM_E2E_WP_PATH=/absolute/path/to/wordpress` and `ACCOM_E2E_URL` before reset/run.
This switches only the WP-CLI transport; the browser and plugin paths are the same.

Every new test resets records owned by these fixtures: their products, resources,
bookings, tagged orders and pages. The seeded customer has the customer role.
Use a separate database and WordPress directory from the PHPUnit suite. No live
payments, emails, calendars or external HTTP services are used; email bodies are
captured at WordPress's mail boundary and checked against saved booking dates.

The full existing suite is `npm run test:e2e`; it also needs its existing email-log
plugin and admin/customer setup described in `bin/initialize.sh`. The focused
regression command keeps the same Playwright config but omits that suite's admin
login/API-key setup. Existing order and confirmation-email journeys remain intact.

Bookings reserves a room when it enters the first customer's cart. The second
session must see it unavailable and keep an empty cart. After the first customer
checks out and cancels through My Account, the second session reloads availability
and completes its own checkout. Assertions check both saved bookings/orders and
permit only one active booking. Boundary fixtures cross December/January and the
Europe/Berlin spring clock change, with distinct nightly rates.
