<?php

namespace Tests\Integration\REST;

use ClypperTechnology\RolePricing\Repositories\RuleRepository;
use ClypperTechnology\RolePricing\Rules\RoleRules;
use ClypperTechnology\RolePricing\Rules\RuleType;
use Generators\Rules\RoleRuleGenerator;
use Generators\Rules\RuleGenerator;
use Tests\Integration\IntegrationTestCase;

/**
 * POST /crbp/v1/roles/{slug}/copyFrom copies the pricing rules of copy_rule_slug onto {slug}.
 */
final class RoleControllerCopyTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();
        $this->loggedInAs( 'shop_manager' );
    }

    private function ruleWithGlobalPercent( string $role_slug, float $percent ): int
    {
        $stored = $this->ruleFor( $role_slug );
        $rule   = RoleRuleGenerator::withItemRules( [], [], RuleGenerator::with( RuleType::TYPE_PERCENT, $percent ) );
        $rule->id        = $stored->id;
        $rule->role_slug = $role_slug;
        ( new RuleRepository() )->update_rule( $rule );

        return $stored->id;
    }

    private function storedRuleFor( string $role_slug ): ?RoleRules
    {
        $posts = get_posts( [ 'post_type' => 'clypper_rbp', 'post_status' => 'any', 'title' => $role_slug, 'numberposts' => -1 ] );

        return $posts ? $this->storedRule( $posts[0]->ID ) : null;
    }

    public function testCopiesOntoAnExistingRule(): void
    {
        $this->ruleWithGlobalPercent( 'subscriber', 15 );
        $target = $this->ruleFor( 'customer' );

        $response = $this->request( 'POST', '/crbp/v1/roles/customer/copyFrom', [ 'copy_rule_slug' => 'subscriber' ] );

        $this->assertSame( 204, $response->get_status() );
        $this->assertEquals( 15, $this->storedRule( $target->id )->global_rule->value );
        $this->assertSame( 'customer', $this->storedRule( $target->id )->role_slug );
    }

    public function testCreatesTheTargetRuleWhenMissing(): void
    {
        $this->ruleWithGlobalPercent( 'subscriber', 15 );

        $response = $this->request( 'POST', '/crbp/v1/roles/customer/copyFrom', [ 'copy_rule_slug' => 'subscriber' ] );

        $this->assertSame( 204, $response->get_status() );
        $this->assertEquals( 15, $this->storedRuleFor( 'customer' )->global_rule->value );
    }

    public function testMissingSourceRuleReturns404AndCreatesNothing(): void
    {
        $response = $this->request( 'POST', '/crbp/v1/roles/customer/copyFrom', [ 'copy_rule_slug' => 'subscriber' ] );

        $this->assertSame( 404, $response->get_status() );
        $this->assertNull( $this->storedRuleFor( 'customer' ) );
    }

    public function testCopyingARoleOntoItselfIsRejected(): void
    {
        $this->ruleFor( 'customer' );

        $response = $this->request( 'POST', '/crbp/v1/roles/customer/copyFrom', [ 'copy_rule_slug' => 'customer' ] );

        $this->assertSame( 400, $response->get_status() );
        $this->assertSame( 'crbp_copy_to_self', $response->get_data()['code'] );
    }

    public static function invalidRequests(): array
    {
        return [
            'unknown target role' => [ 'not_a_role', [ 'copy_rule_slug' => 'subscriber' ] ],
            'unknown source role' => [ 'customer', [ 'copy_rule_slug' => 'not_a_role' ] ],
            'missing source'      => [ 'customer', [] ],
            'source not a string' => [ 'customer', [ 'copy_rule_slug' => [ 'subscriber' ] ] ],
        ];
    }

    /**
     * @dataProvider invalidRequests
     */
    public function testInvalidSlugsAreRejected( string $slug, array $body ): void
    {
        $this->ruleWithGlobalPercent( 'subscriber', 15 );

        $response = $this->request( 'POST', "/crbp/v1/roles/{$slug}/copyFrom", $body );

        $this->assertSame( 400, $response->get_status() );
        $this->assertNull( $this->storedRuleFor( 'customer' ) );
    }

    public function testCustomersCannotCopyRules(): void
    {
        $this->loggedInAs( 'customer' );

        $response = $this->request( 'POST', '/crbp/v1/roles/customer/copyFrom', [ 'copy_rule_slug' => 'subscriber' ] );

        $this->assertSame( 403, $response->get_status() );
    }
}
