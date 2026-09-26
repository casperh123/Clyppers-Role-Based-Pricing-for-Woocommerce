<?php

namespace ClypperTechnology\RolePricing\Services;

use ClypperTechnology\RolePricing\Repositories\RuleRepository;
use ClypperTechnology\RolePricing\Rules\RoleRules;
use InvalidArgumentException;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

class RuleService {
    private RoleService $role_service;
    private RuleRepository $rule_repository;

    public function __construct( RoleService $role_service )
    {
        $this->role_service = $role_service;
        $this->rule_repository = new RuleRepository();
    }

    /**
     * Get rule for role
     */
    public function get_rule_by_current_role(): RoleRules | null {
        $user_role = $this->role_service->get_user_role();

        return $this->rule_repository->get_rule_by_role_slug($user_role);
    }

    /**
     * Add rule
     *
     * @param string $role_slug rule name.
     * @return RoleRules Rule success
     * @throws InvalidArgumentException If rule already exists
     * @throws RuntimeException If creation fails
     */
    public function add_rule(string $role_slug): RoleRules {
        return $this->rule_repository->add_rule($role_slug);
    }

    /**
     * Get RoleRules by ID
     */
    public function get_rule_by_id(int $rule_id): ?RoleRules {
        return $this->rule_repository->get_rule_by_id($rule_id);
    }

    /**
     * Save RoleRules back to database
     */
    public function update_rule(RoleRules $rule): bool {
        return $this->rule_repository->update_rule($rule);
    }

    /**
     * Get all RoleRules
     *
     * @return RoleRules[]
     */
    public function get_all_rules(): array
    {
        return $this->rule_repository->get_all_rules();
    }

    public function copy_roles_from_rule(string $slug, string $copy_from_slug): ?RoleRules
    {
        $rule = $this->rule_repository->get_rule_by_role_slug($slug);

        if(!$rule) {
            $rule = $this->add_rule($slug);
        }

        $copy_from_rule = $this->rule_repository->get_rule_by_role_slug($copy_from_slug);

        if(!$copy_from_rule) {
            return null;
        }

        $rule->copy_from_rule($copy_from_rule);

        $this->update_rule($rule);

        return  $rule;
    }
}
