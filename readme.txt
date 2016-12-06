=== Plugin Name ===
Contributors:  woothemes, jshreve, akeda
Tags: woocommerce, bookings, accommodations
Requires at least: 4.1
Tested up to: 4.6
Stable tag: 1.0.7
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

== Changelog ==

= 1.0.7 =
* Fix - Partially booked dates not indicated on the calendar.
* Fix - Updated deprecated WooCommerce function calls.
* Fix - Plugin locale/text domain loaded after output is generated.

= 1.0.6 =
* Fix - Check in/out time is not respected when booking is created
* Tweak - Also check if booking class exists when plugin is loaded in case directory's name is not woocommerce-bookings

= 1.0.5 =
* Add - Display cost settings field.
* Fix - Undefined variable on $post using tab manager.

= 1.0.4 =
* Feature - Add support for Persons
* Feature - Add support for Resources
* Fix - Full compatibility with Product Add-ons.

= 1.0.3 =
* Fix - Per Night Price Displaying Incorrect above bookings form.
* Fix - Plugin on WordPress.org does not include assets folder.
* Fix - Compatibility with Product Add-ons.

= 1.0.2 =
* Fix - Fatal Error on submit booking request in admin bookings
* Fix - Incorrect end date time in order info and cart
* Fix - No manual booking if product type is Accommodations
* Fix - Better dependencies checker
* Fix - Fatal error when plugin is deactivated
* Fix - Fix display of checkout times when using different date formats.
* Fix - Fix documentation link.

= 1.0.1 =
* Fix - Typo in month names.
* Fix - Fix check-in/check-out times in the booking list table.

= 1.0 =
* Initial version.
