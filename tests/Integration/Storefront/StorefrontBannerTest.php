<?php

namespace Tests\Integration\Storefront;

use ClypperTechnology\RolePricing\Rules\RuleType;
use ClypperTechnology\RolePricing\Services\SettingsService;
use ClypperTechnology\RolePricing\Storefront\BannerRenderer;
use ClypperTechnology\RolePricing\Storefront\StorefrontBanner;

/**
 * Where the badge and price box go. Callbacks are called directly so the plugin's own booted instance doesn't add
 * output to these tests.
 */
final class StorefrontBannerTest extends StorefrontTestCase
{
    private const SALE_FLASH = '<span class="onsale">Sale!</span>';

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

    private function saleFlash( int $product_id, string $html = self::SALE_FLASH ): string
    {
        return $this->banner->append_to_sale_flash( $html, get_post( $product_id ), wc_get_product( $product_id ) );
    }

    private function inProductList( int $product_id ): void
    {
        $this->go_to( get_post_type_archive_link( 'product' ) );
        $GLOBALS['product'] = wc_get_product( $product_id );
    }

    private static function badges( string $html ): int
    {
        return substr_count( $html, 'crbp-volume-badge"' ) + substr_count( $html, 'crbp-volume-badge-flatsome"' );
    }

    // Classic templates: next to the sale badge

    public function testBadgeIsAddedRightAfterTheSaleBadge(): void
    {
        $product = $this->discountedProduct();
        $this->putOnSale( $product );
        $this->asCustomer();

        $html = $this->saleFlash( $product );

        $this->assertStringStartsWith( self::SALE_FLASH . '<span class="onsale crbp-volume-badge"', $html );
    }

    public function testBadgeCopiesTheThemesSaleBadgeMarkup(): void
    {
        $product = $this->discountedProduct();
        $this->putOnSale( $product );
        $this->asCustomer();

        $html = $this->saleFlash( $product, '<div  class="my-theme-badge is-sale" data-x="1">Sale!</div>' );

        $this->assertStringContainsString( '<div class="my-theme-badge is-sale crbp-volume-badge"', $html );
    }

    public function testBadgeStillShowsWhenTheThemeBlanksTheSaleBadge(): void
    {
        $product = $this->discountedProduct();
        $this->putOnSale( $product );
        $this->asCustomer();

        $this->assertSame( 1, self::badges( $this->saleFlash( $product, '' ) ) );
    }

    public function testProductWithoutSaleGetsTheBadgeWhereTheSaleBadgeWouldBe(): void
    {
        $product = $this->discountedProduct();
        $this->asCustomer();
        $this->inProductList( $product );

        $html = $this->printed( 'loop_badge' );

        $this->assertStringStartsWith( '<span class="onsale crbp-volume-badge"', $html );
        $this->assertSame( 'Volume discount!', self::text( $html ) );
    }

    public function testProductOnSaleIsNotBadgedTwice(): void
    {
        $product = $this->discountedProduct();
        $this->putOnSale( $product );
        $this->asCustomer();
        $this->inProductList( $product );

        $this->assertSame( '', $this->printed( 'loop_badge' ) );
    }

    public function testProductPageBadge(): void
    {
        $product = $this->discountedProduct();
        $this->asCustomer();
        $this->onProductPage( $product );

        $this->assertSame( 'Volume discount!', self::text( $this->printed( 'product_badge' ) ) );
    }

    public function testNoBadgeWithoutADiscount(): void
    {
        $this->discountedProduct();
        $plain = $this->product();
        $this->putOnSale( $plain );
        $this->asCustomer();
        $this->inProductList( $plain );

        $this->assertSame( self::SALE_FLASH, $this->saleFlash( $plain ) );
        $this->assertSame( '', $this->printed( 'loop_badge' ) );
    }

    public function testMarkupsGetNoBadge(): void
    {
        $product = $this->product();
        $this->quantityRule( $product, RuleType::TYPE_PERCENT_ADD, 10, 3 );
        $this->asCustomer();
        $this->inProductList( $product );

        $this->assertSame( '', $this->printed( 'loop_badge' ) );
    }

