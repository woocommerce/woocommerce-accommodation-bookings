=== Plugin Name ===
Contributors:  woothemes, jshreve, akeda, bor0, jessepearson, laurendavissmith001
Tags: woocommerce, bookings, accommodations
Requires at least: 4.1
Tested up to: 4.9
Stable tag: 1.1.3
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

= 1.1.3 =
* Fix - Fatal error when disabling WooCommerce.
* Fix - Undefined index notice.
* Fix - Check for product before using it in order info.
* Fix - Resource Costs not added for Custom Rate - Date Ranges.
* Tweak - Add an option to autocomplete accommodation orders by allowing virtual accommodation products.
* Fix - Remove duplicate person cost multiplier checkbox.

= 1.1.2 =
* Fix - Corrupted check-in/check-out dates in DB.
* Update - WC 3.3 compatibility

= 1.1.1 =
* Fix - PHP Notice when fully booked array is empty.
* Fix - Bookings outside of available range being checked for availability.
* Fix - Maximum number of nights allowed option set to 0 breaks page.
* Fix - Bookable product base cost not being cleared when changing to an Accommodation product.
* Fix - Update _wc_booking_block_cost when base cost is updated.

= 1.1.0 =
* Fix - Display cost not used when presenting price to the client.
* Add - Add woocommerce_accommodation_bookings_range_picker_enabled to disable range picker.
* Fix - Fallback to default checkin/checkout time values if none are set.
* Fix - Fully booked days not showing correctly when persons are used.
* Fix - Resource availability calculated incorrectly.
* Add - New functionality to restrict the day a booking can start on. 
* Fix - Tax fields missing.
* Fix - Display price is showing incorrectly.
* Fix - Availability rules being ignored.
* Fix - Today should be shown as booked if it has check out available only

= 1.0.9 =
* Fix - Additional updates for WooCommerce 3.0 compatibility.
* Fix - Min/max rules not being applied.
* Fix - Prices not being added to cart.
* Fix - Align panel icons.
* Fix - Fatal error when using older version of Bookings.
* Fix - Logic for availability of dates with checkin/checkout.
* Fix - Resource costs not being calculated.

= 1.0.8 =
* Fix - WooCommerce 3.0 compatibility.

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
