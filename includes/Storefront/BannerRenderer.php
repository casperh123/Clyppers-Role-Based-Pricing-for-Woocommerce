<?php

namespace ClypperTechnology\RolePricing\Storefront;

defined('ABSPATH') || exit;

/**
 * The only place that turns a BannerView into HTML. All output is escaped here.
 */
final class BannerRenderer
{
    public const STYLE_HANDLE = 'crbp-storefront';
    public const SCRIPT_HANDLE = 'crbp-storefront';

    /**
     * "Volume discount!" badge in the markup of the theme's sale badge (by default WooCommerce's `span.onsale`), so
     * the theme styles and positions it like its own sale badge.
     *
     * @param string $tag The theme's sale badge element, e.g. "span"
     * @param string $classes The theme's sale badge classes, e.g. "onsale"
     */
    public function sale_badge(BannerView $view, string $tag = 'span', string $classes = 'onsale'): string
    {
        $this->enqueue_assets();
        $tag = tag_escape($tag) ?: 'span';

        return "<{$tag} class=\"" . esc_attr(trim("{$classes} crbp-volume-badge")) . '"' . $this->title($view) . '>'
            . $this->badge_text()
            . "</{$tag}>";
    }

    /**
     * The badge in the markup of WooCommerce's Product Sale Badge block.
     *
     * @param string $align The sale badge block's alignment: left, center or right.
     */
    public function block_sale_badge(BannerView $view, string $align = 'right'): string
    {
        $this->enqueue_assets();
        $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'right';

        return '<div class="wp-block-woocommerce-product-sale-badge crbp-volume-badge-block">'
            . '<div class="wc-block-components-product-sale-badge align' . $align
            . ' wc-block-components-product-sale-badge--align-' . $align . ' crbp-volume-badge"' . $this->title($view) . '>'
            . '<span class="wc-block-components-product-sale-badge__text">' . $this->badge_text() . '</span>'
            . '</div></div>';
    }

    /**
     * The badge in Flatsome's badge markup. Inside Flatsome's badge container it stacks with the sale badge.
     */
    public function flatsome_badge(BannerView $view, bool $with_container = false): string
    {
        $this->enqueue_assets();

        $badge = '<div class="callout badge badge-circle crbp-volume-badge-flatsome"' . $this->title($view) . '>'
            . '<div class="badge-inner on-sale"><span class="onsale">' . $this->badge_text() . '</span></div>'
            . '</div>';

        return $with_container ? '<div class="badge-container absolute left top z-1">' . $badge . '</div>' : $badge;
    }

    /**
     * "Unit price when buying N or more" box, shown under the price on the product page.
     */
    public function quantity_price_box(BannerView $view): string
    {
        if ($view->quantity_price === null) {
            return '';
        }

        $this->enqueue_assets();

        return '<div class="crbp-qty-price">'
            . '<p class="crbp-qty-price__label">' . esc_html(sprintf(
                /* translators: %d: minimum quantity */
                __('Unit price when buying %d or more:', 'clyppers-role-based-pricing-for-woocommerce'),
                $view->min_quantity
            )) . '</p>'
            . '<p class="price crbp-qty-price__amount">' . wp_kses_post(wc_price($view->quantity_price)) . '</p>'
            . '</div>';
    }

    private function badge_text(): string
    {
        return esc_html__('Volume discount!', 'clyppers-role-based-pricing-for-woocommerce');
    }

    /**
     * The full offer ("Buy 3+ for $75.00 per item") as a tooltip on the badge.
     */
    private function title(BannerView $view): string
    {
        return $view->message === null ? '' : ' title="' . esc_attr(wp_strip_all_tags($view->message)) . '"';
    }

    /**
     * The assets are enqueued up front on shop pages; this covers product lists on other pages (e.g. a shortcode).
     */
    private function enqueue_assets(): void
    {
        if (wp_style_is(self::STYLE_HANDLE, 'registered') && !wp_style_is(self::STYLE_HANDLE, 'enqueued')) {
            wp_enqueue_style(self::STYLE_HANDLE);
        }

        if (wp_script_is(self::SCRIPT_HANDLE, 'registered') && !wp_script_is(self::SCRIPT_HANDLE, 'enqueued')) {
            wp_enqueue_script(self::SCRIPT_HANDLE);
        }
    }
}
