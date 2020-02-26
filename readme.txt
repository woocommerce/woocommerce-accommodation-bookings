=== Plugin Name ===
Contributors:  woothemes, jshreve, akeda, bor0, jessepearson, laurendavissmith001
Tags: woocommerce, bookings, accommodations
Requires at least: 4.1
Tested up to: 5.0
Stable tag: 1.1.14
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

= 1.1.14 2020-02-26 =
* Fix - Person types are not copied over when duplicating an existing accommodations product.
* Tweak - WC tested up to 4.0.

= 1.1.13 - 2020-xx-xx =
* Fix - Proper escaping of some attributes.
* Fix - Ensure unavailable dates are shown to be unavailable in calendar.

[See changelog for all versions](https://raw.githubusercontent.com/woocommerce/woocommerce-accommodation-bookings/master/changelog.txt).
