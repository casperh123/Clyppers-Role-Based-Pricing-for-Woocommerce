<?php

namespace Generators\Rules;
use ClypperTechnology\RolePricing\Rules\ItemRule;
use ClypperTechnology\RolePricing\Rules\RoleRules;
use ClypperTechnology\RolePricing\Rules\Rule;

class RoleRuleGenerator
{
    public static function random(): RoleRules
    {
        $id = random_int(1, 10_000);
        $role_slug = "slug_{${$id}}";
        $is_active = boolval(random_int(0, 1));

        $global_rule = RuleGenerator::Random();
        $global_category_rule = RuleGenerator::Random();
        $category_ids = self::randomIntArray(random_int(1, 100));
        $product_rules = ItemRuleGenerator::RandomCollection(random_int(1, 100));
        $single_category_rules = ItemRuleGenerator::RandomCollection(random_int(1, 100));

        return new RoleRules(
            $id,
            $role_slug,
            $is_active,
            $global_rule,
            $global_category_rule,
            $category_ids,
            $product_rules,
            $single_category_rules
        );
    }

    public static function empty(bool $active = true) {
        $id = random_int(1, 10_000);
        $role_slug = "slug_{$id}";
        $is_active = $active;

        return new RoleRules(
            $id,
            $role_slug,
            $is_active,
        );
    }

    public static function withItemRules(
        array  $product_rules,
        array  $singe_category_rules,
        ?Rule  $global_rule = null,
        ?Rule $category_rule = null,
        array $categories = []
    ): RoleRules {
        $role_rules = self::empty();

        $role_rules->global_rule = $global_rule;
        $role_rules->categories = $categories;
        $role_rules->category_rule = $category_rule;
        $role_rules->products = $product_rules;
        $role_rules->single_categories = $singe_category_rules;

        return $role_rules;
    }

    private static function randomIntArray(int $size) {
        return array_map(fn() => random_int(0, 10_000), range(1, $size));
    }
}
