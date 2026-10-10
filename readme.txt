=== Clypper's Role Based Pricing for WooCommerce ===
Contributors: casperh123
Tags: woocommerce, b2b, wholesale pricing, role based pricing, dynamic pricing
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.2
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

Each rule can also have a minimum quantity. Once a customer's cart quantity for that item reaches it, a different price applies.

There's a built-in "Guest" role for pricing logged-out visitors.

= On your storefront =

* A "Volume discount!" badge next to the sale badge, on product cards and on the product page, for products with a quantity discount for the visitor's role. Hover it to see the offer, e.g. "Buy 3+ for $75.00 per item".
* A "Unit price when buying 3 or more" box under the price on the product page.

Both work with classic themes and block themes, including Product Collection blocks placed on any page. The badge uses your theme's own sale badge style in your accent colour, and can be switched off under WooCommerce > Role Rules > Settings.

= Dashboard =

* Turn pricing on/off per role
* Add products or categories to a role's rules
* Copy a role's rules to other roles
* Import all products from a category into a role's rules, with or without variations
* Settings: switch the "Volume discount!" badge on or off

= Compatibility =

Works with WooCommerce HPOS. Prices update on the shop page, product pages, and cart.

= Privacy =

This plugin does not collect or store personal data, and makes no requests to external services. Pricing rules are stored in your own WordPress database. Its REST API endpoints are only available to users who can manage WooCommerce.

= Source code =

The admin interface is written in TypeScript and React and compiled with @wordpress/scripts. The uncompiled source is included in the plugin's `src/` folder and is also available on GitHub: https://github.com/casperh123/Clyppers-Role-Based-Pricing-for-Woocommerce

To build it yourself, run `npm ci && npm run build`.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/clyppers-role-based-pricing-for-woocommerce`, or install through the WordPress plugins screen.
2. Make sure WooCommerce is installed and active.
3. Activate the plugin.
4. Go to WooCommerce > Role Rules to set up pricing.

== Frequently Asked Questions ==

= Where do I set up pricing? =

Go to WooCommerce > Role Rules in your WordPress admin.

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

= Does the volume discount badge work with my theme? =

It's placed using WooCommerce's standard template hooks and blocks, so it works with most themes: classic themes such as Storefront, Astra and Kadence, block themes such as Twenty Twenty-Five, and Flatsome. Page builders that draw their own product cards may not show it.

= Can I turn the "Volume discount!" badge off? =

Yes, under WooCommerce > Role Rules > Settings. The unit price box on the product page stays.

= The badge or price box shows in the wrong place. Can I move it? =

Yes, with the `crbp_banner_hooks` filter. Each piece is a WordPress hook and priority; set one to `false` to remove it:

`add_filter( 'crbp_banner_hooks', function ( $hooks ) {
    $hooks['product_price_box'] = [ 'woocommerce_after_add_to_cart_form', 10 ];
    return $hooks;
} );`

The pieces are `loop_badge` (product cards), `product_badge` (product page) and `product_price_box`.

= Can I change the badge colour? =

Yes, with CSS in your theme: `:root { --crbp-accent: #c00; --crbp-accent-text: #fff; }`.

== Screenshots ==

1. Roles overview with pricing status and rule counts.
2. Product and category pricing for a role.
3. The "Volume discount!" badge next to the sale badge in the shop.
4. The badge and unit price box on a product page.
5. Settings.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.