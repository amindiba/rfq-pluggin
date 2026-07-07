<?php
namespace B2B\ProductDefinitions;

defined('ABSPATH') || exit;

final class Bootstrap {
    private static $instance = null;
    private $admin;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init() {
        if (!is_admin()) return;

        require_once __DIR__ . '/database/class-definition-db.php';
        require_once __DIR__ . '/admin/class-definition-admin.php';
        require_once __DIR__ . '/admin/class-definition-ajax.php';
        require_once __DIR__ . '/admin/class-definition-product-meta.php';

        Database\Definition_DB::create_table();

        $this->admin = new Admin\Definition_Admin();
        $this->admin->init();

        new Admin\Definition_Product_Meta();
        new Admin\Definition_Ajax();
    }
}
