<?php

namespace Tests\Integration\Storefront;

use ClypperTechnology\RolePricing\Storefront\BannerView;

/**
 * The banner's HTML: escaped, translatable, no inline styles.
 */
final class BannerRendererTest extends StorefrontTestCase
{
    private static function view( ?string $message = 'Buy 3+ and save 10%', ?float $price = 75.0, int $product_id = 1 ): BannerView
    {
        return new BannerView( $product_id, $message, 3, $price );
    }

    public function testBadge(): void
    {
        $html = $this->renderer->badge( self::view() );

        $this->assertStringContainsString( 'class="crbp-badge crbp-badge--short"', $html );
        $this->assertSame( 'Volume discount!', self::text( $html ) );
    }

    public function testMessage(): void
    {
        $html = $this->renderer->message( self::view() );

        $this->assertStringStartsWith( '<p class="crbp-badge crbp-badge--full">', $html );
        $this->assertSame( 'Buy 3+ and save 10%', self::text( $html ) );
    }

    public function testMessageMarkupIsSanitized(): void
    {
        $html = $this->renderer->message( self::view( '<script>alert(1)</script>Buy <img src=x onerror=alert(1)>3+' ) );

        $this->assertStringNotContainsString( '<script', $html );
        $this->assertStringNotContainsString( 'onerror', $html );
    }

    public function testExtraClassesAndTagAreEscaped(): void
    {
        $html = $this->renderer->message( self::view(), 'span"><script>', 'onsale" onclick="x' );

        $this->assertStringNotContainsString( '<script', $html );
        $this->assertStringNotContainsString( '" onclick', $html );
    }

    public function testQuantityPriceBox(): void
    {
        $html = $this->renderer->quantity_price_box( self::view() );

        $this->assertStringContainsString( 'class="crbp-qty-price"', $html );
        $this->assertStringContainsString( 'Unit price when buying 3 or more:', self::text( $html ) );
        $this->assertStringContainsString( html_entity_decode( wp_strip_all_tags( wc_price( 75 ) ) ), self::text( $html ) );
    }

    public function testCompactQuantityPriceBoxForLists(): void
    {
        $html = $this->renderer->quantity_price_box( self::view(), 'crbp-qty-price--loop' );

        $this->assertStringStartsWith( '<div class="crbp-qty-price crbp-qty-price--loop">', $html );
    }

    public function testNothingWithoutAMessageOrPrice(): void
    {
        $this->assertSame( '', $this->renderer->badge( self::view( message: null ) ) );
        $this->assertSame( '', $this->renderer->message( self::view( message: null ) ) );
        $this->assertSame( '', $this->renderer->quantity_price_box( self::view( price: null ) ) );
    }

    public function testNoInlineStyles(): void
    {
        $html = $this->renderer->badge( self::view() ) . $this->renderer->message( self::view() ) . $this->renderer->quantity_price_box( self::view() );

        $this->assertStringNotContainsString( 'style=', $html );
    }
}
