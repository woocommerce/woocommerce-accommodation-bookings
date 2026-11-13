# WordPress Playground previews

The files behind the WordPress Playground option of the "Test the build" comment that
`.github/workflows/test-the-build.yml` posts on every pull request. None of them ships in the
plugin ZIP: `tests` is `export-ignore`d.

| File | Purpose |
| --- | --- |
| `setup.php` | One-off store setup: no setup wizard, store pages, cash on delivery, check-in and check-out times. |
| `preview.php` | Copied to `mu-plugins`. Says that the add-on needs WooCommerce Bookings, and creates a "Sample room" once it is active. |
| `blueprint-local.json` | The Blueprint for a local preview with WooCommerce Bookings already installed. |

## What the link in the comment can show

Accommodation Bookings does nothing until WooCommerce Bookings is active. WooCommerce Bookings is
a paid extension in a private repository, and Playground only installs what it can download from
a public URL. So the link opens a store with WooCommerce and the pull request's build, on the
plugin upload screen, and the visitor uploads their own `woocommerce-bookings.zip`. The site runs
in the visitor's browser, so the file is not sent to a server.

Never make a build of WooCommerce Bookings downloadable to get around this, and never put a
credential in a Blueprint.

## Run a preview locally, with WooCommerce Bookings installed

Use the Node.js version in `.nvmrc` and build the checkout with `pnpm run build:dev`. You also need a copy of `woocommerce-bookings.zip`. Download it with GitHub CLI if you have access to the repository.

```bash
gh release download --repo woocommerce/woocommerce-bookings --pattern woocommerce-bookings.zip --dir "$HOME/Downloads"
```

Then, from the root of the checkout:

```bash
npx @wp-playground/cli@3.1.64 server \
  --blueprint=tests/playground/blueprint-local.json \
  --mount-before-install=.:/wordpress/wp-content/plugins/woocommerce-accommodation-bookings \
  --mount-before-install="$HOME/Downloads/woocommerce-bookings.zip":/tmp/woocommerce-bookings.zip
```

Open the address it prints once it says "Ready!". You are logged in as an administrator and land
on the product list, which holds the "Sample room". The checkout is mounted, not copied, so a
change to a PHP file shows on the next page load.

## Limits

- A Playground site in a browser has no public address and runs no scheduled tasks. Booking
  reminders and the expiry of bookings left in the cart do not happen there.
- The preview installs the newest WooCommerce from WordPress.org, not the oldest version the
  plugin supports.
- The link in the comment stops working 14 days after the last build, when its artifact expires.

## In-progress features

If a pull request puts its work behind an option or a filter, turn it on in `setup.php` in the
same pull request. The workflow takes these files from the pull request, so its link opens with
the feature on.
