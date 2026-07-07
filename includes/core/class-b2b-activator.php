<?php
/**
 * Activator - Handles plugin activation logic.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Activator
 *
 * Runs environment checks on activation and sets up initial plugin state.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Activator {

    /**
     * Plugin activation handler.
     *
     * Checks system requirements before completing activation.
     * If any requirement fails, the plugin is deactivated immediately.
     */
    public static function activate() {
        // Run environment check.
        $checker = new B2B_Procurement_Environment_Checker();
        $failures = $checker->check_all();

        if (!empty($failures)) {
            // Deactivate immediately.
            deactivate_plugins(B2B_PROCUREMENT_PLUGIN_BASENAME);

            // Show error and redirect.
            wp_die(
                self::render_error_page($failures),
                'خطای فعال‌سازی پلاگین',
                array('back_link' => true)
            );
        }

        // Run activation tasks.
        self::set_version();
        self::create_storage_directories();
        self::set_default_options();
        self::create_database_tables();
        self::flush_rewrite_rules();

        // Set welcome redirect flag.
        B2B_Procurement_Welcome::set_activation_flag();

        // Log activation.
        if (function_exists('error_log')) {
            error_log('[B2B Procurement] Plugin activated successfully. Version: ' . B2B_PROCUREMENT_VERSION);
        }
    }

    /**
     * Store the current plugin version.
     */
    private static function set_version() {
        update_option('b2b_procurement_version', B2B_PROCUREMENT_VERSION);
    }

    /**
     * Create plugin storage directories.
     */
    private static function create_storage_directories() {
        $base = WP_CONTENT_DIR . '/b2b-procurement/';
        $dirs = array(
            'documents',
            'temp',
            'logs',
            'exports',
        );

        foreach ($dirs as $dir) {
            $path = $base . $dir;
            if (!file_exists($path)) {
                wp_mkdir_p($path);
            }
        }

        // Create .htaccess for security.
        $htaccess_content = "Order deny,allow\nDeny from all\n";
        file_put_contents($base . '.htaccess', $htaccess_content);
        file_put_contents($base . 'documents/.htaccess', $htaccess_content);
        file_put_contents($base . 'temp/.htaccess', $htaccess_content);
        file_put_contents($base . 'logs/.htaccess', $htaccess_content);
        file_put_contents($base . 'exports/.htaccess', $htaccess_content);
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        $defaults = array(
            'b2b_procurement_version'       => B2B_PROCUREMENT_VERSION,
            'b2b_procurement_installed_at'   => current_time('mysql'),
            'b2b_procurement_settings'       => array(),
        );

        foreach ($defaults as $key => $value) {
            if (false === get_option($key)) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Create database tables.
     */
    private static function create_database_tables() {
        B2B_Procurement_Master_Data_DB::create_tables();
        B2B_Procurement_Master_Data_DB::seed_defaults();
        B2B_Procurement_Geography_DB::create_tables();
        B2B_Procurement_Geography_DB::seed_iran_data();
        B2B_Procurement_Product_DB::create_tables();
        B2B_Supplier_DB::create_tables();
        B2B_Rfq_DB::create_tables();
        B2B_Quotation_DB::create_tables();
        B2B_PO_DB::create_tables();
        B2B_Contract_DB::create_tables();
        B2B_Notification_DB::create_tables();
    }

    /**
     * Flush rewrite rules.
     */
    private static function flush_rewrite_rules() {
        flush_rewrite_rules();
    }

    /**
     * Render activation error page.
     *
     * @param array $failures List of failure messages.
     * @return string HTML content.
     */
    private static function render_error_page($failures) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>خطای فعال‌سازی پلاگین</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 40px; background: #f1f1f1; }
                .error-box { background: #fff; border-left: 4px solid #dc3232; padding: 20px; max-width: 700px; margin: 0 auto; }
                .error-box h1 { margin-top: 0; color: #dc3232; }
                ul { list-style: disc; margin-left: 20px; }
                li { margin-bottom: 8px; }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>سیستم مدیریت خرید B2B - فعال‌سازی ناموفق</h1>
                <p>شرایط زیر برآورده نشده‌اند:</p>
                <ul>
                    <?php foreach ($failures as $failure) : ?>
                        <li><?php echo esc_html($failure); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>لطفاً این مشکلات را برطرف کرده و دوباره پلاگین را فعال کنید. برای راهنمایی بیشتر به مستندات پلاگین مراجعه کنید.</p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
