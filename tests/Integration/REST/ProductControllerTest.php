<?php

namespace Tests\Integration\REST;

use Tests\Integration\IntegrationTestCase;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * GET /crbp/v1/products?search= finds products by name or SKU for the rule editor.
 */
final class ProductControllerTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();
        $this->loggedInAs( 'shop_manager' );
    }

    private function simpleProduct( string $name, string $sku = '' ): int
    {
        $product = new WC_Product_Simple();
        $product->set_name( $name );
        $product->set_sku( $sku );
        $product->set_regular_price( '100' );

        return $product->save();
    }

    /**
     * @return int[] variation ids
     */
    private function variableProduct( string $name, int $variations ): array
    {
        $parent = new WC_Product_Variable();
        $parent->set_name( $name );
        $parent_id = $parent->save();

        $ids = [];
        for ( $i = 0; $i < $variations; $i++ ) {
            $variation = new WC_Product_Variation();
            $variation->set_parent_id( $parent_id );
            $variation->set_regular_price( '10' );
            $ids[] = $variation->save();
        }

        WC_Product_Variable::sync( $parent_id );

        return $ids;
    }

    private function search( string $term ): \WP_REST_Response
    {
        $request = new \WP_REST_Request( 'GET', '/crbp/v1/products' );
        $request->set_param( 'search', $term );

        return rest_do_request( $request );
    }

    private static function ids( \WP_REST_Response $response ): array
    {
        $ids = array_map( fn( $product ) => $product->id, $response->get_data() );
        sort( $ids );

        return $ids;
    }

    public function testFindsSimpleProductsByName(): void
    {
        $hoodie = $this->simpleProduct( 'Blue Hoodie' );
        $this->simpleProduct( 'Red Cap' );

        $response = $this->search( 'Hoodie' );

        $this->assertSame( 200, $response->get_status() );
        $this->assertSame( [ $hoodie ], self::ids( $response ) );
    }

    public function testFindsProductsBySku(): void
    {
        $product = $this->simpleProduct( 'Blue Hoodie', 'HOOD-001' );

        $this->assertSame( [ $product ], self::ids( $this->search( 'HOOD-001' ) ) );
    }

    public function testVariableProductsAreReturnedAsTheirVariations(): void
    {
        $variations = $this->variableProduct( 'Logo Tee', 2 );

        $this->assertSame( $variations, self::ids( $this->search( 'Logo Tee' ) ) );
    }

    public function testChildThatCannotBeLoadedIsSkipped(): void
    {
        $variations = $this->variableProduct( 'Logo Tee', 1 );
        $add_missing_child = fn( $children ) => array_merge( $children, [ PHP_INT_MAX ] );
        add_filter( 'woocommerce_get_children', $add_missing_child );

        $response = $this->search( 'Logo Tee' );

        remove_filter( 'woocommerce_get_children', $add_missing_child );
        $this->assertSame( 200, $response->get_status() );
        $this->assertSame( $variations, self::ids( $response ) );
    }

    public function testNoMatchesReturnsAnEmptyList(): void
    {
        $response = $this->search( 'nothing matches this' );

        $this->assertSame( 200, $response->get_status() );
        $this->assertSame( [], $response->get_data() );
    }

    public static function tooShortSearches(): array
    {
        return [
            'empty'         => [ '' ],
            'one character' => [ 'a' ],
        ];
    }

    /**
     * @dataProvider tooShortSearches
     */
    public function testTooShortSearchIsRejected( string $term ): void
    {
        $this->assertSame( 400, $this->search( $term )->get_status() );
    }

    public function testMissingSearchIsRejected(): void
    {
        $this->assertSame( 400, rest_do_request( new \WP_REST_Request( 'GET', '/crbp/v1/products' ) )->get_status() );
    }

    public function testCustomersCannotSearch(): void
    {
        $this->loggedInAs( 'customer' );

        $this->assertSame( 403, $this->search( 'Hoodie' )->get_status() );
    }
}
