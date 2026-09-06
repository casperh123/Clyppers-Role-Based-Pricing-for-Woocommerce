<?php
/**
 * Roles & Rules B2B
 *
 * @package Roles&RulesB2B/includes
 */

namespace ClypperTechnology\RolePricing;

use ClypperTechnology\RolePricing\Rules\ItemRule;
use ClypperTechnology\RolePricing\Rules\PricingRule;
use ClypperTechnology\RolePricing\Rules\RoleRules;
use ClypperTechnology\RolePricing\Services\RuleService;use WC_Product;

defined('ABSPATH') || exit;


/**
 * Class for rules
 */
class PriceRules
{
    private RuleService $rule_service;
    private PricingCalculator  $pricing_calculator;

    private static bool $generating_qty_price = false;

    public function __construct(RuleService $rule_service)
    {
        $this->rule_service = $rule_service;
        $this->pricing_calculator = new PricingCalculator();

        add_filter('woocommerce_product_is_on_sale', [$this, 'product_is_on_sale'], 999, 2);
        add_action('woocommerce_before_shop_loop_item', [$this, 'show_discount_banner_shop_archive'], 999);
        add_filter('flatsome_custom_single_product_1', [$this, 'show_discount_banner_product_page'], 999, 3);
        add_filter('woocommerce_get_price_html', [$this, 'add_quantity_discount_display'], 999, 2);
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
     * Modify the price HTML to show quantity discount pricing
     *
     * @param string $price_html The existing price HTML from tax plugin
     * @param WC_Product $product The product object
     * @return string Modified price HTML
     */
    public function add_quantity_discount_display(string $price_html, WC_Product $product): string
    {
        if (is_admin() || self::$generating_qty_price || !$this->user_has_rule() || !is_product()) {
            return $price_html;
        }

        $rule = $this->rule_service->get_rule_by_current_role();
        $applicable_quantity_rule = $this->get_quantity_rule($rule, $product);

        if (!$applicable_quantity_rule) {
            return $price_html;
        }

        $original_price = $this->pricing_calculator->get_wc_price($product);
        $cart_quantity = $this->get_cart_item_qty($product->get_id());
        $qty_discount_price = $applicable_quantity_rule->calculatePrice($original_price, $applicable_quantity_rule->min_quantity);

        if (!$qty_discount_price || $applicable_quantity_rule->quantity_reduction_applies($cart_quantity)) {
            return $price_html;
        }

        return $price_html .
                '<div style="margin: 20px 0; padding: 20px; width: 100%; background-color: #e8e8e8; display: flex; flex-direction: column;">' .
                '<p style="margin: 0 0 10px 0;"> Stykpris v/ ' . esc_html($applicable_quantity_rule->min_quantity) . '+ stk.:</p>' .
                '<p class="price product-page-price">' . wc_price($qty_discount_price) . '</p>' .
                '</div>';
    }

    /**
     * Get sale price with role discount
     *
     * @param string $price current price.
     * @param WC_Product $product current product.
     */
    public function get_rule_price(string $price, WC_Product $product): string
    {
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
        $applicable_rule = $this->get_applicable_rule($rule, $product);

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

    public function show_discount_banner_shop_archive(): void
    {
        $this->show_discount_banner(shortened_message: true);
    }

    public function show_discount_banner_product_page(): void
    {
        $this->show_discount_banner();
    }

    private function show_discount_banner(bool $shortened_message = false): void
    {
        $product = wc_get_product();

        if (!$product) {
            return;
        }

        $rule = $this->rule_service->get_rule_by_current_role();

        if (!$rule || !$rule->rule_active) {
            return;
        }

        $applicable_quantity_rule = $this->get_quantity_rule($rule, $product);

        if (!$applicable_quantity_rule) {
            return;
        }

        $message = $applicable_quantity_rule->quantity_reduction_message();

        if ($shortened_message) {
            ?>
            <div class="badge-container absolute right top z-1">
                <div class="callout badge badge-circle">
                    <div class="badge-inner secondary on-sale" style="background-color: #e3ad30"><span class="onsale">Mængderabat!</span>
                    </div>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="badge-container absolute right top z-1">
                <div class="callout badge badge-circle">
                    <div class="badge-inner secondary on-sale" style="background-color: #e3ad30"><span
                                class="onsale"><?php echo $message ?></span></div>
                </div>
            </div>
            <?php
        }
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

    private function get_applicable_rule(RoleRules $rule, WC_Product $product): ?PricingRule
    {
        $category_ids = $this->get_category_ids($product);

        if (is_wp_error($category_ids)) {
            $category_ids = [];
        }

        return $rule->get_applicable_rule($product->get_id(), $category_ids);
    }

    private function get_quantity_rule(RoleRules $rule, WC_Product $product): ?ItemRule
    {
        $category_ids = $this->get_category_ids($product);

        if (is_wp_error($category_ids)) {
            $category_ids = [];
        }

        return $rule->get_quantity_rule($product->get_id(), $category_ids);
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
