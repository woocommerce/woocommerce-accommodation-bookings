=== Plugin Name ===
Contributors:  woothemes, jshreve, akeda, bor0, jessepearson, laurendavissmith001
Tags: woocommerce, bookings, accommodations
Requires at least: 4.1
Tested up to: 5.0
Stable tag: 1.1.11
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

= 1.1.11 - 2019-11-04 =
* Tweak - WC tested up to 3.8.

= 1.1.10 - 2019-10-24 =
* Fix - Integration with WooCommerce Product Add-ons.

= 1.1.9 - 2019-08-09 =
* Tweak - Update deprecated calls to WC_Bookings_Controller::get_all_existing_bookings since Bookings 1.15.0.
* Tweak - WC tested up to 3.7.

= 1.1.8 - 2019-06-10 =
* Fix - Adds accommodation bookings support for WooCommerce Bookings Availability extension

= 1.1.7 - 2019-06-03 =
* Fix - Mismatch function declaration causing PHP warnings.

= 1.1.6 - 2019-04-17 =
* Remove - partially booked days styling.
* Tweak - WC tested up to 3.6

= 1.1.5 - 2018-11-12 =
* Fix - Overwrite get_duration function, to fix duration calculations.

[See changelog for all versions](https://raw.githubusercontent.com/woocommerce/woocommerce-accommodation-bookings/master/changelog.txt).
