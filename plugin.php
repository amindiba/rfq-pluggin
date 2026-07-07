<?php
/**
 * Plugin Name: B2B Procurement Operating System
 * Plugin URI: https://zoroo.ir/
 * Description: Commercial-grade B2B procurement and supply chain management platform for WordPress and WooCommerce.
 * Version: 1.4.0
 * Author: Zoroo Development Team
 * Author URI: https://zoroo.ir/
 * License: Proprietary
 * Text Domain: b2b-procurement
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', WP_CONTENT_DIR . '/debug.log');

/**
 * Plugin constants.
 */
define('B2B_PROCUREMENT_VERSION', '1.4.0');
define('B2B_PROCUREMENT_PLUGIN_FILE', __FILE__);
define('B2B_PROCUREMENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('B2B_PROCUREMENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('B2B_PROCUREMENT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('B2B_PROCUREMENT_MIN_WP_VERSION', '6.0');
define('B2B_PROCUREMENT_MIN_PHP_VERSION', '8.1');
define('B2B_PROCUREMENT_MIN_WC_VERSION', '8.0');

/**
 * Declare WooCommerce feature compatibility.
 */
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('product_block_editor', __FILE__, true);
    }
});

/**
 * Load the plugin bootstrap.
 */
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_STRICT))) {
        file_put_contents(WP_CONTENT_DIR . '/b2b-shutdown.log',
            date('Y-m-d H:i:s') . ' | ' . $error['type'] . ' | ' . $error['message'] . ' | ' . $error['file'] . ':' . $error['line'] . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
});

@mkdir(WP_CONTENT_DIR . '/b2b-procurement', 0755, true);
@mkdir(WP_CONTENT_DIR . '/b2b-procurement/logs', 0755, true);

try {
    require_once B2B_PROCUREMENT_PLUGIN_DIR . 'bootstrap.php';
} catch (Throwable $e) {
    file_put_contents(WP_CONTENT_DIR . '/b2b-procurement/logs/init-error.log',
        date('Y-m-d H:i:s') . ' | ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine() . "\n",
        FILE_APPEND | LOCK_EX
    );
    add_action('admin_head', function () use ($e) {
        echo '<style>.notice-error{display:block!important;}</style>';
    });
    add_action('admin_notices', function () use ($e) {
        echo '<div class="notice notice-error" style="display:block!important;"><p><strong>B2B Error:</strong> ' . esc_html($e->getMessage()) . ' (' . esc_html(basename($e->getFile())) . ':' . $e->getLine() . ')</p></div>';
    });
}
