# WooCommerce Accommodation Bookings Agent Guide

This file is the primary instruction source for coding agents in this repository.

## Backward Compatibility

Any change to a **public or externally exposed** class, interface, function, or method signature is **high-risk** and **must state its backward-compatibility impact in the PR description**. This plugin gives you no privacy boundary to hide behind: there are no namespaces anywhere in `includes/`, so a `private` member is internal, but a `public` or `protected` method on a loaded class is reachable by anything else on the site.

Treat a symbol as **externally exposed** when it is implemented or consumed outside this repository - by other plugins, themes, or site snippets - even if it looks internal. When in doubt, assume it is exposed and state the BC impact.

**As a producer of public API.** This plugin exposes a surface that third parties consume:
- The `accommodation-booking` product type slug, the largest contract here. It is persisted as the `product_type` term on every accommodation product, and it derives the data store key `product-accommodation-booking`, the dynamic `woocommerce_accommodation-booking_add_to_cart` template hook, and the type checks that gate nearly every filter callback in the plugin. Renaming it orphans existing products on every installed site and is not possible without a data migration. Treat the `_wc_accommodation_booking_*` post meta keys the same way.
- The global-namespace `WC_Accommodation_*` and `WC_Product_Accommodation_Booking*` classes, including every `public` and `protected` method third-party code can call or override.
- The `woocommerce_accommodation_booking_*` and `woocommerce_accommodation_bookings_*` hooks this plugin fires.
- Registered script and style handles, such as `wc_accommodation_bookings_writepanel_js` on the product admin screens.

Adding a **required** method to an interface that external code can implement is backward-incompatible - existing implementers fatal on load - and removing one leaves them carrying a dead method. Prefer a non-breaking alternative: add the method to a concrete class, introduce a separate new interface, or provide a default via an abstract base class. Which methods get *called* is a contract too: adding a fast path that skips an overridable method silently disables a subclass's override even though no signature changed.

**Deprecate, don't rename.** Never rename or remove an existing public symbol (class, method, constant, hook, option key, the product type slug) in place. Mark the old one `@deprecated`, add the replacement alongside it, and keep both working through a deprecation window so consumers can migrate.

> This rule exists because WooCommerce 10.9.0 was reverted on WP Cloud: a required method added to a published contract fataled every older extension that implemented it. The same failure mode applies to any contract this plugin publishes.

**As a consumer of upstream contracts.** This plugin is a guest inside WooCommerce Bookings: it subclasses `WC_Product_Booking` and `WC_Product_Booking_Resource`, hooks roughly twenty `woocommerce_bookings_*` filters, writes to Bookings' own tables, and enforces a minimum Bookings version in `WC_Accommodation_Dependencies::check_dependencies()`. Those are someone else's contracts. Verify behaviour against the installed Bookings version rather than assuming the contract is frozen, and treat raising the version floor as a breaking change for merchants below it, not a cleanup.

### The compatibility surface is wider than PHP signatures

WordPress exposes more contracts than class and function signatures. The following are equally binding: a change to any of them is **high-risk** and requires the same backward-compatibility impact statement in the PR description.

**Hooks and filters are public contracts.** Every `do_action` and `apply_filters` call is an interface that third-party callbacks depend on. Removing a hook, renaming it, or removing/reordering its arguments breaks every attached callback. Changing *when* or *whether* a hook fires can break consumers that depend on its timing. Additive is the safe path: append new arguments at the end, never remove or reorder existing ones. To retire a hook, fire it through `do_action_deprecated()` / `apply_filters_deprecated()` for a deprecation window instead of deleting it.

**Do not assume global state.** This plugin's code runs in admin, REST, CLI, cron, and front-end contexts - `install()` alone runs on `shutdown`, which fires in all of them - and not all of them set the globals a front-end request does (`$post`, `$wp_query`, an initialised session or cart). A newly introduced read of a global, or of `WC()->…` state, in a path reachable outside a standard request is a fatal or a silent misbehaviour in the contexts that do not set it. Guard the exact dependency explicitly: use `function_exists`/`class_exists` for symbols, `isset` for variables, `did_action` for lifecycle state, and verify that `WC()` and the required component are initialised before dereferencing `WC()->…`.

**Do not assume single-site.** Multisite changes where data lives: site-scoped vs network-scoped options (`get_option` vs `get_site_option`), per-site tables, user roles and capabilities, and upload paths all differ. This plugin reads and writes its version options with `get_option`, so they are site-scoped and each site upgrades independently. A change that reads or writes site state must state in its PR whether it behaves correctly under multisite - and if it was not tested there, say so explicitly.

**Do not assume install layout.** WordPress could be configured to run in a subdirectory, with relocated `wp-content`, and behind reverse proxies. Never build paths or URLs by concatenation from the domain root; derive them (`plugins_url()`, `plugin_dir_path()`, `wp_upload_dir()`, and mind the `home_url()` vs `site_url()` distinction). The `WC_ACCOMMODATION_BOOKINGS_*` path and URL constants are already derived this way - use them instead of rebuilding a path. A path that works on a root install and breaks elsewhere is a compatibility bug, not an edge case.

### Before changing any public or externally exposed surface (agent checklist)

1. Identify the contract you are touching: signature, override, hook, the product type slug or a meta key, a Bookings contract you consume, global/scope expectation, site topology, or install layout.
2. Assume unseen consumers. You cannot enumerate third-party code; if the surface is reachable from outside this plugin, someone consumes it.
3. Prefer the additive path (new optional method, appended hook argument, new symbol + deprecation) over changing what exists.
4. State the impact in the PR description: what changed, who could consume it, and why it is safe or what the deprecation path is.
5. If you cannot establish the impact, stop and flag it to the user as needing review.

> Core's [AGENTS.md Backward Compatibility](https://github.com/woocommerce/woocommerce/blob/trunk/AGENTS.md#backward-compatibility) section carries the same guardrail.
