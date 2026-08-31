<?php

namespace ClypperTechnology\RolePricing\Services;

use ClypperTechnology\RolePricing\Rules\RoleRules;
use InvalidArgumentException;
use RuntimeException;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class RuleService {
    private array $role_rules;
    private RoleService $role_service;

    public function __construct( RoleService $role_service )
    {
        $this->role_rules = array();
        $this->role_service = $role_service;
    }

    /**
     * Get rule for role
     *
     */
    public function get_rule_by_current_role(): RoleRules | null {
        $user_role = $this->role_service->get_user_role();

        return $this->get_rule_by_user_role($user_role);
    }

    /**
     * @return WP_Post[]
     */
    private function get_all_rules(): array {
        return get_posts([
            'post_type'   => 'clypper_rbp',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
            'post_status' => 'any'
        ]);
    }

    public function update_rule( RoleRules $rule ): bool {
        return $this->save_role_rules($rule);
    }

    /**
     * Delete rule
     */
    public function delete_rule( string $id ): void {
        wp_delete_post( $id, true );
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
        $rule = [
            'post_title'   => $role_slug,
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'clypper_rbp',
            'post_author'  => get_current_user_id(),
        ];

        $rule_id = wp_insert_post($rule);

        if (!$rule_id || is_wp_error($rule_id)) {
            throw new RuntimeException('Failed to create rule in database');
        }

        return new RoleRules($rule_id, $role_slug, false);
    }

    /**
     * Get RoleRules by ID
     */
    public function get_rules_by_id(int $rule_id): ?RoleRules {
        $post = get_post($rule_id);

        if (! $post || $post->post_type !== 'clypper_rbp') {
            return null;
        }

        return RoleRules::from_post($post);
    }

    /**
     * Save RoleRules back to database
     */
    public function save_role_rules(RoleRules $role_rules): bool {
        $result = wp_update_post([
            'ID' => $role_rules->id,
            'post_title' => $role_rules->role_slug,
            'post_content' => wp_json_encode($role_rules->to_array(), JSON_UNESCAPED_UNICODE),
            'post_author' => get_current_user_id(),
        ], true);

        return !is_wp_error($result);
    }

    /**
     * Get all RoleRules
     *
     * @return RoleRules[]
     */
    public function get_all_role_rules(): array {
        $posts = $this->get_all_rules();
        $roles = array_map(fn($post) => RoleRules::from_post($post), $posts);

        foreach($roles as $role) {
            $this->role_rules[$role->role_slug] = $role;
        }

        return $roles;
    }

    public function get_rule_by_user_role( string $user_role): ?RoleRules {
        if(array_key_exists($user_role, $this->role_rules)) {
            return $this->role_rules[$user_role];
        }

        $all_rules = $this->get_all_role_rules();
        $rule = array_find($all_rules, fn( RoleRules $rule ) => $rule->role_slug === $user_role );

        $this->role_rules[$user_role] = $rule;

        return $rule;
    }
}