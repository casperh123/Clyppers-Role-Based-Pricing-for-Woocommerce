<?php

namespace Tests\Integration\REST;

use Tests\Integration\IntegrationTestCase;

/**
 * POST /crbp/v1/rules and GET /crbp/v1/rules/{id}.
 */
final class RuleControllerCreateTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();
        $this->loggedInAs( 'shop_manager' );
    }

    public function testCreatingARuleForARoleStoresIt(): void
    {
        $response = $this->request( 'POST', '/crbp/v1/rules', [ 'slug' => 'customer' ] );

        $this->assertSame( 200, $response->get_status() );
        $this->assertSame( 'customer', $this->storedRule( $response->get_data()->id )->role_slug );
    }

    public function testCreatingARuleForGuestsIsAllowed(): void
    {
        $response = $this->request( 'POST', '/crbp/v1/rules', [ 'slug' => 'guest' ] );

        $this->assertSame( 200, $response->get_status() );
    }

    public function testSecondRuleForTheSameRoleIsRefused(): void
    {
        $this->ruleFor( 'customer' );

        $response = $this->request( 'POST', '/crbp/v1/rules', [ 'slug' => 'customer' ] );

        $this->assertSame( 409, $response->get_status() );
        $this->assertSame( 'crbp_rule_exists', $response->get_data()['code'] );
        $this->assertCount( 1, $this->rulePostsFor( 'customer' ) );
    }

    public static function invalidSlugs(): array
    {
        return [
            'unknown role' => [ [ 'slug' => 'not_a_role' ] ],
            'empty'        => [ [ 'slug' => '' ] ],
            'not a string' => [ [ 'slug' => [ 'customer' ] ] ],
            'wrong case'   => [ [ 'slug' => 'Customer' ] ],
            'missing'      => [ [] ],
        ];
    }

    /**
     * @dataProvider invalidSlugs
     */
    public function testInvalidSlugIsRejectedAndNothingIsCreated( array $body ): void
    {
        $response = $this->request( 'POST', '/crbp/v1/rules', $body );

        $this->assertSame( 400, $response->get_status() );
        $this->assertSame( [], get_posts( [ 'post_type' => 'clypper_rbp', 'post_status' => 'any' ] ) );
    }

    public function testGettingARuleReturnsIt(): void
    {
        $rule = $this->ruleFor( 'customer' );

        $response = $this->request( 'GET', "/crbp/v1/rules/{$rule->id}" );

        $this->assertSame( 200, $response->get_status() );
        $this->assertSame( 'customer', $response->get_data()['role_slug'] );
    }

    public function testRuleShowsTheRolesDisplayName(): void
    {
        $guest    = $this->ruleFor( 'guest' );
        $manager  = $this->ruleFor( 'shop_manager' );

        $this->assertSame( 'Guest', $this->request( 'GET', "/crbp/v1/rules/{$guest->id}" )->get_data()['role_name'] );
        $this->assertSame( 'Shop manager', $this->request( 'GET', "/crbp/v1/rules/{$manager->id}" )->get_data()['role_name'] );
    }

    public function testRuleShowsACustomRolesOwnName(): void
    {
        add_role( 'b2b_buyer', 'Wholesale Partner' );
        $rule = $this->ruleFor( 'b2b_buyer' );

        $name = $this->request( 'GET', "/crbp/v1/rules/{$rule->id}" )->get_data()['role_name'];

        remove_role( 'b2b_buyer' );
        $this->assertSame( 'Wholesale Partner', $name );
    }

    public function testRuleForARemovedRoleFallsBackToItsSlug(): void
    {
        $rule = $this->ruleFor( 'old_role' );

        $this->assertSame( 'Old role', $this->request( 'GET', "/crbp/v1/rules/{$rule->id}" )->get_data()['role_name'] );
    }

    public function testGettingANonRulePostReturns404(): void
    {
        $page = self::factory()->post->create( [ 'post_type' => 'page' ] );

        $this->assertSame( 404, $this->request( 'GET', "/crbp/v1/rules/{$page}" )->get_status() );
    }

    public function testIdZeroIsRejected(): void
    {
        $this->assertSame( 400, $this->request( 'GET', '/crbp/v1/rules/0' )->get_status() );
    }

    public function testCustomersCannotCreateRules(): void
    {
        $this->loggedInAs( 'customer' );

        $response = $this->request( 'POST', '/crbp/v1/rules', [ 'slug' => 'customer' ] );

        $this->assertSame( 403, $response->get_status() );
    }

    private function rulePostsFor( string $role_slug ): array
    {
        return get_posts( [ 'post_type' => 'clypper_rbp', 'post_status' => 'any', 'title' => $role_slug, 'numberposts' => -1 ] );
    }
}
