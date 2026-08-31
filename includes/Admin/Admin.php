<?php

namespace ClypperTechnology\RolePricing\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {
    public function __construct()
    {
        add_action( 'admin_menu',                   [ $this, 'create_admin_menu' ] );
        add_action( 'admin_enqueue_scripts',        [ $this, 'enqueue_scripts' ] );
    }

    public function enqueue_scripts( string $hook ): void
    {
        if ( 'woocommerce_page_crbp' !== $hook ) {
            return;
        }

        $asset = include CRBP_PLUGIN_PATH . 'build/index.asset.php';

        wp_enqueue_script(
            'clypper-rbp-admin',
            CRBP_PLUGIN_URL . 'build/index.js',
            $asset['dependencies'],
            $asset['version'],
            [ 'in_footer' => true ]
        );

        wp_enqueue_style(
                'clypper-rbp-admin',
                CRBP_PLUGIN_URL . 'build/index.css',
                [ 'wp-components' ],
                $asset['version']
            );

        wp_enqueue_style( 'wp-components' );
    }

  public function create_admin_menu(): void
  {
      add_submenu_page(
          'woocommerce',
          __( 'Role Rules', 'clypper-role-based-pricing' ),
          __( 'Role Rules', 'clypper-role-based-pricing' ),
          'manage_woocommerce',
          'crbp',
          function (): void {
              echo '<div id="clypper-rbp-app"></div>';
          }
      );
  }
}
