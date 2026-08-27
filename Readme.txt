=== Clypper's Role Based Pricing ===
Contributors: casperholten
Tags: woocommerce, b2b, role based pricing, wholesale, dynamic pricing
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.2
WC requires at least: 9.6
WC tested up to: 11.0.1
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Set tailored pricing per WordPress user role, product, and category in WooCommerce, with quantity-break discounts for B2B and B2C stores.

== Description ==

Clypper's Role Based Pricing lets you tailor product prices to who's buying, not just what they're buying. Define pricing rules per WordPress user role (including a built-in Guest role for logged-out visitors) and apply them at whatever level of precision your store needs.

= How pricing rules are chosen =

For each role, rules are checked in order of specificity, and the most specific match wins:

1. **Product rule** — a price adjustment for one specific product
2. **Category rule** — a price adjustment for one specific category
3. **General category rule** — applies across a broader set of selected categories
4. **Global rule** — a fallback that applies to everything else

= Pricing rule types =

Each rule can be set up as:

* Percent discount or percent markup
* Fixed amount discount or fixed amount markup
* A fixed set price

= Quantity break pricing =

Any rule can include a quantity threshold, letting you offer a better rate once a customer's cart quantity for that item reaches a set amount — shown to customers as a preview on the product page before they even reach that quantity.

= Admin tools =

* Enable/disable pricing per role from a simple dashboard
* Search and add individual products or categories to a role's rules
* Copy an existing role's rules to one or more other roles in bulk
* Bulk-import all products from a WooCommerce category into a role's rules, with an option to include product variations

= Compatibility =

Built with WooCommerce's High-Performance Order Storage (HPOS) in mind and integrates directly with core WooCommerce pricing filters, so adjusted prices display correctly across the shop, product pages, and cart.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/clypper-role-based-pricing` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Make sure WooCommerce is installed and active.
4. Go to WooCommerce > Roles & Rules B2B to start configuring rules per role.

== Frequently Asked Questions ==

= Does this work for logged-out visitors? =

Yes. A built-in "Guest" role lets you set pricing for customers who aren't logged in, separate from any registered user role.

= What happens if a product matches more than one rule? =

The most specific rule always wins: a product-level rule overrides a category-level rule, which overrides a general-category rule, which overrides the role's global fallback rule.

= Does this affect product variations? =

Yes, variations are supported, including when bulk-importing products from a category.

= Can I copy pricing rules between roles? =

Yes, you can copy either the product rules or category rules from one role to any number of other roles at once.

= Does this require WooCommerce? =

Yes, WooCommerce must be installed and active.

== Screenshots ==

1. Roles overview showing active status and rule counts per role.
2. Editing product and category rules for a role.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
