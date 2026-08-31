<?php

namespace ClypperTechnology\RolePricing\REST\DTOs;

defined('ABSPATH') || exit;

class ProductDTO
{
    public int $id;
    public string $name;
    public string $price_html;
    public string $image_url;

    public function __construct(
        string $id,
        string $name,
        string $price_html,
        string $url
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->price_html = $price_html;
        $this->image_url = $url;
    }

    public static function from( \WC_Product $product ): self {
        return new self(
            $product->get_id(),
            $product->get_title(),
            wc_price( floatval( $product->get_price()) ),
            wp_get_attachment_image_url( $product->get_image_id() )
        );
    }
}