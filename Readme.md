# Clypper's Role Based Pricing

A WooCommerce plugin that sets prices based on WordPress user role, with quantity-based price breaks.

## What it does

Set pricing rules per WordPress user role, including a built-in Guest role for logged-out visitors.

### Rule priority

For each role, the most specific rule wins:

1. Product rule: price for one specific product
2. Category rule: price for one specific category
3. General category rule: applies across a broader set of selected categories
4. Global rule: fallback for everything else

### Pricing types

- Percent discount or percent markup
- Fixed amount discount or fixed amount markup
- Fixed set price

### Quantity breaks

A rule can include a minimum quantity. Once a customer's cart quantity for an item reaches it, a different price applies. This price is shown on the product page before the customer reaches that quantity.

## Admin features

- Enable/disable pricing per role
- Search and add products or categories to a role's rules
- Copy a role's rules to other roles
- Bulk-import all products from a category into a role's rules, with or without variations

## Requirements

- WordPress 6.8+
- PHP 8.2+
- WooCommerce 9.6+ (tested up to 11.0.1)

## Installation

1. Clone or download this repository into `wp-content/plugins/clypper-role-based-pricing`
2. Run `composer install` to install PHP dependencies
3. Run `npm install && npm run build` to build the admin UI
4. Activate the plugin in WordPress with WooCommerce active
5. Go to WooCommerce > Roles & Rules B2B to configure pricing rules

## Development

```bash
npm install
npm start         # watches and rebuilds the admin UI during development
npm run build      # production build
composer install  # PHP dependencies and autoloading
```

Architecture:

- `includes/Rules/`: core domain model (`Rule`, `ItemRule`, `ApplicableRule`, `RoleRules`), handles the pricing cascade and calculations
- `includes/Services/`: `RuleService` and `RoleService`, persistence and role lookups
- `includes/REST/`: REST controllers and DTOs backing the admin UI
- `includes/PriceRules.php`: WooCommerce hook integration (price filters, cart totals, discount badges)
- `src/`: React/TypeScript admin UI, built with `@wordpress/scripts`

## License

GPLv3. See [LICENSE](https://www.gnu.org/licenses/gpl-3.0.html).

## Author

Casper Holten, [clyppertechnology.com](https://clyppertechnology.com)