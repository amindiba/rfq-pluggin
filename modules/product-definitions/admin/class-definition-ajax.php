<?php
namespace B2B\ProductDefinitions\Admin;

use B2B\ProductDefinitions\Database\Definition_DB;

defined('ABSPATH') || exit;

class Definition_Ajax {

    public function __construct() {
        add_action('wp_ajax_b2b_pd_save', array($this, 'handle_save'));
        add_action('wp_ajax_b2b_pd_delete', array($this, 'handle_delete'));
        add_action('wp_ajax_b2b_pd_toggle', array($this, 'handle_toggle'));
    }

    public function handle_save() {
        check_ajax_referer(B2B_Procurement_Security::NONCE_ACTION, '_b2b_nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
        }

        $id   = intval($_POST['definition_id'] ?? 0);
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));

        if (empty($name)) {
            wp_send_json_error(array('message' => 'نام الزامی است'));
        }

        $data = array(
            'name'        => $name,
            'slug'        => sanitize_title(wp_unslash($_POST['slug'] ?? '')),
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'is_active'   => intval($_POST['is_active'] ?? 1),
            'sort_order'  => intval($_POST['sort_order'] ?? 0),
        );

        if ($id) {
            Definition_DB::update($id, $data);
            wp_send_json_success(array('message' => 'تعریف بروزرسانی شد'));
        } else {
            $new_id = Definition_DB::insert($data);
            if ($new_id) {
                wp_send_json_success(array('message' => 'تعریف ایجاد شد', 'id' => $new_id));
            } else {
                wp_send_json_error(array('message' => 'خطا در ایجاد تعریف'));
            }
        }
    }

    public function handle_delete() {
        check_ajax_referer('b2b_pd_delete_' . intval($_POST['id'] ?? 0), 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
        }

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        }

        Definition_DB::delete($id);
        wp_send_json_success(array('message' => 'تعریف حذف شد'));
    }

    public function handle_toggle() {
        check_ajax_referer('b2b_pd_toggle_' . intval($_POST['id'] ?? 0), 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
        }

        $id = intval($_POST['id'] ?? 0);
        $item = Definition_DB::get($id);
        if (!$item) {
            wp_send_json_error(array('message' => 'تعریف یافت نشد'));
        }

        Definition_DB::update($id, array('is_active' => $item->is_active ? 0 : 1));
        wp_send_json_success(array('message' => 'وضعیت تغییر کرد'));
    }
}
