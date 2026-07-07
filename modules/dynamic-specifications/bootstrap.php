<?php
namespace B2B\DynamicSpecs;

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

        require_once __DIR__ . '/database/class-spec-db.php';
        require_once __DIR__ . '/database/class-spec-value-db.php';
        require_once __DIR__ . '/field-types/class-registry.php';
        require_once __DIR__ . '/admin/class-spec-admin.php';
        require_once __DIR__ . '/admin/class-spec-ajax.php';
        require_once __DIR__ . '/admin/class-spec-product-meta.php';

        Database\Spec_DB::create_table();
        Database\SpecValue_DB::create_table();

        Admin\Spec_Admin::init();
        Admin\Spec_Product_Meta::init();
        Admin\Spec_Ajax::init();
        FieldType\Registry::init();
    }
}
