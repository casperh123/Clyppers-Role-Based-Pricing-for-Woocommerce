<?php

namespace ClypperTechnology\RolePricing\Storefront;

use ClypperTechnology\RolePricing\Services\RuleService;

defined('ABSPATH') || exit;

/**
 * Puts the quantity discount on the storefront: a badge and price box on every product in a list, and the full
 * message and price box on the product page.
 *
 * Product lists come in two kinds:
 * - PHP template loops (classic themes, shortcodes): WooCommerce's loop hooks.
 * - Block lists (Product Collection, in block themes or placed on any page): filters on the Product Image and
 *   Product Price blocks inside `woocommerce/product-template`. WooCommerce's block templates also fire the loop
 *   hooks for compatibility, so those are ignored while a block list renders; each card gets the banner once.
 */
final class StorefrontBanner
{
    private static bool $booted = false;

    /** How many block product lists are rendering right now (they can be nested). */
    private int $block_lists = 0;

    public function __construct(
        private readonly BannerData $data,
        private readonly BannerRenderer $renderer,
    ) {}

    /**
     * Runs on `wp`, once the theme is known and before scripts are enqueued.
     */
    public static function boot(RuleService $rule_service): void
    {
        if (self::$booted || is_admin()) {
            return;
        }

        self::$booted = true;

        wp_register_style(BannerRenderer::STYLE_HANDLE, CRBP_PLUGIN_URL . 'assets/css/crbp.css', [], CRBP_VERSION);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_on_shop_pages']);

        (new self(new BannerData($rule_service), new BannerRenderer()))->register();
    }

    /**
     * Where each piece goes, as [hook, priority]. Themes that move WooCommerce's hooks around can change these with
     * the `crbp_banner_hooks` filter; set an entry to false to turn it off.
     *
     * @return array<string, array{0: string, 1: int}|false>
     */
    public static function hooks(): array
    {
        $hooks = [
            // Product lists: badge after the image, price box after the price (both at priority 10).
            'loop_badge'        => ['woocommerce_before_shop_loop_item_title', 11],
            'loop_price_box'    => ['woocommerce_after_shop_loop_item_title', 11],
            // Product page: message and price box right after the price (priority 10).
            'product_message'   => ['woocommerce_single_product_summary', 11],
            'product_price_box' => ['woocommerce_single_product_summary', 12],
        ];

        if (self::is_flatsome()) {
            // Where Flatsome stores have always shown the badge and message.
            $hooks['loop_badge'] = ['woocommerce_before_shop_loop_item', 999];
            $hooks['product_message'] = ['flatsome_custom_single_product_1', 999];
        }

        return apply_filters('crbp_banner_hooks', $hooks);
    }

    public function register(): void
    {
        foreach (self::hooks() as $piece => $hook) {
            if ($hook && method_exists($this, "print_{$piece}")) {
                add_action($hook[0], [$this, "print_{$piece}"], $hook[1]);
            }
        }

        add_filter('render_block_data', [$this, 'enter_block_list']);
        add_filter('render_block_woocommerce/product-template', [$this, 'leave_block_list']);
        add_filter('render_block_woocommerce/product-image', [$this, 'append_block_badge'], 10, 3);
        add_filter('render_block_woocommerce/product-price', [$this, 'append_block_price_box'], 10, 3);
    }

    public function print_loop_badge(): void
    {
        if ($this->block_lists > 0) {
            return;
        }

        $view = $this->current_view();
        $html = $view ? $this->renderer->badge($view, self::is_flatsome() ? 'onsale' : '') : '';

        $this->output(self::is_flatsome() ? $this->in_flatsome_badge($html) : $html);
    }

    public function print_loop_price_box(): void
    {
        if ($this->block_lists > 0) {
            return;
        }

        $view = $this->current_view();

        $this->output($view ? $this->renderer->quantity_price_box($view, 'crbp-qty-price--loop') : '');
    }

    public function print_product_message(): void
    {
        $view = $this->current_view();

        if (self::is_flatsome()) {
            $this->output($view ? $this->in_flatsome_badge($this->renderer->message($view, 'span', 'onsale')) : '');
        } else {
            $this->output($view ? $this->renderer->message($view) : '');
        }
    }

    public function print_product_price_box(): void
    {
        $view = $this->current_view();

        $this->output($view ? $this->renderer->quantity_price_box($view) : '');
    }

    /**
     * Runs just before a block renders (unlike pre_render_block, only for blocks that really render, so every
     * enter is matched by leave_block_list).
     */
    public function enter_block_list(array $parsed_block): array
    {
        if (($parsed_block['blockName'] ?? '') === 'woocommerce/product-template') {
            $this->block_lists++;
        }

        return $parsed_block;
    }

    public function leave_block_list(string $html): string
    {
        $this->block_lists = max(0, $this->block_lists - 1);

        return $html;
    }

    /**
     * Badge under the image of each product card in a block product list.
     */
    public function append_block_badge(string $html, array $block, ?\WP_Block $instance = null): string
    {
        $view = $this->block_list_view($instance);

        return $view ? $html . $this->renderer->badge($view) : $html;
    }

    /**
     * Price box under the price of each product card in a block product list.
     */
    public function append_block_price_box(string $html, array $block, ?\WP_Block $instance = null): string
    {
        $view = $this->block_list_view($instance);

        return $view ? $html . $this->renderer->quantity_price_box($view, 'crbp-qty-price--loop') : $html;
    }

    public static function enqueue_on_shop_pages(): void
    {
        if (is_woocommerce() || is_cart()) {
            wp_enqueue_style(BannerRenderer::STYLE_HANDLE);
        }
    }

    /**
     * The product WooCommerce is currently rendering (in a loop, or the product page).
     */
    private function current_view(): ?BannerView
    {
        global $product;

        return $product instanceof \WC_Product ? $this->data->for_product($product) : null;
    }

    /**
     * The card's product, when the block is part of a block product list (not e.g. the product page's own price).
     */
    private function block_list_view(?\WP_Block $instance): ?BannerView
    {
        $product_id = $instance->context['postId'] ?? null;

        if ($this->block_lists === 0 || !$product_id) {
            return null;
        }

        $product = wc_get_product($product_id);

        return $product ? $this->data->for_product($product) : null;
    }

    private function output(string $html): void
    {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in BannerRenderer
    }

    private static function is_flatsome(): bool
    {
        return 'flatsome' === get_template();
    }

    /**
     * Flatsome's own badge markup, so the badge looks and sits the way Flatsome stores are used to.
     */
    private function in_flatsome_badge(string $html): string
    {
        if ($html === '') {
            return '';
        }

        return '<div class="badge-container absolute right top z-1 crbp-flatsome">'
            . '<div class="callout badge badge-circle"><div class="badge-inner secondary on-sale">' . $html . '</div></div>'
            . '</div>';
    }
}
