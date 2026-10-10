<?php
/**
 * Integration test bootstrap: WordPress test suite + WooCommerce + this plugin.
 *
 * Runs inside wp-env's tests-cli container, where WP_TESTS_DIR points at the WordPress test library.
 */

$crbp_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/wordpress-phpunit';

$crbp_test_loader = require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
require_once $crbp_tests_dir . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function () use ( $crbp_test_loader ) {
    require WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
    require dirname( __DIR__, 2 ) . '/clyppers-role-based-pricing-for-woocommerce.php';

    // The plugin loads the repo's dev autoloader, which contains the unit suite's PHPUnit.
    // Put this suite's autoloader back in front so PHPUnit resolves to tests/Integration/vendor.
    $crbp_test_loader->unregister();
    $crbp_test_loader->register( true );
} );

// Create WooCommerce's tables, options and roles (e.g. shop_manager) in the test database.
tests_add_filter( 'setup_theme', function () {
    WC_Install::install();

    $GLOBALS['wp_roles'] = null;
    wp_roles();
} );

require $crbp_tests_dir . '/includes/bootstrap.php';

require __DIR__ . '/IntegrationTestCase.php';
require __DIR__ . '/Storefront/StorefrontTestCase.php';
