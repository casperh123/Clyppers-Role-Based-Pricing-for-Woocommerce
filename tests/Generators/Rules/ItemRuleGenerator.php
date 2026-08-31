<?php

namespace Generators\Rules;

use ClypperTechnology\RolePricing\Rules\ItemRule;
use ClypperTechnology\RolePricing\Rules\Rule;

class ItemRuleGenerator
{
    public static function Random(): ItemRule {
        $id = random_int(1, 10_000);
        $name = "name_{$id}";
        $rule = RuleGenerator::Random();
        $quantity_rule = RuleGenerator::Random();
        $min_qty = random_int(0, 100);

        return new ItemRule(
            $id,
            $name,
            $rule,
            $quantity_rule,
            $min_qty
        );
    }

    /**
     * Generate collection of random ItemRules
     *
     * @param int $size Size of array to generate
     * @return ItemRule[] Generated ItemRules
     */
    public static function RandomCollection(int $size): array {
        $rules = [];
        for ($i = 0; $i < $size; $i++) {
            $rule = self::Random();
            $rules[$rule->id] = $rule;
        }
        return $rules;
    }

    public static function with(int $id, string $name, ?Rule $rule = null, ?Rule $quantity_rule = null, int $min_qty = 0): ItemRule
    {
        return new ItemRule(
            $id,
            $name,
            $rule ?? new Rule(Rule::TYPE_PERCENT, 0),
            $quantity_rule ?? new Rule(Rule::TYPE_PERCENT, 0),
            $min_qty
        );
    }

    public static function with_rules(?Rule $rule = null, ?Rule $quantity_rule = null, int $min_qty = 0): ItemRule
    {
        return new ItemRule(
            -1,
            "rule",
            $rule ?? new Rule(Rule::TYPE_PERCENT, 0),
            $quantity_rule ?? new Rule(Rule::TYPE_PERCENT, 0),
            $min_qty
        );
    }
}