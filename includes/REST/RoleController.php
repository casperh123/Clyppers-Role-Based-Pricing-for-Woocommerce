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
                // The body also carries the rule "id"; it is ignored so a request can only toggle the rule of the role it names.
                'args'                => [
                    'slug'   => $this->role_slug_arg(),
                    'active' => [
                        'required' => true,
                        'type'     => 'boolean',
                    ],
                ],
            ],
        ]);

        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<slug>[\w-]+)' . '/copyFrom', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'copy_rule' ],
                'permission_callback' => [ $this, 'permissions_check' ],
                'args'                => [
                    'slug'           => $this->role_slug_arg(),
                    'copy_rule_slug' => $this->role_slug_arg(),
                ],
            ],
        ]);
    }

    private function role_slug_arg(): array
    {
        return [
            'required'          => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_key',
            'validate_callback' => fn( $value ) => $this->roleService->role_exists( $value ),
        ];
    }

    public function permissions_check( $request ): \WP_Error|bool
    {
        return current_user_can( 'manage_woocommerce' );
    }

    public function get_items( $request ): \WP_REST_Response {
        $rules = $this->ruleService->get_all_rules();
        $roles = $this->roleService->get_all_roles($rules);

        return new \WP_REST_Response($roles, 200);
    }

    public function update_items( WP_REST_Request $request ): \WP_REST_Response {
        $slug   = $request->get_param( 'slug' );
        $active = $request->get_param( 'active' );

        $rule = $this->ruleService->get_rule_by_role_slug( $slug );

        if($rule) {
            $rule->rule_active = $active;
            $this->ruleService->update_rule($rule);

            return new \WP_REST_Response($rule, 204);
        }

        $rule = $this->ruleService->add_rule($slug);
        $rule->rule_active = $active;
        $this->ruleService->update_rule($rule);

        return new \WP_REST_Response($rule, 200);
    }

    public function copy_rule( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error
    {
      $slug = $request->get_param("slug");
      $copy_from_slug = $request->get_param("copy_rule_slug");

      if ($slug === $copy_from_slug) {
          return new \WP_Error(
              'crbp_copy_to_self',
              __( 'A role cannot copy rules from itself.', 'clyppers-role-based-pricing-for-woocommerce' ),
              [ 'status' => 400 ]
          );
      }

      $rule = $this->ruleService->copy_roles_from_rule( $slug, $copy_from_slug );

      if(!$rule) {
          return new \WP_REST_Response(null, 404);
      }

      return new \WP_REST_Response(null, 204);
    }
}
