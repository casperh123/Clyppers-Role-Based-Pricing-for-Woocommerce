<?php

namespace ClypperTechnology\RolePricing\Rules;

defined('ABSPATH') || exit;

/**
 * RoleRules - Complete pricing rules for a user role
 */
class RoleRules {
    /**
     * @param int[] $categories;
     * @param ItemRule[] $products
     * @param ItemRule[] $single_categories
     */
    public function __construct(
        public int    $id,
        public string $role_slug,
        public bool   $rule_active = false,
        public ?Rule  $global_rule = null,
        public ?Rule  $category_rule = null,
        public array  $categories = [],           // General category mappings [['123' => '123']]
        public array  $products = [],             // ItemRule[]
        public array  $single_categories = []     // CategoryRule[]
    ) {}

    /**
     * Create RoleRules from WordPress post
     */
    public static function from_post(\WP_Post $post): self {
        $content = json_decode(wp_unslash($post->post_content), true);

        if (!is_array($content)) {
            $content = [];
        }

        $content['id'] = $post->ID;
        $content['role_slug'] = $post->post_title;

        return self::from_array($content);
    }

    /**
     * Build RoleRules from stored or request data. Missing or malformed fields fall back to "no rule".
     */
    public static function from_array(array $json): self {
        $is_array = fn(string $key) => is_array($json[$key] ?? null);

        return new self(
            id: is_numeric($json['id'] ?? null) ? (int)$json['id'] : 0,
            role_slug: is_string($json['role_slug'] ?? null) ? $json['role_slug'] : '',
            rule_active: in_array($json['rule_active'] ?? '', ['on', true], true),
            global_rule: $is_array('global_rule') ? Rule::from_array($json['global_rule']) : null,
            category_rule: $is_array('category_rule') ? Rule::from_array($json['category_rule']) : null,
            categories: $is_array('categories') ? self::positive_ids($json['categories']) : [],
            products: $is_array('products') ? self::key_by_item_id($json['products']) : [],
            single_categories: $is_array('single_categories') ? self::key_by_item_id($json['single_categories']) : [],
        );
    }

    /**
     * @return int[]
     */
    private static function positive_ids(array $ids): array {
        $ids = array_map(fn($id) => is_numeric($id) ? (int)$id : 0, $ids);

        return array_values(array_unique(array_filter($ids, fn(int $id) => $id > 0)));
    }

    /**
     * Build an updated rule from request data, keeping the identity (id and role) of the existing rule.
     */
    public static function from_update(RoleRules $existing, array $json): self {
        $json['id'] = $existing->id;
        $json['role_slug'] = $existing->role_slug;

        return self::from_array($json);
    }

    public function copy_from_rule(RoleRules $from): void
    {
      $this->products = $from->products;
      $this->single_categories = $from->single_categories;
      $this->categories = $from->categories;
      $this->global_rule = $from->global_rule;
      $this->category_rule = $from->category_rule;
    }


    /**
     * Convert to array for storage (matches your exact structure)
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'role_slug' => $this->role_slug,
            'rule_active' => $this->rule_active ? 'on' : '',
            'global_rule' => $this->global_rule?->to_array(),
            'category_rule' => $this->category_rule?->to_array(),
            'categories' => $this->categories,
            'products' => array_map(fn($p) => $p->to_array(), array_values($this->products)),
            'single_categories' => array_map(fn($c) => $c->to_array(), array_values($this->single_categories)),
        ];
    }

    /**
     * @param array $items
     * @return ItemRule[]
     */
    private static function key_by_item_id(array $items): array {
        if(!$items) {
            return [];
        }

        $item_list = [];

        foreach($items as $item_array) {
            $item = ItemRule::from_array($item_array);

            if ($item) {
                $item_list[$item->id] = $item;
            }
        }

        return $item_list;
    }

    public function get_rule_count(): int {
        return sizeof($this->products) + sizeof($this->single_categories);
    }

    public function get_applicable_rule( $product_id, array $category_ids, int $quantity = 1): ?PricingRule {
        $product_rule = $this->get_rule_by_product_id( $product_id );

        //Check for product rules
        if ($product_rule && $product_rule->rule_applies($quantity)) {
            return $product_rule;
        }

        $category_rule = $this->get_single_category_rule( $category_ids );


        if ( $category_rule && $category_rule->rule_applies($quantity)) {
            return $category_rule;
        }

        //Check for general category reductions / increases
        if ($this->has_categories() && $this->category_rule_applies()) {
            // Check if product is in any selected general categories
            if ( $this->matches_any_category( $category_ids ) ) {
                return $this->category_rule;
            }
        }

        if ( $this->global_rule_applies() ) {
            return $this->global_rule;
        }

        return null;
    }

    public function get_quantity_rule( $product_id, array $category_ids): ?ItemRule {
        $product_rule = $this->get_rule_by_product_id( $product_id );

        //Check for product rules
        if ($product_rule && $product_rule->quantity_rule->rule_applies()) {
            return $product_rule;
        }

        $category_rule = $this->get_single_category_rule( $category_ids );

        if ( $category_rule && $category_rule->quantity_rule->rule_applies()) {
            return $category_rule;
        }

        return null;
    }

    /**
     * Get product rules by product ID
     * @param int $product_id
     * @return ?ItemRule
     */
    private function get_rule_by_product_id( int $product_id ): ?ItemRule
    {
        return $this->products[$product_id] ?? null;
    }

    private function has_categories(): bool {
        return ! empty( $this->categories );
    }

    private function category_rule_applies(): bool {
        return isset($this->category_rule) && $this->category_rule->rule_applies();
    }

    private function global_rule_applies(): bool {
        return isset($this->global_rule) && $this->global_rule->rule_applies();
    }

    /**
     * @param int[] $category_ids
     * @return ?ItemRule
     */
    private function get_single_category_rule( array $category_ids ): ?ItemRule {
        foreach ($category_ids as $cat_id) {
            if (isset($this->single_categories[$cat_id])) {
                return $this->single_categories[$cat_id];
            }
        }
        return null;
    }

    /**
     * @param int[] $category_ids
     * @return bool
     */
    private function matches_any_category( array $category_ids ): bool {
        return ! empty(array_intersect($category_ids, $this->categories));
    }
}
