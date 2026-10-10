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

        return ob_get_clean();
    }

    public function testFixedQuantityDiscountShowsThePrice(): void
    {
        $product = $this->productWithQuantityRule( RuleType::TYPE_FIXED_SET, 75, 3 );

        $banner = $this->bannerOnProductPage( $product );

        $this->assertStringContainsString( '<span', $banner );
        $this->assertStringContainsString( '3', $banner );
        $this->assertStringContainsString( '75.00', $banner );
    }

    public function testPercentQuantityDiscountShowsThePercentage(): void
    {
        $product = $this->productWithQuantityRule( RuleType::TYPE_PERCENT, 10, 5 );

        $banner = $this->bannerOnProductPage( $product );

        $this->assertStringContainsString( '5+', $banner );
        $this->assertStringContainsString( '10%', $banner );
    }

    public function testNoBannerWithoutAQuantityRule(): void
    {
        $product = $this->productWithQuantityRule( RuleType::TYPE_PERCENT, 0, 5 );

        $this->assertSame( '', trim( $this->bannerOnProductPage( $product ) ) );
    }
}
