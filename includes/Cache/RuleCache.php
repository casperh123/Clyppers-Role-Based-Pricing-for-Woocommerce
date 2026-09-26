<?php
use ClypperTechnology\RolePricing\Rules\RoleRules;

defined( 'ABSPATH' ) || exit;

public class RuleCache {

    private RoleRule[] $rules;

    public function __construct() {
    }

    public function get_role(int $id): ?RoleRule {
        return $this->rules[$id] ?? null;
    }

    public function add_rule(RoleRules $rule): void {
        $this->rules[$rule->id] = $rule;
    }
}
