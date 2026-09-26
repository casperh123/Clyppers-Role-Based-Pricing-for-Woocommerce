<?php
use ClypperTechnology\RolePricing\Rules\RoleRules;

defined( 'ABSPATH' ) || exit;

class RuleCache {

    private RoleRules[] $rules = [];

    public function __construct() {
    }

    public function get_rule(int $id): ?RoleRules {
        return $this->rules[$id] ?? null;
    }

    public function get_rule_by_role(string $role): ?RoleRules {
        foreach ($this->rules as $id => $rule) {
            if($rule->slug == $role) {
                return $rule;
            }
        }

        return null;
    }

    public function add_rule(?RoleRules $rule): void {
        if($rule == null) {
            return;
        }

        $this->rules[$rule->id] = $rule;
    }
}
