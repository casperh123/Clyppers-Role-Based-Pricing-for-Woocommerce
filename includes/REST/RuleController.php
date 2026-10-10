<?php

namespace ClypperTechnology\RolePricing\REST;

use ClypperTechnology\RolePricing\Factories\Factories\RoleRulesDTOFactory;
use ClypperTechnology\RolePricing\Rules\RoleRules;
use ClypperTechnology\RolePricing\Services\RoleService;
use ClypperTechnology\RolePricing\Services\RuleService;
use WP_REST_Response;

defined('ABSPATH') || exit;

class RuleController extends \WP_REST_Controller
{
    private RuleService $rule_service;
    private RoleService $role_service;

    public function __construct( string $namespace, RuleService $ruleService, RoleService $roleService )
    {
        $this->namespace     = $namespace;
        $this->resource_name = 'rules';
        $this->rule_service  = $ruleService;
        $this->role_service  = $roleService;
    }

    public function register_routes(): void
    {
        register_rest_route( $this->namespace, '/' . $this->resource_name, [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_item' ],
                'permission_callback' => [ $this, 'permissions_check' ],
                'args'                => [
                    'slug' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                        'validate_callback' => fn( $value ) => $this->role_service->role_exists( $value ),
                    ],
                ],
            ],
        ]);

        $id_args = [
            'id' => [
                'type'    => 'integer',
                'minimum' => 1,
            ],
        ];

        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_item' ],
                'permission_callback' => [ $this, 'permissions_check' ],
                'args'                => $id_args,
            ],
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_rule' ],
                'permission_callback' => [ $this, 'permissions_check' ],
                'args'                => $id_args,
            ],
        ]);
    }

    public function permissions_check( $request ): \WP_Error|bool
    {
        return current_user_can( 'manage_woocommerce' );
    }

    public function get_item($request): \WP_REST_Response {
        $id = $request->get_param("id");
        $rule = $this->rule_service->get_rule_by_id($id);

        if(!$rule) {
            return new \WP_REST_Response(null, 404);
        }

        $rule_DTO = RoleRulesDTOFactory::from_rules($rule);

        return new \WP_REST_Response($rule_DTO->to_array(), 200);
    }

    public function create_item( $request ): \WP_REST_Response {
        $slug = $request->get_param( "slug" );

        if ( $this->rule_service->get_rule_by_role_slug( $slug ) ) {
            return new \WP_REST_Response( "A rule already exists for this role", 409 );
        }

        $rule = $this->rule_service->add_rule( $slug );

        return new \WP_REST_Response($rule, 200);
    }

    public function update_rule(\WP_REST_Request $request ): \WP_REST_Response {
        // Read the id from the URL only: $request['id'] prefers a body "id" over the URL for PUT/POST.
        $existing = $this->rule_service->get_rule_by_id( (int) $request->get_url_params()['id'] );

        if ( ! $existing ) {
            return new \WP_REST_Response(null, 404);
        }

        $rule = RoleRules::from_update( $existing, $request->get_json_params() ?? [] );

        if ( ! $this->rule_service->update_rule( $rule ) ) {
            return new \WP_REST_Response(null, 500);
        }

        return new \WP_REST_Response(null, 204);
    }
}
