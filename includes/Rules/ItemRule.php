<?php

namespace ClypperTechnology\RolePricing\Rules;

defined('ABSPATH') || exit;

class ItemRule implements PricingRule
{
    public int $id;
    public string $name;
    public Rule $rule;
    public Rule $quantity_rule;
    public int $min_quantity;

    public function __construct(
        int $id,
        string $name,
        Rule $rule = new Rule(Rule::TYPE_PERCENT, 0),
        Rule $quantity_rule = new Rule(Rule::TYPE_PERCENT, 0),
        int $min_qty = 0,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->rule = $rule;
        $this->quantity_rule = $quantity_rule;
        $this->min_quantity = $min_qty;
    }

    /**
     * Calculate adjusted price based on rule type and value
     *
     * @param float $original_price The original price to adjust
     * @param int $quantity Current cart quantity
     * @return ?float The adjusted price
     */
    public function calculatePrice(float $original_price, int $quantity = -1): ?float {
        if($this->quantity_reduction_applies($quantity)) {
            return $this->quantity_rule->calculatePrice($original_price);
        }

        if($this->rule->has_value()) {
            return $this->rule->calculatePrice($original_price);
        }

        return null;
    }

    public function quantity_reduction_message(): ?string
    {
        if ( !$this->quantity_rule->has_value() || $this->min_quantity <= 0 ) {
            return null;
        }

        return match ($this->quantity_rule->type) {
            Rule::TYPE_PERCENT => "Køb {$this->min_quantity}+ og spar {$this->quantity_rule->value}%",

            Rule::TYPE_FIXED =>
                "Køb {$this->min_quantity}+ og spar " .
                wc_price(
                    $this->quantity_rule->value,
                    ['in_span' => false]
                ) .
                " pr. stk.",

            Rule::TYPE_FIXED_SET =>
                "{$this->min_quantity} for " .
                wc_price(
                    $this->quantity_rule->value,
                    ['in_span' => false]
                ) .
                " pr. stk.",

            default => null,
        };
    }

    public function quantity_reduction_applies(int $quantity = -1): bool
    {
        return $this->quantity_rule->has_value()
            && $this->min_quantity > 0
            && $quantity >= $this->min_quantity;
    }

    public function reduction_applies(int $quantity): bool {
        return $this->rule->has_value() || $this->quantity_reduction_applies($quantity);
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rule' => $this->rule->to_array(),
            'quantity_rule' => $this->quantity_rule->to_array(),
            'min_qty' => $this->min_quantity,
        ];
    }

    public static function from_array(array $data): self {
        return new self(
            id: (int)$data['id'],
            name: $data['name'],
            rule: Rule::from_array( $data['rule'] ?? null),
            quantity_rule: Rule::from_array($data['quantity_rule'] ?? null),
            min_qty: (int)($data['min_qty'] ?? 0),
        );
    }
}