<?php

namespace Generators\Rules;

use ClypperTechnology\RolePricing\Rules\ItemRule;

class ItemRuleGenerator
{
    public static function Random(): ItemRule {
        $id = random_int(1, 10_000);
        $name = "name_{${$id}}";
        $rule = RuleGenerator::Random();
        $min_qty = random_int(0, 100);

        return new ItemRule(
            $id,
            $name,
            $rule,
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
        return array_map(fn() => self::Random(), range(1, $size));
    }
}