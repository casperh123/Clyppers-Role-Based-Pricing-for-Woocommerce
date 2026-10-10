<?php

namespace ClypperTechnology\RolePricing\Rules;

defined('ABSPATH') || exit;

class Rule implements PricingRule
{
    public RuleType $type;
    public float $value;

    public function __construct(
        RuleType $type,
        float $value,
    )
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function calculatePrice(float $original_price, int $quantity = -1): ?float {
        $calculated_price = match ($this->type) {
            RuleType::TYPE_PERCENT => $original_price * (1.0 - ($this->value / 100)),
            RuleType::TYPE_PERCENT_ADD => $original_price * (1.0 + ($this->value / 100)),
            RuleType::TYPE_FIXED => $original_price - $this->value,
            RuleType::TYPE_FIXED_ADD => $original_price + $this->value,
            RuleType::TYPE_FIXED_SET => $this->value,
            RuleType::EMPTY => null,
        };

        if ($calculated_price === null) {
            return null;
        }

        // If calculated price is below 0 and it wasn't intentionally set to 0, return original price
        if ($calculated_price <= 0) {
            return null;
        }

        return round($calculated_price, wc_get_price_decimals() ?? 2);
    }

    public function rule_applies(): bool {
        return $this->type !== RuleType::EMPTY && $this->value > 0;
    }

    public function to_array() : array {
        return [
            'type' => $this->type,
            'value' => $this->value,
        ];
    }

    /**
     * Build a rule from stored or request data. Anything unusable becomes an empty rule that never applies.
     */
    public static function from_array( mixed $rule ) : Rule {
        if ( ! is_array( $rule ) ) {
            $rule = [];
        }

        $type  = is_string( $rule['type'] ?? null ) ? RuleType::tryFrom( $rule['type'] ) : null;
        $value = is_numeric( $rule['value'] ?? null ) ? (float) $rule['value'] : 0.0;

        return new Rule(
            $type ?? RuleType::EMPTY,
            max( 0.0, $value ),
        );
    }
}
