<?php

namespace Generators\Rules;

class RuleTypeGenerator
{
    public static function Random(): string
    {
        $key = random_int(0, 4);

        return match ($key) {
            0 => "percent",
            1 => "percent_add",
            2 => "fixed",
            3 => "fixed_set",
            4 => "fixed_add",
        };
    }
}
