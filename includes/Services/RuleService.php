<?php

namespace ClypperTechnology\RolePricing\Services;

use ClypperTechnology\RolePricing\Rules\RoleRules;
use InvalidArgumentException;
use ClypperTechnology\RolePricing\Cache\RuleCache;
use RuntimeException;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class RuleService {
    private RoleService $role_service;
    private RuleCache $rule_cache;

    public function __construct( RoleService $role_service )
    {
        $this->role_service = $role_service;
        $this->rule_cache = new RuleCache();
    }

    /**
     * Get rule for role
     *
     */
    public function get_rule_by_current_role(): RoleRules | null {
        $user_role = $this->role_service->get_user_role();

        return $this->get_rule_by_role($user_role);
    }

    /**
     * @return WP_Post[]
     */
    private function get_all_rule_posts(): array {
        return get_posts([
            'post_type'   => 'clypper_rbp',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
            'post_status' => 'any'
        ]);
    }
    
    private function get_rule_by_role(string $role_slug): ?RoleRules {
        $rule = $this->rule_cache->get_rule_by_role_slug($role_slug);

        if($rule) {
            return $rule;
        }

        $posts = get_posts([
            'post_type' => 'clypper_rbp',
            'numberposts' => 1,
            'title' => $role_slug
        ]);

        if(empty($posts)) {
            return null;
        }

        $rule = $this->rule_from_post($posts[0]);

        $this->rule_cache->add_rule($rule);
        
        return $rule;
    }

    private function rule_from_post(?WP_Post $post): ?RoleRules {
        if (! $post || $post->post_type !== 'clypper_rbp') {
            return null;
        }

        return RoleRules::from_post($post);
    }
    
    /**
     * Add rule
     *
     * @param string $slug rule name.
     * @return RoleRules Rule success
     * @throws InvalidArgumentException If rule already exists
     * @throws RuntimeException If creation fails
     */
    public function add_rule(string $slug): RoleRules {
        $rule = [
            'post_title'   => $slug,
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'clypper_rbp',
            'post_author'  => get_current_user_id(),
        ];

        $rule_id = wp_insert_post($rule);

        if (!$rule_id || is_wp_error($rule_id)) {
            throw new RuntimeException('Failed to create rule in database');
        }

        return new RoleRules($rule_id, $slug, false);
    }

    /**
     * Get RoleRules by ID
     */
    public function get_rules_by_id(int $rule_id): ?RoleRules {
        $rule = $this->rule_cache->get_rule($rule_id);

        if($rule) {
            return $rule;
        }

        $post = get_post($rule_id);
        $rule = $this->rule_from_post($post);
        $this->rule_cache->add_rule($rule);

        return $rule;
    }

    /**
     * Save RoleRules back to database
     */
    public function save_role_rules(RoleRules $role_rules): bool {
        $result = wp_update_post([
            'ID' => $role_rules->id,
            'post_title' => $role_rules->role_slug,
            'post_content' => wp_slash(
                wp_json_encode(
                    $role_rules->to_array(),
                    JSON_UNESCAPED_UNICODE
                )
            ),
            'post_author' => get_current_user_id(),
            'post_type' => 'clypper_rbp'
        ], true);

        return !is_wp_error($result);
    }

    /**
     * Get all RoleRules
     *
     * @return RoleRules[]
     */
    public function get_all_rules(): array
    {
        $posts = $this->get_all_rule_posts();
        $rules = array_map(fn($post) => RoleRules::from_post($post), $posts);

        foreach($rules as $rule) {
            $this->rule_cache->add_rule($rule);
        }

        return $rules;
    }

    public function copy_roles_from_rule(string $slug, string $copy_from_slug): ?RoleRules
    {
        $rule = $this->get_rule_by_role($slug);

        if(!$rule) {
            $rule = $this->add_rule($slug);
        }

        $copy_from_rule = $this->get_rule_by_role($copy_from_slug);

        if(!$copy_from_rule) {
            return null;
        }

        $rule->copy_from_rule($copy_from_rule);

        $this->save_role_rules($rule);

        return  $rule;
    }
}
