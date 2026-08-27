<?php
/**
 *
 * Plugin Name: Clypper's Role Based Pricing
 * Description: Enables role-based pricing, dynamic discounts, VAT exemptions and much more to create tailored B2B and B2C shopping experiences.
 * Version: 1.0.0
 * Author: Casper Holten
 * Text Domain: clypper-role-based-pricing
 * Author URI:        https://clyppertechnology.com
 * Domain Path: /languages
 *
 * Tested up to: 7.1
 * Requires at least: 6.8.0
 * Requires PHP: 8.2
 * WC requires at least: 9.6
 * WC tested up to: 11.0.1
 *
 * Copyright: © 2026 Casper Holten.
 * License: GNU General Public License v3.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use ClypperTechnology\RolePricing\Admin\Admin;
use ClypperTechnology\RolePricing\REST\ProductController;
use ClypperTechnology\RolePricing\REST\RoleController;
use ClypperTechnology\RolePricing\REST\RuleController;
use ClypperTechnology\RolePricing\PriceRules;
use ClypperTechnology\RolePricing\Services\RoleService;
use ClypperTechnology\RolePricing\Services\RuleService;

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

const CAS_ROLES_RULES_VS   = '1.0.0';

define( 'CRBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CRBP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, 'crbp_install');
register_deactivation_hook( __FILE__, 'crbp_deactivate');
register_uninstall_hook( __FILE__, 'crbp_uninstall');

function crbp_install(): void
{
}

function crbp_deactivate(): void
{
}

function crbp_uninstall(): void {
}

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
    }
});

$crbp_role_service = new RoleService();
$crbp_rule_service = new RuleService( $crbp_role_service );

add_action( 'rest_api_init', function() use ( $crbp_rule_service, $crbp_role_service ) {
    $namespace = 'crbp/v1';

    ( new ProductController( $namespace ) )->register_routes();
    ( new RuleController( $namespace, $crbp_rule_service ) )->register_routes();
    ( new RoleController( $namespace, $crbp_role_service, $crbp_rule_service ) )->register_routes();
});

add_action( 'woocommerce_loaded', function() use ( &$crbp_rule_service, &$crbp_role_service ) {
    new PriceRules( $crbp_rule_service );

    if ( is_admin() ) {
        new Admin();
    }
});

add_action( 'init', function() {
    if ( post_type_exists( 'clypper_rbp' ) ) {
        return;
    }

    register_post_type( 'clypper_rbp', [
        'labels' => [
            'name'          => _x( 'Rules', 'Post Type General Name', 'clypper-role-based-pricing' ),
            'singular_name' => _x( 'Rule', 'Post Type Singular Name', 'clypper-role-based-pricing' ),
        ],
        'public'              => false,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'supports'            => [ 'title', 'editor' ],
        'has_archive'         => false,
        'show_in_rest'        => false,
    ]);
});
