<?php

namespace ClypperTechnology\RolePricing\Rules;

enum RuleType: string
{
    case TYPE_PERCENT = 'percent';
    case TYPE_PERCENT_ADD = 'percent_add';
    case TYPE_FIXED = 'fixed';
    case TYPE_FIXED_ADD = 'fixed_add';
    case TYPE_FIXED_SET = 'fixed_set';
}
