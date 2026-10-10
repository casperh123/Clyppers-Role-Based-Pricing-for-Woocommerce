<?php

namespace ClypperTechnology\RolePricing\Storefront;

defined('ABSPATH') || exit;

/**
 * The only place that turns a BannerView into HTML. All output is escaped here.
 */
final class BannerRenderer
{
    public const STYLE_HANDLE = 'crbp-storefront';

    /**
     * Short "Volume discount!" badge for product cards in product lists.
     */
    public function badge(BannerView $view, string $extra_classes = ''): string
    {
        if ($view->message === null) {
            return '';
        }

        $this->enqueue_style();

        return '<span class="' . esc_attr(trim("crbp-badge crbp-badge--short {$extra_classes}")) . '">'
            . esc_html__('Volume discount!', 'clyppers-role-based-pricing-for-woocommerce')
            . '</span>';
    }

    /**
     * Full message ("Buy 10+ and save 5%") for the product page.
     */
    public function message(BannerView $view, string $tag = 'p', string $extra_classes = ''): string
    {
        if ($view->message === null) {
            return '';
        }

        $this->enqueue_style();
        $tag = tag_escape($tag);

        return "<{$tag} class=\"" . esc_attr(trim("crbp-badge crbp-badge--full {$extra_classes}")) . '">'
            . wp_kses_post($view->message)
            . "</{$tag}>";
    }

    /**
     * "Unit price when buying N or more" box, shown under the product's price.
     */
    public function quantity_price_box(BannerView $view, string $extra_classes = ''): string
    {
        if ($view->quantity_price === null) {
            return '';
        }

        $this->enqueue_style();

        return '<div class="' . esc_attr(trim("crbp-qty-price {$extra_classes}")) . '">'
            . '<p class="crbp-qty-price__label">' . esc_html(sprintf(
                /* translators: %d: minimum quantity */
                __('Unit price when buying %d or more:', 'clyppers-role-based-pricing-for-woocommerce'),
                $view->min_quantity
            )) . '</p>'
            . '<p class="price crbp-qty-price__amount">' . wp_kses_post(wc_price($view->quantity_price)) . '</p>'
            . '</div>';
    }

    /**
     * The stylesheet is enqueued up front on shop pages; this covers product lists on other pages (e.g. a shortcode).
     */
    private function enqueue_style(): void
    {
        if (wp_style_is(self::STYLE_HANDLE, 'registered') && !wp_style_is(self::STYLE_HANDLE, 'enqueued')) {
            wp_enqueue_style(self::STYLE_HANDLE);
        }
    }
}
