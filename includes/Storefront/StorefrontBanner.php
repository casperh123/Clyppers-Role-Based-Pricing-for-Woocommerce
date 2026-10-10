<?php

namespace ClypperTechnology\RolePricing\Storefront;

use ClypperTechnology\RolePricing\Services\RuleService;
use ClypperTechnology\RolePricing\Services\SettingsService;
use WC_Product;

defined('ABSPATH') || exit;

/**
 * Puts the quantity discount on the storefront:
 * - a "Volume discount!" badge next to the theme's sale badge, on product cards and the product page
 *   (can be switched off in the plugin's settings, see SettingsService);
 * - a "Unit price when buying N or more" box under the price on the product page.
 *
 * The badge goes where the sale badge is:
 * - Product on sale: appended to the sale badge's HTML (`woocommerce_sale_flash`, which themes such as Astra also
 *   use for their own sale badge markup), so it sits right next to it.
 * - Not on sale: printed where WooCommerce prints the sale badge, with the theme's sale badge class.
 * - Block product lists (Product Collection): in the Product Sale Badge block, or after the Product Image block
 *   when the card has no sale badge block. WooCommerce's block templates also fire the classic hooks for
 *   compatibility; those are ignored while a block list renders, so each card gets one badge.
 * Where a theme floats the sale badge over the image, assets/js/crbp-storefront.js moves ours below it.
 */
final class StorefrontBanner
{
    private static bool $booted = false;

    /** @var bool[] For each block product list rendering right now (they can nest): whether its cards have a Product Sale Badge block. */
    private array $block_lists = [];

    /** Product whose card in the current block list already has its badge. */
    private ?int $block_badged_product = null;

    /** @var ?array{0: string, 1: string} The theme's sale badge [tag, classes], once looked up. */
    private ?array $theme_sale_badge = null;

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
        wp_register_script(BannerRenderer::SCRIPT_HANDLE, CRBP_PLUGIN_URL . 'assets/js/crbp-storefront.js', [], CRBP_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
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
            // Badge for products that aren't on sale, where WooCommerce prints the sale badge (priority 10).
            'loop_badge'        => ['woocommerce_before_shop_loop_item_title', 10],
            'product_badge'     => ['woocommerce_before_single_product_summary', 10],
            // Right after the price (priority 10) on the product page.
            'product_price_box' => ['woocommerce_single_product_summary', 11],
        ];

        if (self::is_flatsome()) {
            // Where Flatsome stores have always shown the badge.
            $hooks['loop_badge'] = ['woocommerce_before_shop_loop_item', 999];
            $hooks['product_badge'] = ['flatsome_custom_single_product_1', 999];
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

        add_filter('woocommerce_sale_flash', [$this, 'append_to_sale_flash'], 999, 3);

        add_filter('render_block_data', [$this, 'enter_block_list']);
        add_filter('render_block_woocommerce/product-template', [$this, 'leave_block_list']);
        add_filter('render_block_woocommerce/product-sale-badge', [$this, 'append_to_block_sale_badge'], 10, 3);
        add_filter('render_block_woocommerce/product-image', [$this, 'append_to_block_image'], 10, 3);
    }

    // Classic templates

    public function append_to_sale_flash(mixed $html, mixed $post = null, mixed $product = null): string
    {
        $html = (string)$html;
        $view = $this->block_lists ? null : $this->badge_view($product);

        if (!$view) {
            return $html;
        }

        if (self::is_flatsome()) {
            return $html . $this->renderer->flatsome_badge($view);
        }

        // Some themes blank the sale badge here and print their own elsewhere (Astra in product lists).
        [$tag, $classes] = self::badge_markup_of($html) ?? $this->theme_sale_badge($product);

        return $html . $this->renderer->sale_badge($view, $tag, $classes);
    }

    public function print_loop_badge(): void
    {
        $this->print_badge_without_sale();
    }

    public function print_product_badge(): void
    {
        $this->print_badge_without_sale();
    }

    public function print_product_price_box(): void
    {
        global $product;
        $view = $product instanceof WC_Product ? $this->data->for_product($product) : null;

        $this->output($view ? $this->renderer->quantity_price_box($view) : '');
    }

    /**
     * Products on sale get the badge through the sale badge filter; this covers the rest.
     */
    private function print_badge_without_sale(): void
    {
        global $product;

        if ($this->block_lists || !$product instanceof WC_Product || $product->is_on_sale()) {
            return;
        }

        $view = $this->badge_view($product);

        if (!$view) {
            return;
        }

        if (self::is_flatsome()) {
            $this->output($this->renderer->flatsome_badge($view, true));
        } else {
            [$tag, $classes] = $this->theme_sale_badge($product);
            $this->output($this->renderer->sale_badge($view, $tag, $classes));
        }
    }

