<?php

namespace ClypperTechnology\RolePricing\Rules;

interface PricingRule
{
    public function calculatePrice(float $original_price, int $quantity = -1): ?float;
    public function rule_applies(int $quantity = 1): bool;
}
