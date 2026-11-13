# Accommodation critical-flow coverage

Source: [critical-flows wiki](https://github.com/woocommerce/woocommerce-accommodation-bookings/wiki/Critical-flows-WooCommerce-Accommodation-Booking), audited against the current suite. A test listed here is automated coverage, not a claim that its CI job has passed.

| Critical flow | Playwright coverage | Status |
| --- | --- | --- |
| Plugin activation | `admin.spec.js`: extension is activated | Automated |
| Missing WooCommerce or Bookings | Manual activation/dependency verification | N/A in the source wiki; not covered by the browser suite |
| Setup and configuration | `admin.spec.js`: configure accommodation settings | Automated |
| Create accommodation product | `product.spec.js`: create an Accommodation product | Automated |
| Check-in/check-out labels | `product.spec.js`: Verify labels | Automated |
| Minimum nights | `product.spec.js`: Minimum Nights Restriction | Automated |
| Maximum nights | `product.spec.js`: Maximum Nights Restriction | Automated |
| Calendar opens on click | `product.spec.js`: Calendar Display Mode | Automated |
| Calendar always visible | `product.spec.js`: Calendar Display Mode | Automated |
| Confirmation setting | `product.spec.js`: Confirmation Setting | Automated |
| Booking requiring confirmation | `product.spec.js`: Confirmation Setting | Automated |
| Cancellation | `product.spec.js`: Can be cancelled | Automated |
| Room availability | `product.spec.js`: Number of Rooms Availability | Automated |
| Minimum advance booking | `product.spec.js`: Minimum advance booking in days/weeks/months; crossing minimum boundary | Automated; the formerly commented crossing case now passes |
| Maximum advance booking | `product.spec.js`: Maximum advance booking in days/weeks/months; crossing maximum boundary | Automated: late check-ins are rejected; a stay beginning within the window may check out beyond it |
| Standard/display rates | `product.spec.js`: Rate Setting | Automated |
| Night-range rates | `product.spec.js`: Range of certain night | Automated |
| Month-range rates | `product.spec.js`: Range of months | Automated |
| Order check-in/out dates and times | `order.spec.js`: Booking Details | Automated |
| Confirmation email dates and times | `order.spec.js`: Confirmation Emails | Automated |
| Availability Calendar block | `calendar.spec.js`: Booked accommodation nights | Automated: seven booked nights are disabled and an available date remains enabled |

## Advance-booking boundary investigation

The six day/week/month scenarios assert both rejected and accepted stays. The old minimum-boundary check now rejects a stay beginning tomorrow when the minimum is four days away, even if checkout reaches that boundary. It is a normal passing test.

The maximum advance window limits check-in. A customer whose stay begins within that window may check out after it. The former commented rejection expectation contradicted Bookings' start-date validator and is replaced by a passing assertion of the existing rule. This preserves currently accepted stays; no production behavior or new checkout cutoff is introduced.

## Running the suite

Use the existing E2E setup and `pnpm run env:test:e2e:run`. Setup now installs Bookings Availability as well as Bookings. A focused run is `E2E_FILTER='advance|Calendar block' pnpm run env:test:e2e:run --retries=0`. No production feature flag is required. Fixtures require the existing `ACCOM_E2E` test-store constant and must run only on a dedicated test site.

The Calendar fixture creates its own product, seven-night booking and page, and deletes them in reverse order in `finally`. It does not change other products or availability settings. The existing independent fixture tests, persistence journeys and PHP-warning teardown remain part of the full suite.

The remote wiki should link to the merged tests and describe the check-in-only maximum window after CI verification. Local test results and expected failures must not be reported as green CI or full coverage.
