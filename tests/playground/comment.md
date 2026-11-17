<!-- markdownlint-disable-file MD041 -->

WooCommerce Accommodation Bookings is an add-on: it does nothing until WooCommerce Bookings 3.10 or newer is active. WooCommerce Bookings is a paid extension, so the preview below does not install it for you. Have your own copy at hand.

#### WordPress Playground - runs in your browser

- :test_tube: [Open this build in WordPress Playground]($PLAYGROUND_URL): a fresh store with WooCommerce set up and this build active. It opens on the plugin upload screen.
- To test the add-on there, upload your copy of `woocommerce-bookings.zip` on that screen and activate it. The preview then adds a "Sample room" that you can edit in the admin and book in the store. The site runs in your browser, so the file is not sent to a server.
- With a checkout of this branch you can start the same preview with WooCommerce Bookings already installed: see [tests/playground]($README_URL).

:information_source: Without WooCommerce Bookings the preview only shows that the build installs and activates, and the notice that asks for WooCommerce Bookings. A site in a browser tab runs no scheduled tasks, so booking reminders and the expiry of bookings left in the cart do not happen. The link stops working 14 days after the last build.
