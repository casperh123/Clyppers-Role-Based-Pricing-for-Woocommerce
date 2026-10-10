<?php

namespace Tests\Integration;

use ClypperTechnology\RolePricing\Repositories\RuleRepository;
use ClypperTechnology\RolePricing\Rules\RuleType;
use Generators\Rules\ItemRuleGenerator;
use Generators\Rules\RuleGenerator;
use WC_Product_Simple;

/**
 * The quantity-discount banner on the single product page (Flatsome hook).
 */
final class DiscountBannerTest extends IntegrationTestCase
{
    private function productWithQuantityRule( RuleType $type, float $value, int $min_qty ): int
    {
        $product = new WC_Product_Simple();
        $product->set_name( 'Hoodie' );
        $product->set_regular_price( '100' );
        $product_id = $product->save();

        $rule = $this->ruleFor( 'customer' );
        $rule->rule_active = true;
        $rule->products = [
            $product_id => ItemRuleGenerator::with( $product_id, 'Hoodie', null, RuleGenerator::with( $type, $value ), $min_qty ),
        ];
        ( new RuleRepository() )->update_rule( $rule );

        return $product_id;
    }

    private function bannerOnProductPage( int $product_id ): string
    {
        $this->loggedInAs( 'customer' );
        $GLOBALS['post'] = get_post( $product_id );

        ob_start();
        apply_filters( 'flatsome_custom_single_product_1', '' );

        // Compare text, not entity spelling (wp_kses_post writes $ as &#036;, wc_price as &#36;).
        return html_entity_decode( ob_get_clean() );
    }

    public function testFixedPriceQuantityDiscountMessage(): void
    {
        $product = $this->productWithQuantityRule( RuleType::TYPE_FIXED_SET, 75, 3 );

        $banner = $this->bannerOnProductPage( $product );

        $this->assertStringContainsString( 'Buy 3+ for ' . html_entity_decode( wc_price( 75, [ 'in_span' => false ] ) ) . ' per item', $banner );
    }

    public function testFixedAmountQuantityDiscountMessage(): void
    {
        $product = $this->productWithQuantityRule( RuleType::TYPE_FIXED, 5, 4 );

        $banner = $this->bannerOnProductPage( $product );

        $this->assertStringContainsString( 'Buy 4+ and save ' . html_entity_decode( wc_price( 5, [ 'in_span' => false ] ) ) . ' per item', $banner );
    }

    public function testPercentQuantityDiscountMessage(): void
    {
        $product = $this->productWithQuantityRule( RuleType::TYPE_PERCENT, 12.5, 5 );

        $banner = $this->bannerOnProductPage( $product );

        $this->assertStringContainsString( 'Buy 5+ and save 12.5%', $banner );
    }

    public function testMarkupHasNoBannerMessage(): void
    {
        $product = $this->productWithQuantityRule( RuleType::TYPE_PERCENT_ADD, 10, 5 );

        $this->assertStringNotContainsString( 'Buy', $this->bannerOnProductPage( $product ) );
    }

    public function testShopArchiveShowsTheShortBadge(): void
    {
        $product = $this->productWithQuantityRule( RuleType::TYPE_PERCENT, 10, 5 );
        $this->loggedInAs( 'customer' );
        $GLOBALS['post'] = get_post( $product );

        ob_start();
        do_action( 'woocommerce_before_shop_loop_item' );
        $badge = ob_get_clean();

        $this->assertStringContainsString( 'Volume discount!', $badge );
    }

    public function testNoBannerWithoutAQuantityRule(): void
    {
        $product = $this->productWithQuantityRule( RuleType::TYPE_PERCENT, 0, 5 );

        $this->assertSame( '', trim( $this->bannerOnProductPage( $product ) ) );
    }
}
