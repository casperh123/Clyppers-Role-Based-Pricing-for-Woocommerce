<?php

namespace Generators\Rules;

use ClypperTechnology\RolePricing\Rules\RuleType;

class RuleTypeGenerator
{
    public static function Random(): RuleType
    {
        $key = random_int(0, 4);

        return match ($key) {
            0 => RuleType::TYPE_PERCENT,
            1 => RuleType::TYPE_PERCENT_ADD,
            2 => RuleType::TYPE_FIXED,
            3 => RuleType::TYPE_FIXED_SET,
            4 => RuleType::TYPE_FIXED_ADD,
        };
    }
}
