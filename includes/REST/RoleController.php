<?php

namespace ClypperTechnology\RolePricing\REST;

use ClypperTechnology\RolePricing\Services\RoleService;
use ClypperTechnology\RolePricing\Services\RuleService;
use WP_REST_Request;

defined('ABSPATH') || exit;

class RoleController extends  \WP_REST_Controller
{
    private RoleService $roleService;
    private RuleService $ruleService;

    public function __construct( string $namespace, RoleService $roleService, RuleService $ruleService )
    {
        $this->namespace     = $namespace;
        $this->resource_name = 'roles';
        $this->roleService  = $roleService;
        $this->ruleService = $ruleService;
    }

    public function register_routes(): void
    {
        register_rest_route( $this->namespace, '/' . $this->resource_name, [
            [
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_items'],
                'permission_callback' => [ $this, 'permissions_check' ],
            ],
        ]);

        register_rest_route( $this->namespace, '/' . $this->resource_name, [
            [
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => [ $this, 'update_items'],
                'permission_callback' => [ $this, 'permissions_check' ],
            ],
        ]);

        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<slug>[\w-]+)' . '/copyFrom', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'copy_rule' ],
                'permission_callback' => [ $this, 'permissions_check' ],
            ],
        ]);
    }

    public function permissions_check( $request ): \WP_Error|bool
    {
        return current_user_can( 'manage_woocommerce' );
    }

    public function get_items( $request ): \WP_REST_Response {
        $rules = $this->ruleService->get_all_role_rules();
        $roles = $this->roleService->get_all_roles($rules);

        return new \WP_REST_Response($roles, 200);
    }

    public function update_items( WP_REST_Request $request ): \WP_REST_Response {
        $request_rule = $request->get_json_params();
        $rule = $this->ruleService->get_rules_by_id($request_rule["id"]);

        if($rule) {
            $active = boolval($request["active"]);

            $rule->rule_active = $active;
            $this->ruleService->save_role_rules($rule);

            return new \WP_REST_Response($rule, 204);
        }

        $role_slug = $request_rule["slug"];

        $rule = $this->ruleService->add_rule($role_slug);
        $rule->rule_active = true;
        $this->ruleService->save_role_rules($rule);

        return new \WP_REST_Response($rule, 200);
    }

    public function copy_rule( \WP_REST_Request $request ) {
      $slug = $request->get_param("slug");
      $request_json = $request->get_json_params();
      $copy_from_slug = $request_json['copy_rule_slug'] ?? null;

      if(!$copy_from_slug || $slug == $copy_from_slug) {
        return new \WP_REST_Response("No rule_id found in request body", 400);
      }

      $rule = $this->ruleService->get_rule_by_user_role($slug);

      if(!$rule) {
        $rule = $this->ruleService->add_rule($slug);
      }

      $copy_from_rule = $this->ruleService->get_rule_by_user_role($copy_from_slug);

      if(!$copy_from_rule) {
        return new \WP_REST_Response(null, 404);
      }

      $rule->copy_from_rule($copy_from_rule);
      
      $this->ruleService->save_role_rules($rule);

      return new \WP_REST_Response(null, 204);
    }
}
