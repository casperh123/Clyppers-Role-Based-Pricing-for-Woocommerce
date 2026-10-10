<?php

namespace Tests\Integration;

use WC_Product_Simple;

/**
 * Malformed rules, whether sent to the API or already stored, must never cause an error or a price change.
 */
final class RuleInputHardeningTest extends IntegrationTestCase
{
    private function product( string $price = '100' ): int
    {
        $product = new WC_Product_Simple();
        $product->set_name( 'Hoodie' );
        $product->set_regular_price( $price );

        return $product->save();
    }

    /**
     * Store a rule post exactly as given, bypassing the plugin's own serialisation.
     */
    private function storeRawRule( string $role_slug, string $content ): int
    {
        return wp_insert_post( [
            'post_type'    => 'clypper_rbp',
            'post_status'  => 'publish',
            'post_title'   => $role_slug,
            'post_content' => wp_slash( $content ),
        ] );
    }

    private function priceAsCustomer( int $product_id ): string
    {
        $this->loggedInAs( 'customer' );

        return wc_get_product( $product_id )->get_price();
    }

    public static function malformedStoredRules(): array
    {
        return [
            'not JSON'             => [ 'this is not json' ],
            'JSON scalar'          => [ '"on"' ],
            'empty rule type'      => [ '{"rule_active":"on","global_rule":{"type":"","value":50}}' ],
            'unknown rule type'    => [ '{"rule_active":"on","global_rule":{"type":"free","value":50}}' ],
            'rule not an object'   => [ '{"rule_active":"on","global_rule":"percent"}' ],
            'products not a list'  => [ '{"rule_active":"on","products":"all"}' ],
            'product without rule' => [ '{"rule_active":"on","products":[{"id":%d}]}' ],
            'categories garbage'   => [ '{"rule_active":"on","categories":"1,2","category_rule":{"type":"percent","value":50}}' ],
        ];
    }

    /**
     * @dataProvider malformedStoredRules
     */
    public function testMalformedStoredRuleLeavesStorefrontPricesAlone( string $content ): void
    {
        $product = $this->product();
        $this->storeRawRule( 'customer', sprintf( $content, $product ) );

        $this->assertEquals( 100, $this->priceAsCustomer( $product ) );
    }

    public function testValidStoredRuleStillApplies(): void
    {
        $product = $this->product();
        $this->storeRawRule( 'customer', '{"rule_active":"on","global_rule":{"type":"percent","value":20}}' );

        $this->assertEquals( 80, $this->priceAsCustomer( $product ) );
    }

    public static function malformedRequestBodies(): array
    {
        return [
            'empty body'           => [ [] ],
            'wrong types'          => [ [ 'rule_active' => [ 'on' ], 'global_rule' => 'x', 'categories' => 'x', 'products' => 'x', 'single_categories' => 5 ] ],
            'unknown rule type'    => [ [ 'rule_active' => 'on', 'global_rule' => [ 'type' => 'free', 'value' => 100 ] ] ],
            'item without rules'   => [ [ 'rule_active' => 'on', 'products' => [ [ 'id' => 5 ] ] ] ],
            'item without id'      => [ [ 'rule_active' => 'on', 'products' => [ [ 'name' => 'x', 'rule' => [ 'type' => 'percent', 'value' => 10 ] ] ] ] ],
            'negative values'      => [ [ 'rule_active' => 'on', 'global_rule' => [ 'type' => 'percent', 'value' => -50 ] ] ],
        ];
    }

    /**
     * @dataProvider malformedRequestBodies
     */
    public function testMalformedUpdateIsSavedAsAHarmlessRule( array $body ): void
    {
        $this->loggedInAs( 'shop_manager' );
        $rule    = $this->ruleFor( 'customer' );
        $product = $this->product();

        $response = $this->request( 'PUT', "/crbp/v1/rules/{$rule->id}", $body );

        $this->assertSame( 204, $response->get_status() );
        $this->assertSame( 'customer', $this->storedRule( $rule->id )->role_slug );
        $this->assertEquals( 100, $this->priceAsCustomer( $product ) );
    }

    public function testItemNamesAreStoredSanitized(): void
    {
        $this->loggedInAs( 'shop_manager' );
        $rule = $this->ruleFor( 'customer' );
        $body = [ 'products' => [ [ 'id' => 5, 'name' => '<img src=x onerror=alert(1)>Hoodie' ] ] ];

        $this->request( 'PUT', "/crbp/v1/rules/{$rule->id}", $body );

        $this->assertSame( 'Hoodie', $this->storedRule( $rule->id )->products[5]->name );
        $this->assertStringNotContainsString( 'onerror', get_post( $rule->id )->post_content );
    }
}
