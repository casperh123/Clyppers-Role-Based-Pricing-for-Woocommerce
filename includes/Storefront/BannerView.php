<?php

namespace ClypperTechnology\RolePricing\Storefront;

defined('ABSPATH') || exit;

/**
 * What the storefront should show for one product's quantity discount.
 */
final class BannerView
{
    /**
     * @param int $product_id The product (or variation) the discount belongs to
     * @param ?string $message Full message, e.g. "Buy 10+ and save 5%". Null for markups, which aren't advertised.
     * @param int $min_quantity Quantity from which the discount applies
     * @param ?float $quantity_price Unit price from $min_quantity, or null when it shouldn't be shown
     *                               (not cheaper, or the cart already qualifies)
     */
    public function __construct(
        public readonly int $product_id,
        public readonly ?string $message,
        public readonly int $min_quantity,
        public readonly ?float $quantity_price,
    ) {}
}
