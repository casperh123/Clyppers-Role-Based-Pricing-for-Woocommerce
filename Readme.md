# Clypper's Role Based Pricing

A WooCommerce extension that sets tailored pricing per WordPress user role, product, and category — with quantity-break discounts for B2B and B2C stores.

## What it does

Define pricing rules per WordPress user role (including a built-in **Guest** role for logged-out visitors), applied at whatever level of precision a store needs.

### Rule priority

For each role, rules are checked in order of specificity — the most specific match wins:

1. **Product rule** — a price adjustment for one specific product
2. **Category rule** — a price adjustment for one specific category
3. **General category rule** — applies across a broader set of selected categories
4. **Global rule** — a fallback applied to everything else

### Pricing types

- Percent discount / percent markup
- Fixed amount discount / fixed amount markup
- Fixed set price

### Quantity breaks

Any rule can include a minimum quantity threshold, offering a better rate once a customer's cart quantity for that item crosses it — previewed on the product page before the customer reaches that quantity.

## Admin features

- Enable/disable pricing per role
- Search and add individual products or categories to a role's rules
- Copy an existing role's rules to one or more other roles in bulk
- Bulk-import all products from a category into a role's rules, with an option to include variations

## Requirements

- WordPress 6.8+
- PHP 8.2+
- WooCommerce 9.6+ (tested up to 11.0.1)

## Installation

1. Clone or download this repository into `wp-content/plugins/clypper-role-based-pricing`
2. Run `composer install` to install PHP dependencies
3. Run `npm install && npm run build` to build the admin UI
4. Activate the plugin in WordPress with WooCommerce active
5. Go to **WooCommerce → Roles & Rules B2B** to configure pricing rules

## Development

```bash
npm install
npm start        # watches and rebuilds the admin UI during development
npm run build     # production build
composer install # PHP dependencies / autoloading
```

Architecture at a glance:

- `includes/Rules/` — core domain model (`Rule`, `ItemRule`, `ApplicableRule`, `RoleRules`) responsible for the pricing cascade and calculations
- `includes/Services/` — `RuleService` and `RoleService`, persistence and role lookups
- `includes/REST/` — REST controllers and DTOs backing the admin UI
- `includes/PriceRules.php` — WooCommerce hook integration (price filters, cart totals, discount badges)
- `src/` — React/TypeScript admin UI, built with `@wordpress/scripts`

## License

GPLv3 — see [LICENSE](https://www.gnu.org/licenses/gpl-3.0.html).

## Author

Casper Holten — [clyppertechnology.com](https://clyppertechnology.com)
