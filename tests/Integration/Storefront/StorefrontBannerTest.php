<?php

namespace Tests\Integration\Storefront;

use ClypperTechnology\RolePricing\Storefront\BannerRenderer;
use ClypperTechnology\RolePricing\Storefront\StorefrontBanner;

/**
 * Where the banner goes. Print callbacks are called directly so the plugin's own booted instance doesn't add output.
 */
final class StorefrontBannerTest extends StorefrontTestCase
{
    private StorefrontBanner $banner;

    public function set_up(): void
    {
        parent::set_up();
        $this->banner = new StorefrontBanner( $this->data, $this->renderer );
    }

    private function printed( string $piece ): string
    {
        ob_start();
        $this->banner->{"print_{$piece}"}();

        return ob_get_clean();
    }

    private function inProductList( int $product_id ): void
    {
        $this->go_to( get_post_type_archive_link( 'product' ) );
        $GLOBALS['product'] = wc_get_product( $product_id );
    }

    private function asFlatsome(): void
    {
        add_filter( 'template', fn() => 'flatsome' );
    }

    // Product lists

    public function testEveryProductInAListGetsTheBadgeAndPriceBox(): void
    {
        $product = $this->discountedProduct();
        $this->asCustomer();
        $this->inProductList( $product );

        $badge = $this->printed( 'loop_badge' );
        $box   = $this->printed( 'loop_price_box' );

        $this->assertSame( 'Volume discount!', self::text( $badge ) );
        $this->assertStringContainsString( 'crbp-qty-price--loop', $box );
        $this->assertStringContainsString( 'Unit price when buying 3 or more:', self::text( $box ) );
    }

    public function testProductsWithoutADiscountGetNothing(): void
    {
        $this->discountedProduct();
        $plain = $this->product();
        $this->asCustomer();
        $this->inProductList( $plain );

        $this->assertSame( '', $this->printed( 'loop_badge' ) . $this->printed( 'loop_price_box' ) );
    }

    public function testNothingOutsideALoop(): void
    {
        $this->discountedProduct();
        $this->asCustomer();
        unset( $GLOBALS['product'] );

        $this->assertSame( '', $this->printed( 'loop_badge' ) . $this->printed( 'product_message' ) );
    }

    // Product page

    public function testProductPageShowsTheMessageAndPriceBox(): void
    {
        $product = $this->discountedProduct();
        $this->asCustomer();
        $this->onProductPage( $product );

        $message = $this->printed( 'product_message' );
        $box     = $this->printed( 'product_price_box' );

        $this->assertStringStartsWith( '<p class="crbp-badge crbp-badge--full">', $message );
        $this->assertStringStartsWith( 'Buy 3+ for', self::text( $message ) );
        $this->assertStringStartsWith( '<div class="crbp-qty-price">', $box );
    }

    // Hooks

    public function testBootHooksEveryPieceAndRegistersTheStylesheet(): void
    {
        // Earlier tests' go_to() already fired `wp`; the test case restores hooks after each test but not this flag.
        ( new \ReflectionProperty( StorefrontBanner::class, 'booted' ) )->setValue( null, false );

        do_action( 'wp', $GLOBALS['wp'] );

        $this->assertSame(
            [
                'woocommerce_before_shop_loop_item_title' => [ 11 => 'print_loop_badge' ],
                'woocommerce_after_shop_loop_item_title'  => [ 11 => 'print_loop_price_box' ],
                'woocommerce_single_product_summary'      => [ 11 => 'print_product_message', 12 => 'print_product_price_box' ],
            ],
            self::bannerCallbacks()
        );
        $this->assertTrue( wp_style_is( BannerRenderer::STYLE_HANDLE, 'registered' ) );
    }

    public function testHooksCanBeMovedAndTurnedOff(): void
    {
        $move = fn( $hooks ) => [ 'loop_badge' => [ 'my_theme_card_footer', 5 ], 'loop_price_box' => false ] + $hooks;
        add_filter( 'crbp_banner_hooks', $move );

        $this->banner->register();

        $this->assertSame( 5, has_action( 'my_theme_card_footer', [ $this->banner, 'print_loop_badge' ] ) );
        $this->assertFalse( has_action( 'woocommerce_after_shop_loop_item_title', [ $this->banner, 'print_loop_price_box' ] ) );
    }

    // Flatsome

    public function testFlatsomeUsesItsOwnHooksForTheBadgeAndMessage(): void
    {
        $this->asFlatsome();

        $hooks = StorefrontBanner::hooks();

        $this->assertSame( [ 'woocommerce_before_shop_loop_item', 999 ], $hooks['loop_badge'] );
        $this->assertSame( [ 'flatsome_custom_single_product_1', 999 ], $hooks['product_message'] );
        $this->assertSame( [ 'woocommerce_after_shop_loop_item_title', 11 ], $hooks['loop_price_box'] );
        $this->assertSame( [ 'woocommerce_single_product_summary', 12 ], $hooks['product_price_box'] );
    }

    public function testFlatsomeKeepsItsBadgeMarkup(): void
    {
        $product = $this->discountedProduct();
        $this->asCustomer();
        $this->asFlatsome();
        $this->onProductPage( $product );

        $badge   = $this->printed( 'loop_badge' );
        $message = $this->printed( 'product_message' );

        $this->assertStringContainsString( '<div class="badge-container absolute right top z-1 crbp-flatsome">', $badge );
        $this->assertStringContainsString( 'class="crbp-badge crbp-badge--short onsale"', $badge );
        $this->assertStringContainsString( '<span class="crbp-badge crbp-badge--full onsale">', $message );
        $this->assertStringNotContainsString( 'style=', $badge . $message );
    }

    // Separation

    public function testPricingClassNoLongerOutputsHtml(): void
    {
        $source = file_get_contents( dirname( __DIR__, 3 ) . '/includes/PriceRules.php' );

        $this->assertDoesNotMatchRegularExpression( '/<(div|span|p)\b|echo |flatsome/i', $source );
    }

    public function testFlatsomeIsOnlyHandledInStorefrontBanner(): void
    {
        $files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( dirname( __DIR__, 3 ) . '/includes', \FilesystemIterator::SKIP_DOTS ) );
        $mentions = [];

        foreach ( $files as $file ) {
            if ( stripos( file_get_contents( $file->getPathname() ), 'flatsome' ) !== false ) {
                $mentions[] = $file->getFilename();
            }
        }

        $this->assertSame( [ 'StorefrontBanner.php' ], $mentions );
    }

    /**
     * @return array<string, array<int, string>> hook => [priority => StorefrontBanner method]
     */
    private static function bannerCallbacks(): array
    {
        global $wp_filter;
        $found = [];

        foreach ( [ 'woocommerce_before_shop_loop_item_title', 'woocommerce_after_shop_loop_item_title', 'woocommerce_single_product_summary' ] as $hook ) {
            foreach ( $wp_filter[ $hook ]->callbacks ?? [] as $priority => $callbacks ) {
                foreach ( $callbacks as $callback ) {
                    if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof StorefrontBanner ) {
                        $found[ $hook ][ $priority ] = $callback['function'][1];
                    }
                }
            }
        }

        return $found;
    }
}
