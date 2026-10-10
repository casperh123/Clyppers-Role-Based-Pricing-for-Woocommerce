<?php

namespace ClypperTechnology\RolePricing\REST;

use ClypperTechnology\RolePricing\Services\SettingsService;

defined('ABSPATH') || exit;

class SettingsController extends \WP_REST_Controller
{
    public function __construct( string $namespace )
    {
        $this->namespace     = $namespace;
        $this->resource_name = 'settings';
    }

    public function register_routes(): void
    {
        register_rest_route( $this->namespace, '/' . $this->resource_name, [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'permissions_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_settings' ],
                'permission_callback' => [ $this, 'permissions_check' ],
                'args'                => [
                    'show_volume_badge' => [
                        'required' => true,
                        'type'     => 'boolean',
                    ],
                ],
            ],
        ]);
    }

    public function permissions_check( $request ): \WP_Error|bool
    {
        return current_user_can( 'manage_woocommerce' );
    }

    public function get_settings( \WP_REST_Request $request ): \WP_REST_Response
    {
        return new \WP_REST_Response( SettingsService::all(), 200 );
    }

    public function update_settings( \WP_REST_Request $request ): \WP_REST_Response
    {
        SettingsService::set_show_volume_badge( $request->get_param( 'show_volume_badge' ) );

        return new \WP_REST_Response( SettingsService::all(), 200 );
    }
}
