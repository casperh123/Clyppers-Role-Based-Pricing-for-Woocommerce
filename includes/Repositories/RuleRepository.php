<?php

namespace ClypperTechnology\RolePricing\Repositories;

use ClypperTechnology\RolePricing\Cache\RuleCache;
use ClypperTechnology\RolePricing\Rules\RoleRules;
use InvalidArgumentException;
use RuntimeException;
use WP_Post;

class RuleRepository
{
    private RuleCache $rule_cache;

    public function __construct()
    {
        $this->rule_cache = new RuleCache();
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

    public function get_rule_by_role_slug(string $role_slug): ?RoleRules {
        $rule = $this->rule_cache->get_rule_by_role_slug($role_slug);

        if($rule) {
            return $rule;
        }

        $posts = get_posts([
            'post_type'      => 'clypper_rbp',
            'post_status'    => 'any',
            'numberposts'    => 1,
            'title'          => $role_slug,
        ]);

        if(empty($posts)) {
            return null;
        }

        $rule = $this->rule_from_post($posts[0]);

        $this->rule_cache->add_rule($rule);

        return $rule;
    }

    /**
     * Get RoleRules by ID
     */
    public function get_rule_by_id(int $rule_id): ?RoleRules {
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
    public function update_rule(RoleRules $rule): bool {
        $result = wp_update_post([
            'ID' => $rule->id,
            'post_title' => $rule->role_slug,
            'post_content' => wp_slash(
                wp_json_encode(
                    $rule->to_array(),
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
        $posts = get_posts([
            'post_type'   => 'clypper_rbp',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
            'post_status' => 'any'
        ]);;
        $rules = array_map(fn($post) => RoleRules::from_post($post), $posts);

        foreach($rules as $rule) {
            $this->rule_cache->add_rule($rule);
        }

        return $rules;
    }

    private function rule_from_post(?WP_Post $post): ?RoleRules {
        if (! $post || $post->post_type !== 'clypper_rbp') {
            return null;
        }

        return RoleRules::from_post($post);
    }
}