<?php

namespace Generators\Rules;

use ClypperTechnology\RolePricing\Rules\Rule;

class RuleGenerator
{

    public static function Random()
    {
        $type = RuleTypeGenerator::Random();
        $quantity_type = RuleTypeGenerator::Random();

        return new Rule(
            $type,
            self::generateValue($type),
            self::generateValue($quantity_type),
            $quantity_type
        );
    }

    public static function with($type, $value, $quantity = 0, $quantity_type = Rule::TYPE_PERCENT): Rule
    {
        return new Rule(
            $type,
            $value,
            $quantity,
            $quantity_type
        );
    }

    private static function generateValue(string $type): int
    {
        return match ($type) {
            "percent" => random_int(1, 100),
            "percent_add" => random_int(1, 1000),
            "fixed" => random_int(1, 100),
            "fixed_set" => random_int(1, 1_000_000),
            "fixed_add" => random_int(1, 100),
        };
    }
}
