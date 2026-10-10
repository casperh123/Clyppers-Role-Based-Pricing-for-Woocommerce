<?php

namespace Tests\Integration\REST;

use Tests\Integration\IntegrationTestCase;

/**
 * PATCH /crbp/v1/roles toggles whether a role's pricing rule is active, creating the rule if needed.
 */
final class RoleControllerUpdateTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();
        $this->loggedInAs( 'shop_manager' );
    }

    /**
     * The body as the admin app sends it: a RoleDTO with id -1 when the role has no rule yet.
     */
    private static function role( string $slug, bool $active, int $id = -1 ): array
    {
        return [ 'id' => $id, 'name' => ucfirst( $slug ), 'slug' => $slug, 'active' => $active, 'rule_count' => 0 ];
    }

    public function testActivatingARoleWithoutARuleCreatesAnActiveRule(): void
    {
        $response = $this->request( 'PATCH', '/crbp/v1/roles', self::role( 'customer', true ) );

        $this->assertSame( 200, $response->get_status() );
        $this->assertTrue( $this->storedRule( $response->get_data()->id )->rule_active );
    }

    public function testCreatingARuleHonoursActiveFalse(): void
    {
        $response = $this->request( 'PATCH', '/crbp/v1/roles', self::role( 'customer', false ) );

        $this->assertSame( 200, $response->get_status() );
        $this->assertFalse( $this->storedRule( $response->get_data()->id )->rule_active );
    }

    public function testTogglingAnExistingRuleUpdatesIt(): void
    {
        $rule = $this->ruleFor( 'customer' );

        $activate   = $this->request( 'PATCH', '/crbp/v1/roles', self::role( 'customer', true, $rule->id ) );
        $active     = $this->storedRule( $rule->id )->rule_active;
        $deactivate = $this->request( 'PATCH', '/crbp/v1/roles', self::role( 'customer', false, $rule->id ) );

        $this->assertSame( 204, $activate->get_status() );
        $this->assertTrue( $active );
        $this->assertSame( 204, $deactivate->get_status() );
        $this->assertFalse( $this->storedRule( $rule->id )->rule_active );
    }

    public function testStaleIdMinusOneDoesNotCreateASecondRule(): void
    {
        $rule = $this->ruleFor( 'customer' );

        $this->request( 'PATCH', '/crbp/v1/roles', self::role( 'customer', true ) );

        $this->assertCount( 1, get_posts( [ 'post_type' => 'clypper_rbp', 'post_status' => 'any', 'numberposts' => -1 ] ) );
        $this->assertTrue( $this->storedRule( $rule->id )->rule_active );
    }

    public function testBodyIdCannotToggleAnotherRolesRule(): void
    {
        $customer   = $this->ruleFor( 'customer' );
        $subscriber = $this->ruleFor( 'subscriber' );

        $this->request( 'PATCH', '/crbp/v1/roles', self::role( 'customer', true, $subscriber->id ) );

        $this->assertTrue( $this->storedRule( $customer->id )->rule_active );
        $this->assertFalse( $this->storedRule( $subscriber->id )->rule_active );
    }

    public static function invalidBodies(): array
    {
        return [
            'unknown role'   => [ [ 'slug' => 'not_a_role', 'active' => true ] ],
            'missing slug'   => [ [ 'active' => true ] ],
            'missing active' => [ [ 'slug' => 'customer' ] ],
            'active not bool'=> [ [ 'slug' => 'customer', 'active' => 'yes please' ] ],
            'empty body'     => [ [] ],
        ];
    }

    /**
     * @dataProvider invalidBodies
     */
    public function testInvalidBodyIsRejectedAndNothingIsCreated( array $body ): void
    {
        $response = $this->request( 'PATCH', '/crbp/v1/roles', $body );

        $this->assertSame( 400, $response->get_status() );
        $this->assertSame( [], get_posts( [ 'post_type' => 'clypper_rbp', 'post_status' => 'any' ] ) );
    }

    public function testCustomersCannotToggleRoles(): void
    {
        $this->loggedInAs( 'customer' );

        $this->assertSame( 403, $this->request( 'PATCH', '/crbp/v1/roles', self::role( 'customer', true ) )->get_status() );
    }
}
