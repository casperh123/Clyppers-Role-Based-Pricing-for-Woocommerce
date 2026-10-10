<?php

namespace Tests\Integration\Storefront;

use ClypperTechnology\RolePricing\Storefront\BannerView;

/**
 * The banner's HTML: escaped, translatable, in the theme's sale badge markup, no inline styles.
 */
final class BannerRendererTest extends StorefrontTestCase
{
    private static function view( ?string $message = 'Buy 3+ and save 10%', ?float $price = 75.0 ): BannerView
    {
        return new BannerView( 1, $message, 3, $price );
    }

    public function testSaleBadgeUsesWooCommercesSaleBadgeMarkupByDefault(): void
    {
        $html = $this->renderer->sale_badge( self::view() );

        $this->assertSame( '<span class="onsale crbp-volume-badge" title="Buy 3+ and save 10%">Volume discount!</span>', $html );
    }

    public function testSaleBadgeCopiesTheThemesMarkup(): void
    {
        $html = $this->renderer->sale_badge( self::view(), 'div', 'ast-on-card-button ast-onsale-card' );

        $this->assertStringStartsWith( '<div class="ast-on-card-button ast-onsale-card crbp-volume-badge"', $html );
        $this->assertStringEndsWith( '</div>', $html );
    }

    public function testSaleBadgeEscapesTagClassesAndTooltip(): void
    {
        $html = $this->renderer->sale_badge( self::view( '<b>Buy</b> 3+ "now"' ), 'span"><script>', 'x" onclick="y' );

        $this->assertStringNotContainsString( '<script', $html );
        $this->assertStringNotContainsString( '" onclick', $html );
        $this->assertStringContainsString( 'title="Buy 3+ &quot;now&quot;"', $html );
    }

    public function testBlockSaleBadgeUsesTheBlocksMarkupAndAlignment(): void
    {
        $left    = $this->renderer->block_sale_badge( self::view(), 'left' );
        $invalid = $this->renderer->block_sale_badge( self::view(), 'sideways' );

        $this->assertStringContainsString( 'class="wc-block-components-product-sale-badge alignleft wc-block-components-product-sale-badge--align-left crbp-volume-badge"', $left );
        $this->assertStringContainsString( 'alignright', $invalid );
        $this->assertSame( 'Volume discount!', self::text( $left ) );
    }

    public function testFlatsomeBadge(): void
    {
        $badge     = $this->renderer->flatsome_badge( self::view() );
        $contained = $this->renderer->flatsome_badge( self::view(), true );

        $this->assertStringStartsWith( '<div class="callout badge badge-circle crbp-volume-badge-flatsome"', $badge );
        $this->assertStringStartsWith( '<div class="badge-container absolute left top z-1">', $contained );
        $this->assertSame( 'Volume discount!', self::text( $contained ) );
    }

    public function testQuantityPriceBox(): void
    {
        $html = $this->renderer->quantity_price_box( self::view() );

        $this->assertStringStartsWith( '<div class="crbp-qty-price">', $html );
        $this->assertStringContainsString( 'Unit price when buying 3 or more:', self::text( $html ) );
        $this->assertStringContainsString( html_entity_decode( wp_strip_all_tags( wc_price( 75 ) ) ), self::text( $html ) );
        $this->assertSame( '', $this->renderer->quantity_price_box( self::view( price: null ) ) );
    }

    public function testNoInlineStyles(): void
    {
        $html = $this->renderer->sale_badge( self::view() )
            . $this->renderer->block_sale_badge( self::view() )
            . $this->renderer->flatsome_badge( self::view(), true )
            . $this->renderer->quantity_price_box( self::view() );

        $this->assertStringNotContainsString( 'style=', $html );
    }
}
