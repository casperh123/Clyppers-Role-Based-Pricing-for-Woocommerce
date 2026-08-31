<?php

namespace ClypperTechnology\RolePricing\REST;

use ClypperTechnology\RolePricing\Factories\Factories\RoleRulesDTOFactory;
use ClypperTechnology\RolePricing\Rules\RoleRules;
use ClypperTechnology\RolePricing\Services\RuleService;

defined('ABSPATH') || exit;

class RuleController extends \WP_REST_Controller
{
    private RuleService $rule_service;

    public function __construct( string $namespace, RuleService $ruleService )
    {
        $this->namespace     = $namespace;
        $this->resource_name = 'rules';
        $this->rule_service  = $ruleService;
    }

    public function register_routes(): void
    {
        register_rest_route( $this->namespace, '/' . $this->resource_name, [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_item' ],
                'permission_callback' => [ $this, 'permissions_check' ],
            ],
        ]);

        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_item' ],
                'permission_callback' => [ $this, 'permissions_check' ],
            ],
        ]);

        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'delete_item' ],
                'permission_callback' => [ $this, 'permissions_check' ],
            ],
        ]);

        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_rule' ],
                'permission_callback' => [ $this, 'permissions_check' ],
            ],
        ]);
    }

    public function permissions_check( $request ): \WP_Error|bool
    {
        return current_user_can( 'manage_woocommerce' );
    }

    public function get_item($request): \WP_REST_Response {
        $id = $request->get_param("id");
        $rule = $this->rule_service->get_rules_by_id($id);

        if(!$rule) {
            return new \WP_REST_Response(null, 404);
        }

        $rule_DTO = RoleRulesDTOFactory::from_rules($rule);

        return new \WP_REST_Response($rule_DTO->to_array(), 200);
    }

    public function create_item( $request ): \WP_REST_Response {
        $slug = $request->get_param( "slug" );

        $id = $this->rule_service->add_rule( $slug );

        return new \WP_REST_Response($id, 200);
    }

    public function delete_item( $request ): \WP_REST_Response
    {
        $this->rule_service->delete_rule( $request->get_param( 'id' ) );

        return new \WP_REST_Response( null, 204 );
    }

    public function update_rule(\WP_REST_Request $request ): \WP_REST_Response {
        $request_json = $request->get_json_params();
        $rule = RoleRules::from_array($request_json);

        $this->rule_service->update_rule($rule);

        return new \WP_REST_Response(null, 204);
    }
}