    /**
     * The tag and classes the theme uses for its sale badge. Themes can replace WooCommerce's `span.onsale` through
     * the `woocommerce_sale_flash` filter (Astra does), so ask the filter rather than assume.
     *
     * @return array{0: string, 1: string}
     */
    private function theme_sale_badge(WC_Product $product): array
    {
        // Astra's "modern" shop style prints its own sale badge instead of going through the filter.
        if (function_exists('astra_is_shop_page_modern_style') && astra_is_shop_page_modern_style()) {
            return ['span', 'ast-on-card-button ast-onsale-card'];
        }

        if ($this->theme_sale_badge === null) {
            remove_filter('woocommerce_sale_flash', [$this, 'append_to_sale_flash'], 999);
            // Only the theme's tag and classes are read, so the badge text doesn't matter.
            $html = apply_filters('woocommerce_sale_flash', '<span class="onsale"></span>', get_post($product->get_id()), $product); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter, asked for the theme's sale badge markup
            add_filter('woocommerce_sale_flash', [$this, 'append_to_sale_flash'], 999, 3);

            $this->theme_sale_badge = self::badge_markup_of((string)$html) ?? ['span', 'onsale'];
        }

        return $this->theme_sale_badge;
    }

    /**
     * Tag and classes of the outermost element of a sale badge's HTML.
     *
     * @return ?array{0: string, 1: string}
     */
    private static function badge_markup_of(string $html): ?array
    {
        if (!preg_match('/^\s*<([a-z][a-z0-9]*)\b[^>]*?\bclass\s*=\s*["\']([^"\']*)["\']/i', $html, $match)) {
            return null;
        }

        return [strtolower($match[1]), trim($match[2])];
    }

    // Block product lists

    /**
     * Runs just before a block renders (unlike pre_render_block, only for blocks that really render, so every
     * enter is matched by leave_block_list).
     */
    public function enter_block_list(array $parsed_block): array
    {
        if (($parsed_block['blockName'] ?? '') === 'woocommerce/product-template') {
            $this->block_lists[] = self::contains_block($parsed_block['innerBlocks'] ?? [], 'woocommerce/product-sale-badge');
            $this->block_badged_product = null;
        }

        return $parsed_block;
    }

    public function leave_block_list(string $html): string
    {
        array_pop($this->block_lists);
        $this->block_badged_product = null;

        return $html;
    }

    public function append_to_block_sale_badge(string $html, array $block, ?\WP_Block $instance = null): string
    {
        $view = $this->block_card_badge_view($instance);

        return $view ? $html . $this->renderer->block_sale_badge($view, $block['attrs']['align'] ?? 'right') : $html;
    }

    /**
     * For cards without a Product Sale Badge block.
     */
    public function append_to_block_image(string $html, array $block, ?\WP_Block $instance = null): string
    {
        $cards_have_sale_badge = end($this->block_lists);
        $view = $cards_have_sale_badge ? null : $this->block_card_badge_view($instance);

        if (!$view) {
            return $html;
        }

        // Inside the image block's container, where WooCommerce puts its own sale badge, so it's positioned the same.
        $badge = $this->renderer->block_sale_badge($view);
        $end = strrpos($html, '</div>');

        return $end === false ? $html . $badge : substr_replace($html, $badge, $end, 0);
    }

    private static function contains_block(array $blocks, string $name): bool
    {
        foreach ($blocks as $block) {
            if (($block['blockName'] ?? '') === $name || self::contains_block($block['innerBlocks'] ?? [], $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The badge for a card in a block product list, unless the card already has it.
     */
    private function block_card_badge_view(?\WP_Block $instance): ?BannerView
    {
        $product_id = (int)($instance->context['postId'] ?? 0);

        if (!$this->block_lists || !$product_id || $product_id === $this->block_badged_product) {
            return null;
        }

        $view = $this->badge_view(wc_get_product($product_id));

        if ($view) {
            $this->block_badged_product = $product_id;
        }

        return $view;
    }

    // Shared

    public static function enqueue_on_shop_pages(): void
    {
        if (is_woocommerce() || is_cart()) {
            wp_enqueue_style(BannerRenderer::STYLE_HANDLE);
            wp_enqueue_script(BannerRenderer::SCRIPT_HANDLE);
        }
    }

    /**
     * What the badge shows for this product, or null when the badge is off or there's no discount to advertise.
     */
    private function badge_view(mixed $product): ?BannerView
    {
        if (!SettingsService::show_volume_badge() || !$product instanceof WC_Product) {
            return null;
        }

        $view = $this->data->for_product($product);

        return $view && $view->message !== null ? $view : null;
    }

    private function output(string $html): void
    {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in BannerRenderer
    }

    private static function is_flatsome(): bool
    {
        return 'flatsome' === get_template();
    }
}
