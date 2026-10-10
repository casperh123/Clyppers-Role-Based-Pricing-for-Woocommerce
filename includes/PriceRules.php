<?php
/**
 * Roles & Rules B2B
 *
 * @package Roles&RulesB2B/includes
 */

namespace ClypperTechnology\RolePricing;

use ClypperTechnology\RolePricing\Rules\PricingRule;
use ClypperTechnology\RolePricing\Rules\RoleRules;
use ClypperTechnology\RolePricing\Services\RuleService;
use WC_Product;

defined('ABSPATH') || exit;


/**
 * Class for rules
 */
class PriceRules
{
    private RuleService $rule_service;
    private PricingCalculator  $pricing_calculator;

    public function __construct(RuleService $rule_service)
    {
        $this->rule_service = $rule_service;
        $this->pricing_calculator = new PricingCalculator();

        add_filter('woocommerce_product_is_on_sale', [$this, 'product_is_on_sale'], 999, 2);
        add_filter('woocommerce_product_get_price', [$this, 'get_rule_price'], 20, 2);
        add_filter('woocommerce_product_variation_get_price', [$this, 'get_rule_price'], 20, 2);
        add_filter('woocommerce_product_get_sale_price', [$this, 'get_rule_price'], 20, 2);
        add_filter('woocommerce_product_variation_get_sale_price', [$this, 'get_rule_price'], 20, 2);
        add_filter('woocommerce_variation_prices_price', [$this, 'get_rule_price'], 20, 3);
        add_filter('woocommerce_variation_prices_sale_price', [$this, 'get_rule_price'], 20, 3);
        add_action(
                'woocommerce_before_calculate_totals',
                [$this, 'apply_role_pricing_to_cart'],
                20
        );
    }

    /**
     * Get price with role discount
     *
     * @param string $price current price.
     * @param WC_Product $product current product.
     */
    public function get_rule_price(string $price, WC_Product $product): string
    {
        if (is_admin()) {
          return $price;
        }

        $cart_qty = $this->get_cart_item_qty($product->get_id());
        $calculated_price = $this->resolve_role_price($product, $cart_qty);

        if (!$calculated_price) {
            return $price;
        }

        return strval($calculated_price);
    }

    private function resolve_role_price(WC_Product $product, int $quantity = 1): ?float
    {
        if (!$this->user_has_rule()) {
            return null;
        }

        $rule = $this->rule_service->get_rule_by_current_role();

        if (!$rule) {
            return null;
        }

        return $this->role_price($rule, $product, $quantity);
    }

    public function role_price(RoleRules $rule, WC_Product $product, int $cart_qty): ?float
    {
        $applicable_rule = $this->get_applicable_rule($rule, $product, $cart_qty);

        if(!$applicable_rule) {
            return null;
        }

        $base_price = $this->pricing_calculator->get_wc_price($product);

        return $applicable_rule?->calculatePrice($base_price, $cart_qty);
    }

    /**
     * Check if product is on sale
     *
     * @param bool $is_on_sale bool value.
     * @param WC_Product $product product.
     */
    public function product_is_on_sale(bool $is_on_sale, WC_Product $product): bool
    {
        if (is_admin() || !$this->user_has_rule()) {
            return $is_on_sale;
        }

        $regular_price = floatval($product->get_regular_price("edit"));
        $rule_price = $this->get_rule_price('', $product);

        if (!$rule_price) {
            return $is_on_sale;
        }

        return floatval($rule_price) < $regular_price;
    }

    /**
     * Check if user has a role or is guest frontend
     */
    public function user_has_rule(): bool
    {
        $rule = $this->rule_service->get_rule_by_current_role();

        if ($rule == null) {
            return false;
        }

        return $rule->rule_active;
    }

    public function apply_role_pricing_to_cart($cart): void
    {
        if (is_admin() && !defined('DOING_AJAX') && !defined('REST_REQUEST')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {

            if (!isset($cart_item['data']) || !is_object($cart_item['data'])) {
                continue;
            }

            $product  = $cart_item['data'];
            $quantity = $cart_item['quantity'];

            $new_price = $this->resolve_role_price($product, $quantity);

            if ($new_price) {
                $product->set_price($new_price);
            }
        }
    }

    private function get_applicable_rule(RoleRules $rule, WC_Product $product, int $cart_qty): ?PricingRule
    {
        $category_ids = $this->get_category_ids($product);

        if (is_wp_error($category_ids)) {
            $category_ids = [];
        }

        return $rule->get_applicable_rule($product->get_id(), $category_ids, $cart_qty);
    }

    /**
     * Get cart quantity for a given product.
     */
    private function get_cart_item_qty(int $product_id): int
    {
        $cart = WC()->cart;

        if (!$cart) {
            return 0;
        }

        $quantities = $cart->get_cart_item_quantities();

        return intval($quantities[$product_id] ?? 0);
    }

    /**
     * @return int[]
     */
    private function get_category_ids($product): array
    {
        return ('variation' === $product->get_type()) ?
                wc_get_product_term_ids($product->get_parent_id(), 'product_cat') :
                wc_get_product_term_ids($product->get_id(), 'product_cat');
    }
}
