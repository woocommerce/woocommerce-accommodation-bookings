=== WooCommerce Accommodation Bookings ===
Contributors:  woocommerce, automattic
Tags: woocommerce, bookings, accommodations
Requires at least: 6.5
Tested up to: 6.7
Stable tag: 1.3.0
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

An add-on for WooCommerce Bookings, making it easier to sell hotel rooms, apartments, and spaces to your customers with WooCommerce.

== Description ==

Accommodation Bookings is a free add-on for WooCommerce and the WooCommerce Bookings extension that makes it easier for you to rent out your space or run a hotel.

This extension extends Bookings and makes it possible to:

* Easily configure room rates for specific nights.
* Reservations that span nights instead of days.
* List check-in/check-out information on the product page, cart, and order pages.

Accommodation Bookings is fully [compatible with WooPayments](https://woocommerce.com/products/woopayments/).

== Installation ==

1. Make sure WooCommerce & WooCommerce Bookings are installed.
1. Download the plugin file to your computer and unzip it.
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation’s wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

Or use the automatic installation wizard through your admin panel, just search for this plugins name.

== Frequently Asked Questions ==

= Why do my Accommodation Products show higher prices than I defined in the dashboard? =

If the prices shown on the product do not match the prices defined in the dashboard, the caching mechanism used for pricing calculation is most likely still using old information (e.g. when you updated the prices, or when changing a Bookable product to an Accommodation product). The quickest way to make sure that your prices are correct is to save your existing accommodation product again. The save will update the cache and the price on your site will now reflect what you have defined in your dashboard.

== Changelog ==

= 1.3.0 - 2024-12-02 =
* Fix - Ensure sorting by price works as expected.
* Dev - Bump WooCommerce "tested up to" version 9.5.
* Dev - Bump WooCommerce minimum supported version to 9.3.

= 1.2.10 - 2024-11-18 =
* Dev - Bump WordPress "tested up to" version 6.7.

= 1.2.9 - 2024-10-28 =
* Dev - Bump WooCommerce "tested up to" version 9.4.
* Dev - Bump WooCommerce minimum supported version to 9.2.
* Dev - Bump WordPress minimum supported version to 6.5.

= 1.2.8 - 2024-08-28 =
* Fix - Ensure display of checkbox options shows correctly in an Accommodation product.
* Fix - Allow Accommodation product tabs to be ordered properly.
* Dev - Bump WooCommerce "tested up to" version 9.2.
* Dev - Bump WooCommerce minimum supported version to 9.0.
* Dev - Fix QIT E2E tests and add support for a few new test types.
* Dev - Update E2E tests to accommodate the changes in WooCommerce 9.2.

= 1.2.7 - 2024-07-15 =
* Dev - Bump WooCommerce "tested up to" version 9.0.
* Dev - Bump WooCommerce minimum supported version to 8.8.
* Dev - Bump WordPress "tested up to" version 6.6.
* Dev - Bump WordPress minimum supported version to 6.4.
* Dev - Update NPM packages and node version to v20 to modernize developer experience.
* Dev - Add proper plugin name to the `readme.txt`. Remove Bookings from our required plugins list as it's not hosted on WordPress.org.
* Dev - Exclude the Woo Comment Hook `@since` sniff.
* Fix - Unavailable accommodation days appear available even when booked.
* Fix - Error in Updating Accommodation Product Data via REST API: `Unrecognized Product Type Response`.

= 1.2.6 - 2024-05-20 =
* Dev - Bump WooCommerce "tested up to" version 8.9.
* Dev - Bump WooCommerce minimum supported version to 8.7.
* Dev - Bump WordPress "tested up to" version 6.5.
* Dev - Bump WordPress minimum supported version to 6.3.

= 1.2.5 - 2024-02-26 =
* Dev - Apply same code style (call static method) within file.
* Dev - Bump WooCommerce "tested up to" version 8.6.
* Dev - Bump WooCommerce minimum supported version to 8.4.
* Fix - Booking Calendar displays incorrect availability for Accommodation Products.
* Fix - Missing product ID when getting check-in time.

= 1.2.4 - 2024-02-05 =
* Dev - Bump PHP "tested up to" version 8.3.
* Dev - Bump WooCommerce "tested up to" version 8.5.
* Dev - Bump WooCommerce minimum supported version to 8.3.
* Dev - Bump WordPress minimum supported version to 6.3.

= 1.2.3 - 2024-01-08 =
* Dev - Declare compatibility with WooCommerce Blocks.
* Dev - Bump PHP minimum supported version to 7.4.
* Dev - Bump WooCommerce "tested up to" version 8.4.
* Dev - Bump WooCommerce minimum supported version to 8.2.

= 1.2.2 - 2023-12-11 =
* Dev - Add end-to-end tests using Playwright.
* Dev - Update default behavior to use a block-based cart and checkout in E2E tests.
* Dev - Bump WooCommerce "tested up to" version 8.3.
* Dev - Bump WooCommerce minimum supported version to 8.1.
* Dev - Bump WordPress "tested up to" version 6.4.
* Dev - Bump WordPress minimum supported version to 6.2.

= 1.2.1 - 2023-10-10 =
* Dev - Hard code the paths to the asset data files.
* Dev - Update PHPCS and PHPCompatibility GitHub Actions.
* Fix - Fatal Error when WooCommerce is disabled.
* Tweak - Indicate compatibility with WooPayments extension.

= 1.2.0 - 2023-09-05 =
* Dev - Bump PHP minimum supported version from 7.0 to 7.3.
* Dev - Bump WooCommerce "tested up to" version from 7.8 to 8.0.
* Dev - Bump WooCommerce minimum supported version from 7.2 to 7.8.
* Dev - Bump WordPress "tested up to" version from 6.2 to 6.3.

= 1.1.43 - 2023-07-17 =
* Dev - Bump WooCommerce "tested up to" version 7.8.
* Dev - Bump WooCommerce minimum supported version from 6.0 to 7.2.
* Dev - Bump WordPress minimum supported version from 5.6 to 6.1.

= 1.1.42 - 2023-06-14 =
* Dev - Bump WooCommerce "tested up to" version to 7.6.
* Dev - Bump WooCommerce minimum supported version from 6.0 to 6.8.
* Dev - Bump WordPress "tested up to" version to 6.2.
* Dev - Bump WordPress minimum supported version from 5.6 to 5.8.
* Dev - Added new GitHub Workflow to run Quality Insights Toolkit tests.

= 1.1.41 - 2023-05-26 =
* Dev - Add product unit filter, `wc_bookings_product_duration_fallback`, to add night unit support.
* Dev - Fix linting errors found by the Quality Insights Toolkit.

= 1.1.40 - 2023-05-12 =
* Dev - Added a new filter, `woocommerce_accommodation_booking_get_check_times`, to change the check-in/out timings per product.
* Fix - Fully booked days show as partially booked - Day after booking shows partially booked.

= 1.1.39 - 2023-03-14 =
* Dev - Bump `http-cache-semantics` from 4.1.0 to 4.1.1.

= 1.1.38 - 2023-03-13 =
* Dev - Bump `http-cache-semantics` from 4.1.0 to 4.1.1.

= 1.1.37 - 2023-01-27 =
* Dev - Bump `scss-tokenizer` from 0.3.0 to 0.4.3 and `node-sass` from 7.0.1 to 7.0.3.
* Fix - Fatal error when Rate is empty and Range Cost is added.
* Tweak - Bump WooCommerce "tested up to" from 6.7 to 7.3.
* Tweak - Bump WordPress tested up to version from 6.0 to 6.1.
* Tweak - Bump minimum required WordPress version from 4.1 to 5.6.

= 1.1.36 - 2022-11-24 =
* Update - Bump NPM to v8.
* Update - Bump composer to v2.
* Update - Bump node to v16.

= 1.1.35 - 2022-11-09 =
* Add - Declare support for High-performance Order Storage ("HPOS").
* Bump minimist from 1.2.5 to 1.2.6.
* Fix - Showing check-in date on the cart page instead of booking date.
* Tweak - Minimum Version Bumps: WP 5.6, Woo 6.0, & PHP 7.0.

= 1.1.34 - 2022-11-01 =
* Fixed - PHP 8.0/8.1 Compatibility issue fixed: Critical error when cost in range is empty if Standard room rate is empty as well.

= 1.1.33 - 2022-08-10 =
* Fix - Build artifact includes node_modules.

= 1.1.32 - 2022-08-01 =
* Tweak - WC 6.7.0 compatibility.
* Update all npm dependencies.

= 1.1.31 - 2022-07-06 =
* Fix - Fatal error with PHP 8.0 when Base Cost/Block Cost is empty.

= 1.1.30 - 2022-06-02 =
* Fix - PHP Warnings on the setting page.
* Tweak - Bump tested up to WordPress version 6.0.

= 1.1.29 - 2022-06-02 =
* Fix - PHP Warnings on the setting page.
* Tweak - Bump tested up to WordPress version 6.0.

= 1.1.27 - 2022-06-02 =
* Fix - PHP Warnings on the setting page.
* Tweak - Bump tested up to WordPress version 6.0.

= 1.1.26 - 2021-11-30 =
* Fix - Bump y18n from 3.2.1 to 3.2.2.
* Tweak - WC 5.9 compatibility.
* Tweak - WP 5.8 compatibility.

= 1.1.25 - 2021-10-28 =
* Tweak - Change start days settings label to selectable days to be more accurate with functionality.
* Tweak - WC 5.8 compatibility.
* Tweak - WP 5.8 compatibility.

= 1.1.24 - 2021-05-11 =
* Fix - Added tested up to comment for WordPress compatibility to make it easier to use common tooling.
* Fix - Replace deprecated jQuery 3 methods.

= 1.1.23 - 2021-02-25 =
* Fix - Dev - Fix: Add casts to float before applying the 'abs' function to potentially empty strings for compatibility with PHP8.
* Tweak - WC 5.0 compatibility.

= 1.1.22 - 2020-10-27 =
* Tweak - WC 4.6 compatibility.

= 1.1.21 - 2020-09-29 =
* Fix   - Skip formatting dates in ICS output for Accommodation Bookable products.
* Fix   - Allow products to be specified as virtual.
* Fix   - Use time for transient to skip autoload.
* Tweak - Migrate Settings from Product to Bookings screen.

= 1.1.20 - 2020-08-25 =
* Fix - Do not round cost values in range types in Rates section.

= 1.1.19 - 2020-08-19 =
* Tweak - WordPress 5.5 compatibility.

= 1.1.18 - 2020-07-08 =
* Fix - Existing booking checkout date showed as fully booked and not selectable.

= 1.1.17 - 2020-06-10 =
* Tweak - WC tested up to 4.2.

= 1.1.15 - 2020-03-06 =
* Add - Add basic unit tests suite.
* Fix - Fix missing translation for resource duration display.
* Tweak - WP tested up to 5.4.

[See changelog for all versions](https://raw.githubusercontent.com/woocommerce/woocommerce-accommodation-bookings/master/changelog.txt).
