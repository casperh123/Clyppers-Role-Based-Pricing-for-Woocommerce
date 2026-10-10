<?php

namespace Tests\Integration;

use ClypperTechnology\RolePricing\Cache\RuleCache;
use ClypperTechnology\RolePricing\Repositories\RuleRepository;
use ClypperTechnology\RolePricing\Rules\RoleRules;
use ClypperTechnology\RolePricing\Services\RuleService;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_UnitTestCase;

/**
 * Base class for integration tests: real WordPress + WooCommerce + this plugin.
 *
 * Every test runs in a database transaction that WP_UnitTestCase rolls back afterwards.
 */
abstract class IntegrationTestCase extends WP_UnitTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        // Fresh REST server so the plugin's crbp/v1 routes are registered for this test.
        global $wp_rest_server;
        $wp_rest_server = new WP_REST_Server();
        do_action( 'rest_api_init', $wp_rest_server );

        $this->resetPluginRuleCache();
    }

    /**
     * The plugin's RuleService lives for the whole PHPUnit process, but its in-memory RuleCache is meant to
     * live for one request. Empty it so rules from earlier (rolled back) tests don't leak into this one.
     */
    private function resetPluginRuleCache(): void
    {
        global $wp_filter;

        foreach ( $wp_filter['rest_api_init']->callbacks as $callbacks ) {
            foreach ( $callbacks as $callback ) {
                if ( ! $callback['function'] instanceof \Closure ) {
                    continue;
                }

                $rule_service = ( new \ReflectionFunction( $callback['function'] ) )->getStaticVariables()['crbp_rule_service'] ?? null;

                if ( $rule_service instanceof RuleService ) {
                    $repository = ( new \ReflectionProperty( RuleService::class, 'rule_repository' ) )->getValue( $rule_service );
                    ( new \ReflectionProperty( RuleRepository::class, 'rule_cache' ) )->setValue( $repository, new RuleCache() );
                }
            }
        }
    }

    public function tear_down(): void
    {
        global $wp_rest_server;
        $wp_rest_server = null;

        parent::tear_down();
    }

    protected function loggedInAs( string $role ): int
    {
        $user_id = self::factory()->user->create( [ 'role' => $role ] );
        wp_set_current_user( $user_id );

        return $user_id;
    }

    /**
     * Create a stored pricing rule for a role, through the plugin's own storage.
     */
    protected function ruleFor( string $role_slug ): RoleRules
    {
        return ( new RuleRepository() )->add_rule( $role_slug );
    }

    /**
     * Read a rule straight from the database, bypassing any in-request cache.
     */
    protected function storedRule( int $id ): ?RoleRules
    {
        $post = get_post( $id );

        return $post && 'clypper_rbp' === $post->post_type ? RoleRules::from_post( $post ) : null;
    }

    /**
     * Send a JSON request to the REST API, the way the admin app (apiFetch) does.
     */
    protected function request( string $method, string $route, ?array $body = null ): WP_REST_Response
    {
        $request = new WP_REST_Request( $method, $route );

        if ( null !== $body ) {
            $request->set_header( 'Content-Type', 'application/json' );
            $request->set_body( wp_json_encode( $body ) );
        }

        return rest_do_request( $request );
    }

    /**
     * A rule as the admin app sends it: JSON-encoded, then decoded by the REST API.
     */
    protected static function asRequestBody( RoleRules $rule ): array
    {
        return json_decode( wp_json_encode( $rule->to_array() ), true );
    }
}