    // Price box: product page only

    public function testProductPageShowsThePriceBox(): void
    {
        $product = $this->discountedProduct();
        $this->asCustomer();
        $this->onProductPage( $product );

        $this->assertStringStartsWith( '<div class="crbp-qty-price">', $this->printed( 'product_price_box' ) );
    }

    public function testProductListsHaveNoPriceBox(): void
    {
        $this->assertArrayNotHasKey( 'loop_price_box', StorefrontBanner::hooks() );
        $this->assertFalse( method_exists( $this->banner, 'print_loop_price_box' ) );
    }

    // Setting

    public function testSwitchingTheBadgeOffRemovesItEverywhereButKeepsThePriceBox(): void
    {
        $product = $this->discountedProduct();
        $this->asCustomer();
        $this->onProductPage( $product );
        SettingsService::set_show_volume_badge( false );

        $badges = $this->printed( 'product_badge' ) . $this->saleFlash( $product ) . $this->renderBlockList();
        $box    = $this->printed( 'product_price_box' );

        $this->assertSame( 0, self::badges( $badges ) );
        $this->assertNotSame( '', $box );
    }

    // Block product lists (Product Collection), on any page

    private const COLLECTION = '<!-- wp:woocommerce/product-collection {"queryId":1,"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]}} -->'
        . '<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->%s<!-- /wp:woocommerce/product-template --></div><!-- /wp:woocommerce/product-collection -->';

    private const CARD_WITH_SALE_BADGE = '<!-- wp:woocommerce/product-image {"showSaleBadge":false} /--><!-- wp:woocommerce/product-sale-badge {"align":"left"} /--><!-- wp:post-title /--><!-- wp:woocommerce/product-price /-->';

    private const CARD_WITHOUT_SALE_BADGE = '<!-- wp:woocommerce/product-image {"showSaleBadge":false} /--><!-- wp:post-title /--><!-- wp:woocommerce/product-price /-->';

    /**
     * The test's products, listed by a Product Collection block.
     */
    private function renderBlockList( string $card = self::CARD_WITH_SALE_BADGE ): string
    {
        $this->asCustomer();
        $this->banner->register();

        return do_blocks( sprintf( self::COLLECTION, $card ) );
    }

    public function testBlockCardsGetTheBadgeInTheSaleBadgeBlock(): void
    {
        $product = $this->discountedProduct();
        $this->putOnSale( $product );

        $html = $this->renderBlockList();

        $this->assertSame( 1, self::badges( $html ) );
        $this->assertStringContainsString( 'alignleft wc-block-components-product-sale-badge--align-left crbp-volume-badge"', $html );
    }

    public function testBlockCardsWithoutASaleBadgeBlockGetTheBadgeAfterTheImage(): void
    {
        $this->discountedProduct();

        $html = $this->renderBlockList( self::CARD_WITHOUT_SALE_BADGE );

        $this->assertSame( 1, self::badges( $html ) );
    }

    public function testLoopHooksFiredInsideABlockListDontAddASecondBadge(): void
    {
        $this->discountedProduct();
        // What WooCommerce's block template compatibility layer does on shop pages.
        $compat = function ( $html, $block, $instance ) {
            $GLOBALS['product'] = wc_get_product( $instance->context['postId'] );
            ob_start();
            do_action( 'woocommerce_before_shop_loop_item_title' );

            return ob_get_clean() . $html;
        };
        add_filter( 'render_block_core/post-title', $compat, 10, 3 );

        $html = $this->renderBlockList();

        $this->assertSame( 1, self::badges( $html ) );
    }

    public function testNoPriceBoxInBlockLists(): void
    {
        $this->discountedProduct();

        $this->assertStringNotContainsString( 'crbp-qty-price', $this->renderBlockList() );
    }

    // Flatsome

