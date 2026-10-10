<?php

namespace Tests\Integration\Services;

use ClypperTechnology\RolePricing\Services\RoleService;
use Tests\Integration\IntegrationTestCase;

final class RoleServiceTest extends IntegrationTestCase
{
    public static function existingRoles(): array
    {
        return [
            'WordPress role'   => [ 'subscriber' ],
            'WooCommerce role' => [ 'customer' ],
            'guest'            => [ 'guest' ],
        ];
    }

    /**
     * @dataProvider existingRoles
     */
    public function testExistingRoleExists( string $slug ): void
    {
        $this->assertTrue( ( new RoleService() )->role_exists( $slug ) );
    }

    public static function nonRoles(): array
    {
        return [
            'unknown slug'  => [ 'not_a_role' ],
            'empty string'  => [ '' ],
            'null'          => [ null ],
            'integer'       => [ 1 ],
            'array'         => [ [ 'customer' ] ],
            'role with tag' => [ 'customer<script>' ],
        ];
    }

    /**
     * @dataProvider nonRoles
     */
    public function testAnythingElseIsNotARole( mixed $slug ): void
    {
        $this->assertFalse( ( new RoleService() )->role_exists( $slug ) );
    }

    public function testRoleAddedAtRuntimeExists(): void
    {
        add_role( 'wholesale', 'Wholesale' );

        $this->assertTrue( ( new RoleService() )->role_exists( 'wholesale' ) );

        remove_role( 'wholesale' );
    }
}
