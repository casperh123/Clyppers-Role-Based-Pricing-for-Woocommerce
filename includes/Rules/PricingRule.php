<?php

namespace ClypperTechnology\RolePricing\Rules;

defined('ABSPATH') || exit;

interface PricingRule
{
    public function calculatePrice(float $original_price, int $quantity = -1): ?float;
}