    public function testFlatsomeStacksTheBadgeInItsBadgeContainer(): void
    {
        $product = $this->discountedProduct();
        $this->asCustomer();
        add_filter( 'template', fn() => 'flatsome' );
        $this->inProductList( $product );

        $this->assertStringContainsString( 'crbp-volume-badge-flatsome', $this->saleFlash( $product ) );
        $this->assertStringStartsWith( '<div class="badge-container absolute left top z-1">', $this->printed( 'loop_badge' ) );
        $this->assertSame( [ 'woocommerce_before_shop_loop_item', 999 ], StorefrontBanner::hooks()['loop_badge'] );
        $this->assertSame( [ 'flatsome_custom_single_product_1', 999 ], StorefrontBanner::hooks()['product_badge'] );
    }

    // Hooks

    public function testBootHooksEveryPieceAndRegistersTheAssets(): void
    {
        // Earlier tests' go_to() already fired `wp`; the test case restores hooks after each test but not this flag.
        ( new \ReflectionProperty( StorefrontBanner::class, 'booted' ) )->setValue( null, false );

        do_action( 'wp', $GLOBALS['wp'] );

        $this->assertSame(
            [
                'woocommerce_before_shop_loop_item_title'   => [ 10 => 'print_loop_badge' ],
                'woocommerce_before_single_product_summary' => [ 10 => 'print_product_badge' ],
                'woocommerce_single_product_summary'        => [ 11 => 'print_product_price_box' ],
                'woocommerce_sale_flash'                    => [ 999 => 'append_to_sale_flash' ],
                'render_block_woocommerce/product-sale-badge' => [ 10 => 'append_to_block_sale_badge' ],
                'render_block_woocommerce/product-image'      => [ 10 => 'append_to_block_image' ],
            ],
            self::bannerCallbacks()
        );
        $this->assertTrue( wp_style_is( BannerRenderer::STYLE_HANDLE, 'registered' ) );
        $this->assertTrue( wp_script_is( BannerRenderer::SCRIPT_HANDLE, 'registered' ) );
    }

    public function testHooksCanBeMovedAndTurnedOff(): void
    {
        $move = fn( $hooks ) => [ 'loop_badge' => [ 'my_theme_card_footer', 5 ], 'product_price_box' => false ] + $hooks;
        add_filter( 'crbp_banner_hooks', $move );

        $this->banner->register();

        $this->assertSame( 5, has_action( 'my_theme_card_footer', [ $this->banner, 'print_loop_badge' ] ) );
        $this->assertFalse( has_action( 'woocommerce_single_product_summary', [ $this->banner, 'print_product_price_box' ] ) );
    }

    // Separation

    public function testPricingClassNoLongerOutputsHtml(): void
    {
        $source = file_get_contents( dirname( __DIR__, 3 ) . '/includes/PriceRules.php' );

        $this->assertDoesNotMatchRegularExpression( '/<(div|span|p)\b|echo |flatsome/i', $source );
    }

    public function testFlatsomeIsOnlyHandledInTheStorefrontClasses(): void
    {
        $files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( dirname( __DIR__, 3 ) . '/includes', \FilesystemIterator::SKIP_DOTS ) );
        $mentions = [];

        foreach ( $files as $file ) {
            if ( stripos( file_get_contents( $file->getPathname() ), 'flatsome' ) !== false ) {
                $mentions[] = $file->getFilename();
            }
        }

        sort( $mentions );
        $this->assertSame( [ 'BannerRenderer.php', 'StorefrontBanner.php' ], $mentions );
    }

    /**
     * @return array<string, array<int, string>> hook => [priority => StorefrontBanner method]
     */
    private static function bannerCallbacks(): array
    {
        global $wp_filter;
        $found = [];

        foreach ( $wp_filter as $hook => $filter ) {
            foreach ( $filter->callbacks as $priority => $callbacks ) {
                foreach ( $callbacks as $callback ) {
                    $function = $callback['function'];

                    if ( is_array( $function ) && $function[0] instanceof StorefrontBanner && ! in_array( $function[1], [ 'enter_block_list', 'leave_block_list' ], true ) ) {
                        $found[ $hook ][ $priority ] = $function[1];
                    }
                }
            }
        }

        return $found;
    }
}
