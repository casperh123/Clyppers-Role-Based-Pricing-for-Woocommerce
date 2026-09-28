<?php

namespace Tests\Integration\REST;

use ClypperTechnology\RolePricing\Repositories\RuleRepository;
use ClypperTechnology\RolePricing\Rules\RuleType;
use Generators\Rules\RoleRuleGenerator;
use Generators\Rules\RuleGenerator;
use Tests\Integration\IntegrationTestCase;

/**
 * PUT /crbp/v1/rules/{id} may only ever change the pricing rule named in the URL.
 */
final class RuleControllerUpdateTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();
        $this->loggedInAs( 'shop_manager' );
    }

    public static function nonRulePostTypes(): array
    {
        return [
            'post'    => [ 'post' ],
            'page'    => [ 'page' ],
            'product' => [ 'product' ],
        ];
    }

    /**
     * @dataProvider nonRulePostTypes
     */
    public function testUpdatingANonRulePostIsRejectedAndThePostIsUntouched( string $post_type ): void
    {
        $victim = self::factory()->post->create_and_get( [ 'post_type' => $post_type, 'post_content' => 'original' ] );
        $body   = self::asRequestBody( RoleRuleGenerator::empty() );
        $body['id'] = $victim->ID;

        $response = $this->request( 'PUT', "/crbp/v1/rules/{$victim->ID}", $body );

        $this->assertSame( 404, $response->get_status() );
        $this->assertPostUnchanged( $victim );
    }

    /**
     * @dataProvider nonRulePostTypes
     */
    public function testBodyIdCannotRedirectTheUpdateToAnotherPost( string $post_type ): void
    {
        $rule   = $this->ruleFor( 'customer' );
        $victim = self::factory()->post->create_and_get( [ 'post_type' => $post_type, 'post_content' => 'original' ] );
        $body   = self::asRequestBody( RoleRuleGenerator::empty() );
        $body['id'] = $victim->ID;

        $response = $this->request( 'PUT', "/crbp/v1/rules/{$rule->id}", $body );

        $this->assertSame( 204, $response->get_status() );
        $this->assertPostUnchanged( $victim );
    }

    public function testBodyIdCannotOverwriteAnotherRolesRule(): void
    {
        $target = $this->ruleFor( 'customer' );
        $other  = $this->ruleFor( 'subscriber' );
        $body   = self::asRequestBody( RoleRuleGenerator::withItemRules( [], [], RuleGenerator::with( RuleType::TYPE_PERCENT, 50 ) ) );
        $body['id'] = $other->id;

        $this->request( 'PUT', "/crbp/v1/rules/{$target->id}", $body );

        $this->assertEquals( 50, $this->storedRule( $target->id )->global_rule->value );
        $this->assertNull( $this->storedRule( $other->id )->global_rule );
    }

    public function testRoleOfARuleCannotBeChangedThroughTheBody(): void
    {
        $rule = $this->ruleFor( 'customer' );
        $body = self::asRequestBody( RoleRuleGenerator::empty() );
        $body['id'] = $rule->id;
        $body['role_slug'] = 'administrator';

        $this->request( 'PUT', "/crbp/v1/rules/{$rule->id}", $body );

        $this->assertSame( 'customer', $this->storedRule( $rule->id )->role_slug );
    }

    public function testUnknownIdReturns404(): void
    {
        $response = $this->request( 'PUT', '/crbp/v1/rules/999999', self::asRequestBody( RoleRuleGenerator::empty() ) );

        $this->assertSame( 404, $response->get_status() );
    }

    public function testValidUpdateIsSaved(): void
    {
        $rule = $this->ruleFor( 'customer' );
        $body = self::asRequestBody( RoleRuleGenerator::withItemRules( [], [], RuleGenerator::with( RuleType::TYPE_FIXED_SET, 42 ) ) );
        $body['id'] = $rule->id;

        $response = $this->request( 'PUT', "/crbp/v1/rules/{$rule->id}", $body );

        $stored = $this->storedRule( $rule->id );
        $this->assertSame( 204, $response->get_status() );
        $this->assertTrue( $stored->rule_active );
        $this->assertEquals( RuleType::TYPE_FIXED_SET, $stored->global_rule->type );
        $this->assertEquals( 42, $stored->global_rule->value );
    }

    public function testRepositoryRefusesToSaveOverANonRulePost(): void
    {
        $victim = self::factory()->post->create_and_get( [ 'post_type' => 'page', 'post_content' => 'original' ] );
        $rule   = RoleRuleGenerator::empty();
        $rule->id = $victim->ID;

        $saved = ( new RuleRepository() )->update_rule( $rule );

        $this->assertFalse( $saved );
        $this->assertPostUnchanged( $victim );
    }

    private function assertPostUnchanged( \WP_Post $before ): void
    {
        clean_post_cache( $before->ID );
        $after = get_post( $before->ID );

        $this->assertSame( $before->post_type, $after->post_type );
        $this->assertSame( $before->post_title, $after->post_title );
        $this->assertSame( $before->post_content, $after->post_content );
    }
}
