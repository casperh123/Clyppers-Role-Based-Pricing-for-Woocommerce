<?php

namespace ClypperTechnology\RolePricing\Storefront;

use ClypperTechnology\RolePricing\PricingCalculator;
use ClypperTechnology\RolePricing\Rules\ItemRule;
use ClypperTechnology\RolePricing\Services\RuleService;
use WC_Product;

defined('ABSPATH') || exit;

/**
 * Works out what the current visitor should see about a product's quantity discount.
 */
final class BannerData
{
    private RuleService $rule_service;
    private PricingCalculator $pricing_calculator;

    public function __construct(RuleService $rule_service)
    {
        $this->rule_service = $rule_service;
        $this->pricing_calculator = new PricingCalculator();
    }

    public function for_product(WC_Product $product): ?BannerView
    {
        $rule = $this->rule_service->get_rule_by_current_role();

        if (!$rule || !$rule->rule_active) {
            return null;
        }

        $item_rule = $rule->get_quantity_rule($product->get_id(), $this->category_ids($product));

        if (!$item_rule || $item_rule->min_quantity <= 0) {
            return null;
        }

        $message = $item_rule->quantity_reduction_message();
        $quantity_price = $this->quantity_price($product, $item_rule);

        if ($message === null && $quantity_price === null) {
            return null;
        }

        return new BannerView($product->get_id(), $message, $item_rule->min_quantity, $quantity_price);
    }

    private function quantity_price(WC_Product $product, ItemRule $item_rule): ?float
    {
        // Once the cart qualifies, the discounted price is already the product's price.
        if ($item_rule->quantity_reduction_applies($this->cart_quantity($product->get_id()))) {
            return null;
        }

        $base_price = $this->pricing_calculator->get_wc_price($product);
        $price = $item_rule->calculatePrice($base_price, $item_rule->min_quantity);

        // Only advertise an actual saving (markups and broken rules don't get a price box).
        return $price !== null && $price < $base_price ? $price : null;
    }

    /**
     * @return int[]
     */
    private function category_ids(WC_Product $product): array
    {
        $id = 'variation' === $product->get_type() ? $product->get_parent_id() : $product->get_id();
        $ids = wc_get_product_term_ids($id, 'product_cat');

        return is_array($ids) ? $ids : [];
    }

    private function cart_quantity(int $product_id): int
    {
        $cart = function_exists('WC') ? WC()->cart : null;

        if (!$cart) {
            return 0;
        }

        return (int)($cart->get_cart_item_quantities()[$product_id] ?? 0);
    }
}
