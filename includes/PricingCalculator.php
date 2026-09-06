<?php

namespace ClypperTechnology\RolePricing;

class PricingCalculator
{
    public function __construct()
    {

    }

    public function get_wc_price(\WC_Product $product): float {
        // Use WC sale price if exists, otherwise regular
        $wc_sale_price = $product->get_sale_price("edit");

        return !empty($wc_sale_price)
            ? floatval($wc_sale_price)
            : floatval($product->get_regular_price("edit"));
    }
}