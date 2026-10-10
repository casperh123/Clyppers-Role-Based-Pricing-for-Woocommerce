<?php

namespace Tests\Integration\REST;

use Tests\Integration\IntegrationTestCase;

/**
 * GET /crbp/v1/roles lists every role, plus guests, for the admin app.
 */
final class RoleControllerListTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();
        $this->loggedInAs( 'shop_manager' );
    }

    private function rolesBySlug(): array
    {
        $roles = $this->request( 'GET', '/crbp/v1/roles' )->get_data();

        return array_column( array_map( fn( $role ) => (array) $role, $roles ), null, 'slug' );
    }

    public function testListsEveryRolePlusGuest(): void
    {
        $roles = $this->rolesBySlug();

        $this->assertEqualsCanonicalizing( [ ...array_keys( wp_roles()->get_names() ), 'guest' ], array_keys( $roles ) );
    }

    public function testGuestAndRoleNamesAreDisplayNames(): void
    {
        $roles = $this->rolesBySlug();

        $this->assertSame( 'Guest', $roles['guest']['name'] );
        $this->assertSame( 'Shop manager', $roles['shop_manager']['name'] );
    }

    public function testRoleNamesAreTranslated(): void
    {
        $translate = fn( $translation, $text ) => 'Guest' === $text || 'Customer' === $text ? "[{$text}]" : $translation;
        add_filter( 'gettext_with_context', $translate, 10, 2 );
        add_filter( 'gettext', $translate, 10, 2 );

        $roles = $this->rolesBySlug();

        remove_filter( 'gettext_with_context', $translate );
        remove_filter( 'gettext', $translate );
        $this->assertSame( '[Guest]', $roles['guest']['name'] );
        $this->assertSame( '[Customer]', $roles['customer']['name'] );
    }

    public function testRoleWithARuleShowsItsState(): void
    {
        $rule = $this->ruleFor( 'customer' );
        $this->request( 'PATCH', '/crbp/v1/roles', [ 'slug' => 'customer', 'active' => true ] );

        $customer = $this->rolesBySlug()['customer'];

        $this->assertSame( $rule->id, $customer['id'] );
        $this->assertTrue( $customer['active'] );
    }
}
