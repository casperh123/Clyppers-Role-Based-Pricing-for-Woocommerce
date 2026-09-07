<?php

namespace Generators\Rules;

use ClypperTechnology\RolePricing\Rules\Rule;
use ClypperTechnology\RolePricing\Rules\RuleType;

class RuleGenerator
{

    public static function Random()
    {
        $type = RuleTypeGenerator::Random();

        return new Rule(
            $type,
            self::generateValue($type),
        );
    }

    public static function with($type, $value): Rule
    {
        return new Rule(
            $type,
            $value,
        );
    }

    private static function generateValue(RuleType $type): int
    {
        return match ($type) {
            RuleType::TYPE_PERCENT => random_int(1, 100),
            RuleType::TYPE_FIXED_ADD => random_int(1, 1000),
            RuleType::TYPE_FIXED => random_int(1, 100),
            RuleType::TYPE_FIXED_SET => random_int(1, 1_000_000),
            RuleType::TYPE_PERCENT_ADD => random_int(1, 100),
        };
    }
}
