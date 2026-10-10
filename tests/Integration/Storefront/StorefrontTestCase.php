<?php

namespace Tests\Integration\Storefront;

use ClypperTechnology\RolePricing\Repositories\RuleRepository;
use ClypperTechnology\RolePricing\Rules\RuleType;
use ClypperTechnology\RolePricing\Services\RoleService;
use ClypperTechnology\RolePricing\Services\RuleService;
use ClypperTechnology\RolePricing\Storefront\BannerData;
use ClypperTechnology\RolePricing\Storefront\BannerRenderer;
use Generators\Rules\ItemRuleGenerator;
use Generators\Rules\RuleGenerator;
use Tests\Integration\IntegrationTestCase;
use WC_Product_Simple;

/**
 * Helpers for storefront banner tests: a customer whose role has a quantity rule on a product.
 */
abstract class StorefrontTestCase extends IntegrationTestCase
{
    protected BannerData $data;
    protected BannerRenderer $renderer;

    public function set_up(): void
    {
        parent::set_up();

        $this->data     = new BannerData( new RuleService( new RoleService() ) );
        $this->renderer = new BannerRenderer();
    }

    public function tear_down(): void
    {
        unset( $GLOBALS['product'] );

        parent::tear_down();
    }

    protected function product( string $price = '100' ): int
    {
        $product = new WC_Product_Simple();
        $product->set_name( 'Hoodie' );
        $product->set_regular_price( $price );

        return $product->save();
    }

    /**
     * Give customers a quantity rule on the product: from $min_qty items, apply $type/$value.
     */
    protected function quantityRule( int $product_id, RuleType $type, float $value, int $min_qty, bool $active = true ): void
    {
        $rule = $this->ruleFor( 'customer' );
        $rule->rule_active = $active;
        $rule->products = [
            $product_id => ItemRuleGenerator::with( $product_id, 'Hoodie', null, RuleGenerator::with( $type, $value ), $min_qty ),
        ];
        ( new RuleRepository() )->update_rule( $rule );
    }

    /**
     * A product with "buy 3+ for 75 each" for customers.
     */
    protected function discountedProduct(): int
    {
        $product = $this->product();
        $this->quantityRule( $product, RuleType::TYPE_FIXED_SET, 75, 3 );

        return $product;
    }

    protected function asCustomer(): void
    {
        $this->loggedInAs( 'customer' );
    }

    /**
     * Make the main query a single product page for $product_id, with the loop's global $product set.
     */
    protected function onProductPage( int $product_id ): \WC_Product
    {
        $this->go_to( get_permalink( $product_id ) );
        $GLOBALS['product'] = wc_get_product( $product_id );

        return $GLOBALS['product'];
    }

    protected static function text( string $html ): string
    {
        return trim( html_entity_decode( wp_strip_all_tags( $html ) ) );
    }
}
