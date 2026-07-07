<?php
defined('ABSPATH') || exit;

class B2B_Rfq_Ajax {

    public static function init() {
        add_action('wp_ajax_b2b_rfq_get_list', array(__CLASS__, 'get_list'));
        add_action('wp_ajax_b2b_rfq_get', array(__CLASS__, 'get_rfq'));
        add_action('wp_ajax_b2b_rfq_save', array(__CLASS__, 'save'));
        add_action('wp_ajax_b2b_rfq_submit', array(__CLASS__, 'submit'));
        add_action('wp_ajax_b2b_rfq_close', array(__CLASS__, 'close_rfq'));
        add_action('wp_ajax_b2b_rfq_delete', array(__CLASS__, 'delete_rfq'));
    }

    private static function check() {
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
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
        wp_send_json_success(B2B_Rfq_DB::get_rfqs($args));
    }

    public static function get_rfq() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        $rfq = B2B_Rfq_DB::get_rfq($id);
        if (!$rfq) wp_send_json_error(array('message' => 'درخواست یافت نشد'));

        $rfq->products = B2B_Rfq_DB::get_rfq_products($id);
        $rfq->suppliers = B2B_Rfq_DB::get_rfq_suppliers($id);
        wp_send_json_success(array('data' => $rfq));
    }

    public static function save() {
        self::check();
        $id = intval($_POST['rfq_id'] ?? 0);
        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $deadline = sanitize_text_field(wp_unslash($_POST['deadline'] ?? ''));
        $products = json_decode(stripslashes($_POST['products'] ?? '[]'), true) ?? array();
        $suppliers = json_decode(stripslashes($_POST['suppliers'] ?? '[]'), true) ?? array();

        // Validation
        if (empty($title)) wp_send_json_error(array('message' => 'عنوان الزامی است'));
        if (empty($deadline)) wp_send_json_error(array('message' => 'مهلت پیشنهاد قیمت الزامی است'));
        if (strtotime($deadline) < strtotime('today')) wp_send_json_error(array('message' => 'مهلت نمی‌تواند در گذشته باشد'));
        if (empty($products)) wp_send_json_error(array('message' => 'حداقل یک محصول انتخاب کنید'));
        if (empty($suppliers)) wp_send_json_error(array('message' => 'حداقل یک تامین‌کننده انتخاب کنید'));

        foreach ($products as $p) {
            if (floatval($p['requested_qty'] ?? 0) <= 0) {
                wp_send_json_error(array('message' => 'تعداد درخواستی باید بیشتر از صفر باشد'));
            }
        }

        if ($id > 0) {
            $existing = B2B_Rfq_DB::get_rfq($id);
            if (!$existing) wp_send_json_error(array('message' => 'درخواست یافت نشد'));
            if ($existing->status !== 'draft') wp_send_json_error(array('message' => 'فقط درخواست‌های پیش‌نویس قابل ویرایش هستند'));

            B2B_Rfq_DB::update_rfq($id, array('title' => $title, 'description' => $description, 'deadline' => $deadline));
            B2B_Rfq_DB::save_rfq_products($id, $products);
            B2B_Rfq_DB::save_rfq_suppliers($id, $suppliers);
            wp_send_json_success(array('message' => 'درخواست بروزرسانی شد', 'id' => $id));
        } else {
            $result = B2B_Rfq_DB::create_rfq(array('title' => $title, 'description' => $description, 'deadline' => $deadline));
            if (is_wp_error($result)) wp_send_json_error(array('message' => $result->get_error_message()));

            B2B_Rfq_DB::save_rfq_products($result['id'], $products);
            B2B_Rfq_DB::save_rfq_suppliers($result['id'], $suppliers);
            wp_send_json_success(array('message' => 'درخواست ایجاد شد', 'id' => $result['id'], 'reference' => $result['reference']));
        }
    }

    public static function submit() {
        self::check();
        $id = intval($_POST['rfq_id'] ?? 0);
        $rfq = B2B_Rfq_DB::get_rfq($id);
        if (!$rfq) wp_send_json_error(array('message' => 'درخواست یافت نشد'));
        if ($rfq->status !== 'draft') wp_send_json_error(array('message' => 'فقط درخواست‌های پیش‌نویس قابل ارسال هستند'));

        B2B_Rfq_DB::submit_rfq($id);

        // Notify all admin/shop_manager users
        $users = get_users(array('role__in' => array('administrator', 'shop_manager'), 'fields' => 'ID'));
        foreach ($users as $user_id) {
            B2B_Notification_DB::create_notification_for_event('rfq_submitted', $user_id, $id, 'rfq');
        }

        wp_send_json_success(array('message' => 'درخواست با موفقیت ارسال شد'));
    }

    public static function close_rfq() {
        self::check();
        $id = intval($_POST['rfq_id'] ?? 0);
        $rfq = B2B_Rfq_DB::get_rfq($id);
        if (!$rfq) wp_send_json_error(array('message' => 'درخواست یافت نشد'));
        if ($rfq->status === 'closed') wp_send_json_error(array('message' => 'درخواست قبلاً بسته شده'));

        B2B_Rfq_DB::close_rfq($id);
        wp_send_json_success(array('message' => 'درخواست بسته شد'));
    }

    public static function delete_rfq() {
        self::check();
        $id = intval($_POST['rfq_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        B2B_Rfq_DB::delete_rfq($id);
        wp_send_json_success(array('message' => 'درخواست حذف شد'));
    }
}
