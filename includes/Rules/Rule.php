<?php

namespace ClypperTechnology\RolePricing\Rules;

defined('ABSPATH') || exit;

class Rule
{
    public string $type;
    public int $value;

    public const  TYPE_PERCENT = 'percent';
    public const  TYPE_PERCENT_ADD = 'percent_add';
    public const  TYPE_FIXED = 'fixed';
    public const  TYPE_FIXED_ADD = 'fixed_add';
    public const  TYPE_FIXED_SET = 'fixed_set';

    public function __construct(
        string $type,
        int $value,
    )
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function applyRule(float $original_price): ?float {
        $adjust_value = floatval($this->value);

        $calculated_price = match ($this->type) {
            Rule::TYPE_PERCENT => $original_price * (1.0 - ($adjust_value / 100)),
            Rule::TYPE_PERCENT_ADD => $original_price * (1.0 + ($adjust_value / 100)),
            Rule::TYPE_FIXED => $original_price - $adjust_value,
            Rule::TYPE_FIXED_ADD => $original_price + $adjust_value,
            Rule::TYPE_FIXED_SET => $adjust_value,
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
            intval($rule['value'] ?? 0),
        );
    }
}
