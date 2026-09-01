<?php

namespace ClypperTechnology\RolePricing\Rules;

defined('ABSPATH') || exit;

class Rule implements PricingRule
{
    public string $type;
    public float $value;

    public const  TYPE_PERCENT = 'percent';
    public const  TYPE_PERCENT_ADD = 'percent_add';
    public const  TYPE_FIXED = 'fixed';
    public const  TYPE_FIXED_ADD = 'fixed_add';
    public const  TYPE_FIXED_SET = 'fixed_set';

    public function __construct(
        string $type,
        float $value,
    )
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function calculatePrice(float $original_price, int $quantity = -1): ?float {
        $calculated_price = match ($this->type) {
            Rule::TYPE_PERCENT => $original_price * (1.0 - ($this->value / 100)),
            Rule::TYPE_PERCENT_ADD => $original_price * (1.0 + ($this->value / 100)),
            Rule::TYPE_FIXED => $original_price - $this->value,
            Rule::TYPE_FIXED_ADD => $original_price + $this->value,
            Rule::TYPE_FIXED_SET => $this->value,
            default => null
        };

        // If calculated price is below 0 and it wasn't intentionally set to 0, return original price
        if ($calculated_price <= 0) {
            return null;
        }

        return round($calculated_price, wc_get_price_decimals() ?? 2);
    }

    public function has_value(): bool {
        return ! empty( $this->value );
    }

    public function to_array() : array {
        return [
            'type' => $this->type,
            'value' => $this->value,
        ];
    }

    public static function from_array( array $rule ) : Rule {
        return new Rule(
            $rule['type'] ?? "",
            floatval($rule['value'] ?? 0.0),
        );
    }
}
