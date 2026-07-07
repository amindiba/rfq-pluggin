<?php
defined('ABSPATH') || exit;

final class B2B_Procurement_Bootstrap {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function define_constants() {
        if (!defined('B2B_PROCUREMENT_STORAGE_DIR')) {
            define('B2B_PROCUREMENT_STORAGE_DIR', WP_CONTENT_DIR . '/b2b-procurement/');
        }
        if (!defined('B2B_PROCUREMENT_LOG_FILE')) {
            define('B2B_PROCUREMENT_LOG_FILE', B2B_PROCUREMENT_STORAGE_DIR . 'logs/activity.log');
        }
    }

    private function load_dependencies() {
        $base = B2B_PROCUREMENT_PLUGIN_DIR;
        $files = array(
            'includes/core/class-b2b-environment-checker.php',
            'includes/core/class-b2b-activator.php',
            'includes/core/class-b2b-deactivator.php',
            'includes/core/class-b2b-loader.php',
            'includes/helpers/class-b2b-logger.php',
            'includes/helpers/class-b2b-security.php',
            'includes/storage/class-b2b-storage.php',
            'includes/database/class-b2b-master-data-db.php',
            'includes/database/class-b2b-geography-db.php',
            'includes/database/class-b2b-product-db.php',
            'includes/database/class-b2b-supplier-db.php',
            'includes/database/class-b2b-rfq-db.php',
            'includes/database/class-b2b-quotation-db.php',
            'includes/database/class-b2b-po-db.php',
            'includes/database/class-b2b-contract-db.php',
            'includes/database/class-b2b-notification-db.php',
            'includes/catalog/models/class-category.php',
            'includes/catalog/models/class-product.php',
            'includes/catalog/models/class-attribute.php',
            'includes/catalog/repositories/class-category-repository.php',
            'includes/catalog/repositories/class-product-repository.php',
            'includes/catalog/repositories/class-attribute-repository.php',
            'includes/catalog/services/class-category-service.php',
            'includes/catalog/services/class-attribute-service.php',
            'includes/catalog/services/class-wc-category-service.php',
            'includes/catalog/services/class-wc-product-service.php',
            'includes/catalog/validation/class-category-validator.php',
            'includes/catalog/validation/class-product-validator.php',
            'includes/catalog/validation/class-attribute-validator.php',
            'includes/catalog/controllers/class-category-controller.php',
            'includes/catalog/controllers/class-product-controller.php',
            'includes/catalog/controllers/class-attribute-controller.php',
            'includes/admin/class-b2b-admin.php',
            'includes/admin/class-b2b-dashboard.php',
            'includes/admin/class-b2b-welcome.php',
            'includes/admin/class-b2b-ui.php',
            'includes/admin/class-b2b-settings.php',
            'includes/admin/class-b2b-notices.php',
            'includes/admin/class-b2b-modal.php',
            'includes/admin/class-b2b-table.php',
            'includes/admin/class-b2b-form.php',
            'includes/admin/class-b2b-settings-page.php',
            'includes/admin/class-b2b-tools-page.php',
            'includes/admin/class-b2b-logs-page.php',
            'includes/admin/class-b2b-system-status-page.php',
            'includes/admin/class-b2b-help-page.php',
            'includes/admin/class-b2b-master-data.php',
            'includes/admin/class-b2b-geography.php',
            'includes/admin/class-b2b-product-catalog-admin.php',
            'includes/admin/class-b2b-supplier-admin.php',
            'includes/admin/class-b2b-rfq-admin.php',
            'includes/admin/class-b2b-quotation-admin.php',
            'includes/admin/class-b2b-po-admin.php',
            'includes/admin/class-b2b-contract-admin.php',
            'includes/admin/class-b2b-notification-admin.php',
            'includes/admin/class-b2b-dashboard-report.php',
            'includes/ajax/class-b2b-ajax.php',
            'includes/ajax/class-b2b-master-data-ajax.php',
            'includes/ajax/class-b2b-geography-ajax.php',
            'includes/ajax/class-b2b-product-catalog-ajax.php',
            'includes/ajax/class-b2b-supplier-ajax.php',
            'includes/ajax/class-b2b-rfq-ajax.php',
            'includes/ajax/class-b2b-quotation-ajax.php',
            'includes/ajax/class-b2b-po-ajax.php',
            'includes/ajax/class-b2b-contract-ajax.php',
            'includes/ajax/class-b2b-notification-ajax.php',
            'includes/api/class-b2b-rest-api.php',
        );
        foreach ($files as $file) {
            $path = $base . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
        // Persian Calendar Engine
        $pc_path = $base . 'core/calendar/PersianCalendar/bootstrap.php';
        if (file_exists($pc_path)) {
            require_once $pc_path;
        }
    }

    private function init_hooks() {
        register_activation_hook(B2B_PROCUREMENT_PLUGIN_FILE, array('B2B_Procurement_Activator', 'activate'));
        register_deactivation_hook(B2B_PROCUREMENT_PLUGIN_FILE, array('B2B_Procurement_Deactivator', 'deactivate'));
        add_action('plugins_loaded', array($this, 'init'));

        // Init Persian Calendar
        add_action('plugins_loaded', function () {
            if (class_exists('B2B_Persian_Calendar')) {
                B2B_Persian_Calendar::instance()->init();
            }
        }, 5);
    }

    public function init() {
        $classes = array(
            'B2B_Procurement_Logger', 'B2B_Procurement_Storage', 'B2B_Procurement_Notices',
            'B2B_Procurement_Admin', 'B2B_Procurement_Welcome', 'B2B_Procurement_Ajax',
            'B2B_Procurement_REST_API', 'B2B_Procurement_Master_Data', 'B2B_Procurement_Master_Data_Ajax',
            'B2B_Procurement_Geography', 'B2B_Procurement_Geography_Ajax',
            'B2B_Product_Catalog_Admin', 'B2B_Product_Catalog_Ajax',
            'B2B_Supplier_Admin', 'B2B_Supplier_Ajax',
            'B2B_Rfq_Admin', 'B2B_Rfq_Ajax',
            'B2B_Quotation_Admin', 'B2B_Quotation_Ajax',
            'B2B_PO_Admin', 'B2B_PO_Ajax',
            'B2B_Contract_Admin', 'B2B_Contract_Ajax',
            'B2B_Notification_Admin', 'B2B_Notification_Ajax',
            'B2B_Dashboard_Report',
        );
        foreach ($classes as $cls) {
            if (class_exists($cls) && method_exists($cls, 'init')) {
                try {
                    $cls::init();
                } catch (Throwable $e) {
                    @file_put_contents(WP_CONTENT_DIR . '/b2b-init-errors.log',
                        date('Y-m-d H:i:s') . " | $cls | " . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine() . "\n",
                        FILE_APPEND | LOCK_EX
                    );
                }
            }
        }
    }
}

B2B_Procurement_Bootstrap::instance();
