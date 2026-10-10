<?php

namespace Tests\Integration\Storefront;

use ClypperTechnology\RolePricing\Repositories\RuleRepository;
use ClypperTechnology\RolePricing\Rules\RuleType;
use Generators\Rules\ItemRuleGenerator;
use Generators\Rules\RuleGenerator;

/**
 * What the banner says for the current visitor and product.
 */
final class BannerDataTest extends StorefrontTestCase
{
    public function testQuantityRuleGivesMessageAndPrice(): void
    {
        $product = $this->discountedProduct();
        $this->asCustomer();

        $view = $this->data->for_product( wc_get_product( $product ) );

        $this->assertSame( $product, $view->product_id );
        $this->assertSame( 3, $view->min_quantity );
        $this->assertStringStartsWith( 'Buy 3+ for', $view->message );
        $this->assertSame( 75.0, $view->quantity_price );
    }

    public function testPercentRuleGivesThePercentagePrice(): void
    {
        $product = $this->product( '80' );
        $this->quantityRule( $product, RuleType::TYPE_PERCENT, 25, 10 );
        $this->asCustomer();

        $view = $this->data->for_product( wc_get_product( $product ) );

        $this->assertSame( 'Buy 10+ and save 25%', $view->message );
        $this->assertSame( 60.0, $view->quantity_price );
    }

    public function testNothingForAVisitorWhoseRoleHasNoRule(): void
    {
        $product = $this->discountedProduct();
        $this->loggedInAs( 'subscriber' );

        $this->assertNull( $this->data->for_product( wc_get_product( $product ) ) );
    }

    public function testNothingForGuestsWithoutAGuestRule(): void
    {
        $product = $this->discountedProduct();
        wp_set_current_user( 0 );

        $this->assertNull( $this->data->for_product( wc_get_product( $product ) ) );
    }

    public function testNothingWhenTheRuleIsInactive(): void
    {
        $product = $this->product();
        $this->quantityRule( $product, RuleType::TYPE_FIXED_SET, 75, 3, active: false );
        $this->asCustomer();

        $this->assertNull( $this->data->for_product( wc_get_product( $product ) ) );
    }

    public function testNothingForAnotherProduct(): void
    {
        $this->discountedProduct();
        $other = $this->product();
        $this->asCustomer();

        $this->assertNull( $this->data->for_product( wc_get_product( $other ) ) );
    }

    public function testMarkupsAreNotAdvertised(): void
    {
        $product = $this->product();
        $this->quantityRule( $product, RuleType::TYPE_PERCENT_ADD, 10, 3 );
        $this->asCustomer();

        $this->assertNull( $this->data->for_product( wc_get_product( $product ) ) );
    }

    public function testNoPriceBoxWhenTheQuantityPriceIsNotCheaper(): void
    {
        $product = $this->product( '50' );
        $this->quantityRule( $product, RuleType::TYPE_FIXED_SET, 75, 3 );
        $this->asCustomer();

        $view = $this->data->for_product( wc_get_product( $product ) );

        $this->assertNotNull( $view->message );
        $this->assertNull( $view->quantity_price );
    }

    public function testNoPriceBoxOnceTheCartQualifies(): void
    {
        $product = $this->discountedProduct();
        $this->asCustomer();
        wc_load_cart();
        WC()->cart->add_to_cart( $product, 3 );

        $view = $this->data->for_product( wc_get_product( $product ) );

        WC()->cart->empty_cart();
        $this->assertNotNull( $view->message );
        $this->assertNull( $view->quantity_price );
    }

    public function testCategoryQuantityRuleAppliesToProductsInTheCategory(): void
    {
        $category = self::factory()->term->create( [ 'taxonomy' => 'product_cat' ] );
        $product  = $this->product();
        wp_set_object_terms( $product, [ $category ], 'product_cat' );
        $rule = $this->ruleFor( 'customer' );
        $rule->rule_active = true;
        $rule->single_categories = [
            $category => ItemRuleGenerator::with( $category, 'Clothing', null, RuleGenerator::with( RuleType::TYPE_PERCENT, 10 ), 5 ),
        ];
        ( new RuleRepository() )->update_rule( $rule );
        $this->asCustomer();

        $view = $this->data->for_product( wc_get_product( $product ) );

        $this->assertSame( 'Buy 5+ and save 10%', $view->message );
    }
}
