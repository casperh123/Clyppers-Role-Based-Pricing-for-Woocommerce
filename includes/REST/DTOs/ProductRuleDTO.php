<?php

namespace ClypperTechnology\RolePricing\REST\DTOs;

use ClypperTechnology\RolePricing\Rules\ItemRule;

defined('ABSPATH') || exit;

class ProductRuleDTO
{
    public ItemRule $rule;
    public ?string $image_url;
    public string $price_html;

    public function __construct(
        ItemRule $rule,
        ?string  $image_url,
        string $price_html
    ) {
        $this->rule = $rule;
        $this->image_url = $image_url;
        $this->price_html = $price_html;
    }

    public function to_array(): array
    {
        return [
            ...$this->rule->to_array(),
            'image_url' => $this->image_url,
            'price_html' => $this->price_html
        ];
    }
}