<?php

namespace Tests\Integration\REST;

use ClypperTechnology\RolePricing\Services\SettingsService;
use Tests\Integration\IntegrationTestCase;

/**
 * GET/PUT /crbp/v1/settings.
 */
final class SettingsControllerTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();
        $this->loggedInAs( 'shop_manager' );
    }

    public function testBadgeIsOnByDefault(): void
    {
        $response = $this->request( 'GET', '/crbp/v1/settings' );

        $this->assertSame( 200, $response->get_status() );
        $this->assertSame( [ 'show_volume_badge' => true ], $response->get_data() );
    }

    public function testBadgeCanBeSwitchedOffAndOnAgain(): void
    {
        $off = $this->request( 'PUT', '/crbp/v1/settings', [ 'show_volume_badge' => false ] );
        $stored_off = SettingsService::show_volume_badge();
        $on  = $this->request( 'PUT', '/crbp/v1/settings', [ 'show_volume_badge' => true ] );

        $this->assertSame( [ 'show_volume_badge' => false ], $off->get_data() );
        $this->assertFalse( $stored_off );
        $this->assertSame( [ 'show_volume_badge' => true ], $on->get_data() );
        $this->assertTrue( SettingsService::show_volume_badge() );
    }

    public static function invalidBodies(): array
    {
        return [
            'missing'     => [ [] ],
            'not boolean' => [ [ 'show_volume_badge' => 'maybe' ] ],
        ];
    }

    /**
     * @dataProvider invalidBodies
     */
    public function testInvalidValuesAreRejected( array $body ): void
    {
        $response = $this->request( 'PUT', '/crbp/v1/settings', $body );

        $this->assertSame( 400, $response->get_status() );
        $this->assertTrue( SettingsService::show_volume_badge() );
    }

    public function testCustomersCannotReadOrChangeSettings(): void
    {
        $this->loggedInAs( 'customer' );

        $this->assertSame( 403, $this->request( 'GET', '/crbp/v1/settings' )->get_status() );
        $this->assertSame( 403, $this->request( 'PUT', '/crbp/v1/settings', [ 'show_volume_badge' => false ] )->get_status() );
        $this->assertTrue( SettingsService::show_volume_badge() );
    }
}
