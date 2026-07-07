<?php
/**
 * Product Resource Manager - Bootstrap
 *
 * @package B2B_Procurement
 */

namespace B2B\ProductResources;

defined('ABSPATH') || exit;

final class Bootstrap {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'load'));
    }

    public function load() {
        if (!is_admin()) return;
        if (!current_user_can('edit_products')) return;

        require_once __DIR__ . '/admin/class-meta-box.php';
        require_once __DIR__ . '/admin/class-ajax-handler.php';

        new Admin\Meta_Box();
        new Admin\Ajax_Handler();

        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;

        $screen = get_current_screen();
        if (!$screen || 'product' !== $screen->post_type) return;

        $base_url = plugin_dir_url(__FILE__);

        wp_enqueue_style(
            'b2b-product-resources',
            $base_url . 'assets/css/admin.css',
            array(),
            '1.4.0'
        );

        wp_enqueue_script(
            'b2b-product-resources',
            $base_url . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            '1.4.0',
            true
        );

        wp_localize_script('b2b-product-resources', 'b2bPR', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('b2b_product_resources_nonce'),
            'i18n'    => array(
                'addResource'    => '+ افزودن منبع',
                'deleteConfirm'  => 'آیا از حذف این منبع اطمینان دارید؟',
                'uploadFile'     => 'انتخاب فایل',
                'uploadThumb'    => 'انتخاب تصویر شاخص',
                'noFile'         => 'فایلی انتخاب نشده',
                'collapseAll'    => 'بستن همه',
                'expandAll'      => 'باز کردن همه',
            ),
        ));
    }
}
