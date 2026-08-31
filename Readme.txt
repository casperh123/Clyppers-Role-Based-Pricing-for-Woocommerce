=== Clypper's Role Based Pricing for WooCommerce ===
Contributors: Casper Holten
Tags: woocommerce, b2b, wholesale pricing, role based pricing, dynamic pricing
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.3
WC requires at least: 9.6
WC tested up to: 11.0.1
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Set different prices based on WordPress user role, and offer lower prices at higher quantities.

== Description ==

This plugin lets you set different prices for different WordPress user roles, and set quantity-based price breaks on top of that.

You can set pricing at three levels:

* For a whole role, across all products
* For a role, on one category
* For a role, on one product

If more than one applies to a product, the most specific one is used: product rule first, then category rule, then the role's general rule.

Each rule can be a percent discount, percent markup, fixed amount discount, fixed amount markup, or a fixed price.

Each rule can also have a minimum quantity. Once a customer's cart quantity for that item reaches it, a different price applies. This price is shown on the product page before the customer reaches that quantity.

There's a built-in "Guest" role for pricing logged-out visitors.

= Dashboard =

* Turn pricing on/off per role
* Add products or categories to a role's rules
* Copy a role's rules to other roles
* Import all products from a category into a role's rules, with or without variations

= Compatibility =

Works with WooCommerce HPOS. Prices update on the shop page, product pages, and cart.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/clypper-role-based-pricing`, or install through the WordPress plugins screen.
2. Activate the plugin.
3. Make sure WooCommerce is installed and active.
4. Go to WooCommerce > Roles Rules to set up pricing.

== Frequently Asked Questions ==

= Can I set pricing for logged-out visitors? =

Yes, using the built-in "Guest" role.

= What happens if a product matches more than one rule? =

The most specific rule wins: product rule, then category rule, then the role's general rule.

= Does this work with product variations? =

Yes, including when bulk-importing a category.

= Can I copy pricing from one role to another? =

Yes.

= Do I need WooCommerce? =

Yes.

== Screenshots ==

1. Roles overview with pricing status and rule counts.
2. Product and category pricing for a role.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.