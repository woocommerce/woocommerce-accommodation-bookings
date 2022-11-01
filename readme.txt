=== Plugin Name ===
Contributors:  woocommerce, automattic, woothemes, jshreve, akeda, bor0, jessepearson, laurendavissmith001, royho
Tags: woocommerce, bookings, accommodations
Requires at least: 4.1
Tested up to: 5.8
Stable tag: 1.1.34
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

An add-on for WooCommerce Bookings, making it easier to sell hotel rooms, apartments, and spaces to your customers with WooCommerce.

== Description ==

Accommodation Bookings is a free add-on for WooCommerce and the WooCommerce Bookings extension that makes it easier for you to rent out your space or run a hotel.

This extension extends Bookings and makes it possible to:

* Easily configure room rates for specific nights.
* Reservations that span nights instead of days.
* List check-in/check-out information on the product page, cart, and order pages.

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
