<?php
namespace B2B\ProductFeatures;

defined('ABSPATH') || exit;

final class Bootstrap {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        if (!is_admin()) return;

        require_once __DIR__ . '/database/class-feature-db.php';
        require_once __DIR__ . '/database/class-feature-value-db.php';
        require_once __DIR__ . '/admin/class-feature-admin.php';
        require_once __DIR__ . '/admin/class-feature-ajax.php';
        require_once __DIR__ . '/admin/class-feature-product-meta.php';

        Database\Feature_DB::create_table();
        Database\FeatureValue_DB::create_table();

        Admin\Feature_Admin::init();
        Admin\Feature_Product_Meta::init();
        Admin\Feature_Ajax::init();
    }
}
