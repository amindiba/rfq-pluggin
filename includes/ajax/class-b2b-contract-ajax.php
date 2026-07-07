<?php
defined('ABSPATH') || exit;

class B2B_Contract_Ajax {

    public static function init() {
        add_action('wp_ajax_b2b_contract_get_list', array(__CLASS__, 'get_list'));
        add_action('wp_ajax_b2b_contract_get', array(__CLASS__, 'get_contract'));
        add_action('wp_ajax_b2b_contract_save', array(__CLASS__, 'save'));
        add_action('wp_ajax_b2b_contract_create_from_po', array(__CLASS__, 'create_from_po'));
        add_action('wp_ajax_b2b_contract_activate', array(__CLASS__, 'activate'));
        add_action('wp_ajax_b2b_contract_close', array(__CLASS__, 'close_contract'));
        add_action('wp_ajax_b2b_contract_delete', array(__CLASS__, 'delete_contract'));
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
        wp_send_json_success(B2B_Contract_DB::get_contracts($args));
    }

    public static function get_contract() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        $contract = B2B_Contract_DB::get_contract($id);
        if (!$contract) wp_send_json_error(array('message' => 'قرارداد یافت نشد'));
        wp_send_json_success(array('data' => $contract));
    }

    public static function create_from_po() {
        self::check();
        $po_id = intval($_POST['po_id'] ?? 0);
        if ($po_id <= 0) wp_send_json_error(array('message' => 'شناسه سفارش نامعتبر'));

        // Check PO exists and is confirmed
        $po = B2B_PO_DB::get_po($po_id);
        if (!$po) wp_send_json_error(array('message' => 'سفارش خرید یافت نشد'));
        if ($po->status !== 'confirmed') wp_send_json_error(array('message' => 'فقط سفارشات تأیید شده قابل تبدیل به قرارداد هستند'));

        // Check if contract already exists
        $existing = B2B_Contract_DB::get_contract_by_po($po_id);
        if ($existing) wp_send_json_error(array('message' => 'برای این سفارش قبلاً قرارداد ایجاد شده'));

        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $start_date = sanitize_text_field(wp_unslash($_POST['start_date'] ?? ''));
        $end_date = sanitize_text_field(wp_unslash($_POST['end_date'] ?? ''));
        $notes = sanitize_textarea_field(wp_unslash($_POST['notes'] ?? ''));

        if (empty($title)) wp_send_json_error(array('message' => 'عنوان قرارداد الزامی است'));
        if (empty($start_date)) wp_send_json_error(array('message' => 'تاریخ شروع الزامی است'));
        if (empty($end_date)) wp_send_json_error(array('message' => 'تاریخ پایان الزامی است'));
        if (strtotime($end_date) <= strtotime($start_date)) wp_send_json_error(array('message' => 'تاریخ پایان باید بعد از تاریخ شروع باشد'));

        $result = B2B_Contract_DB::create_contract(array(
            'title' => $title,
            'po_id' => $po->id,
            'rfq_id' => $po->rfq_id,
            'quotation_id' => $po->quotation_id,
            'supplier_id' => $po->supplier_id,
            'supplier_name' => $po->supplier_name,
            'po_number' => $po->po_number,
            'rfq_reference' => $po->rfq_reference,
            'quotation_reference' => $po->quotation_reference,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'contract_value' => $po->grand_total,
            'notes' => $notes,
        ));

        if (is_wp_error($result)) wp_send_json_error(array('message' => $result->get_error_message()));

        wp_send_json_success(array('message' => 'قرارداد با موفقیت ایجاد شد', 'id' => $result['id'], 'contract_number' => $result['contract_number']));
    }

    public static function save() {
        self::check();
        $id = intval($_POST['contract_id'] ?? 0);
        $contract = B2B_Contract_DB::get_contract($id);
        if (!$contract) wp_send_json_error(array('message' => 'قرارداد یافت نشد'));
        if ($contract->status !== 'draft') wp_send_json_error(array('message' => 'فقط قراردادهای پیش‌نویس قابل ویرایش هستند'));

        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $start_date = sanitize_text_field(wp_unslash($_POST['start_date'] ?? ''));
        $end_date = sanitize_text_field(wp_unslash($_POST['end_date'] ?? ''));
        $notes = sanitize_textarea_field(wp_unslash($_POST['notes'] ?? ''));

        if (empty($title)) wp_send_json_error(array('message' => 'عنوان الزامی است'));
        if (empty($start_date)) wp_send_json_error(array('message' => 'تاریخ شروع الزامی است'));
        if (empty($end_date)) wp_send_json_error(array('message' => 'تاریخ پایان الزامی است'));
        if (strtotime($end_date) <= strtotime($start_date)) wp_send_json_error(array('message' => 'تاریخ پایان باید بعد از تاریخ شروع باشد'));

        B2B_Contract_DB::update_contract($id, array('title' => $title, 'start_date' => $start_date, 'end_date' => $end_date, 'notes' => $notes));

        wp_send_json_success(array('message' => 'قرارداد بروزرسانی شد'));
    }

    public static function activate() {
        self::check();
        $id = intval($_POST['contract_id'] ?? 0);
        $contract = B2B_Contract_DB::get_contract($id);
        if (!$contract) wp_send_json_error(array('message' => 'قرارداد یافت نشد'));
        if ($contract->status !== 'draft') wp_send_json_error(array('message' => 'فقط قراردادهای پیش‌نویس قابل فعال‌سازی هستند'));

        B2B_Contract_DB::activate_contract($id);

        $users = get_users(array('role__in' => array('administrator', 'shop_manager'), 'fields' => 'ID'));
        foreach ($users as $user_id) {
            B2B_Notification_DB::create_notification_for_event('contract_activated', $user_id, $id, 'contract');
        }

        wp_send_json_success(array('message' => 'قرارداد فعال شد'));
    }

    public static function close_contract() {
        self::check();
        $id = intval($_POST['contract_id'] ?? 0);
        $contract = B2B_Contract_DB::get_contract($id);
        if (!$contract) wp_send_json_error(array('message' => 'قرارداد یافت نشد'));
        if ($contract->status === 'closed') wp_send_json_error(array('message' => 'قرارداد قبلاً بسته شده'));

        B2B_Contract_DB::close_contract($id);

        $users = get_users(array('role__in' => array('administrator', 'shop_manager'), 'fields' => 'ID'));
        foreach ($users as $user_id) {
            B2B_Notification_DB::create_notification_for_event('contract_closed', $user_id, $id, 'contract');
        }

        wp_send_json_success(array('message' => 'قرارداد بسته شد'));
    }

    public static function delete_contract() {
        self::check();
        $id = intval($_POST['contract_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        B2B_Contract_DB::delete_contract($id);
        wp_send_json_success(array('message' => 'قرارداد حذف شد'));
    }
}
