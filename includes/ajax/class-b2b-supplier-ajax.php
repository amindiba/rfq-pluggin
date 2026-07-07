<?php
defined('ABSPATH') || exit;

class B2B_Supplier_Ajax {

    public static function init() {
        add_action('wp_ajax_b2b_supplier_get_list', array(__CLASS__, 'get_list'));
        add_action('wp_ajax_b2b_supplier_get', array(__CLASS__, 'get_supplier'));
        add_action('wp_ajax_b2b_supplier_save', array(__CLASS__, 'save'));
        add_action('wp_ajax_b2b_supplier_delete', array(__CLASS__, 'delete_supplier'));
        add_action('wp_ajax_b2b_supplier_restore', array(__CLASS__, 'restore'));
        add_action('wp_ajax_b2b_supplier_toggle', array(__CLASS__, 'toggle'));
        add_action('wp_ajax_b2b_supplier_bulk', array(__CLASS__, 'bulk_action'));
    }

    private static function check() {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
        }
        if (!isset($_POST['_b2b_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_b2b_nonce'])), 'b2b_procurement_nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی'));
        }
        return true;
    }

    public static function get_list() {
        self::check();
        $args = array(
            'search' => sanitize_text_field(wp_unslash($_POST['search'] ?? '')),
            'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? '')),
            'per_page' => max(1, intval($_POST['per_page'] ?? 20)),
            'page' => max(1, intval($_POST['page'] ?? 1)),
        );
        wp_send_json_success(B2B_Supplier_DB::get_suppliers($args));
    }

    public static function get_supplier() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        $item = B2B_Supplier_DB::get_supplier($id);
        if (!$item) {
            wp_send_json_error(array('message' => 'تامین‌کننده یافت نشد'));
        }
        wp_send_json_success(array('data' => $item));
    }

    public static function save() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        $data = $_POST;

        if (empty($data['code'])) wp_send_json_error(array('message' => 'کد تامین‌کننده الزامی است'));
        if (empty($data['name'])) wp_send_json_error(array('message' => 'نام تامین‌کننده الزامی است'));
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $data['code'])) {
            wp_send_json_error(array('message' => 'کد باید فقط شامل حروف انگلیسی، اعداد و خط تیره باشد'));
        }
        if (!empty($data['email']) && !is_email($data['email'])) {
            wp_send_json_error(array('message' => 'فرمت ایمیل معتبر نیست'));
        }

        if ($id > 0) {
            $existing = B2B_Supplier_DB::get_supplier_by_code($data['code']);
            if ($existing && $existing->id != $id) {
                wp_send_json_error(array('message' => 'کد تکراری است'));
            }
            B2B_Supplier_DB::update_supplier($id, $data);
            wp_send_json_success(array('message' => 'تامین‌کننده بروزرسانی شد'));
        } else {
            $existing = B2B_Supplier_DB::get_supplier_by_code($data['code']);
            if ($existing) {
                wp_send_json_error(array('message' => 'کد تکراری است'));
            }
            $result = B2B_Supplier_DB::create_supplier($data);
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }
            wp_send_json_success(array('message' => 'تامین‌کننده ایجاد شد', 'id' => $result));
        }
    }

    public static function delete_supplier() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        B2B_Supplier_DB::delete_supplier($id);
        wp_send_json_success(array('message' => 'تامین‌کننده حذف شد'));
    }

    public static function restore() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        B2B_Supplier_DB::restore_supplier($id);
        wp_send_json_success(array('message' => 'تامین‌کننده بازیابی شد'));
    }

    public static function toggle() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        B2B_Supplier_DB::toggle_status($id);
        wp_send_json_success(array('message' => 'وضعیت تغییر کرد'));
    }

    public static function bulk_action() {
        self::check();
        $action = sanitize_text_field(wp_unslash($_POST['bulk_action'] ?? ''));
        $ids = array_map('intval', $_POST['ids'] ?? array());
        if (empty($ids)) wp_send_json_error(array('message' => 'موردی انتخاب نشده'));

        if ($action === 'delete') {
            B2B_Supplier_DB::bulk_delete($ids);
            wp_send_json_success(array('message' => count($ids) . ' تامین‌کننده حذف شد'));
        } elseif ($action === 'restore') {
            B2B_Supplier_DB::bulk_restore($ids);
            wp_send_json_success(array('message' => count($ids) . ' تامین‌کننده بازیابی شد'));
        } else {
            wp_send_json_error(array('message' => 'عملیات نامعتبر'));
        }
    }
}
