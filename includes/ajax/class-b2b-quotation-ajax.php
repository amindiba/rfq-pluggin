<?php
defined('ABSPATH') || exit;

class B2B_Quotation_Ajax {

    public static function init() {
        add_action('wp_ajax_b2b_quotation_get_list', array(__CLASS__, 'get_list'));
        add_action('wp_ajax_b2b_quotation_get', array(__CLASS__, 'get_quotation'));
        add_action('wp_ajax_b2b_quotation_save', array(__CLASS__, 'save'));
        add_action('wp_ajax_b2b_quotation_submit', array(__CLASS__, 'submit'));
        add_action('wp_ajax_b2b_quotation_compare', array(__CLASS__, 'compare'));
        add_action('wp_ajax_b2b_quotation_select_winner', array(__CLASS__, 'select_winner'));
        add_action('wp_ajax_b2b_quotation_delete', array(__CLASS__, 'delete_quotation'));
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
            'rfq_id' => intval($_POST['rfq_id'] ?? 0),
            'per_page' => max(1, intval($_POST['per_page'] ?? 20)),
            'page' => max(1, intval($_POST['page'] ?? 1)),
        );
        wp_send_json_success(B2B_Quotation_DB::get_quotations($args));
    }

    public static function get_quotation() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        $q = B2B_Quotation_DB::get_quotation($id);
        if (!$q) wp_send_json_error(array('message' => 'پیشنهاد یافت نشد'));
        $q->items = B2B_Quotation_DB::get_items($id);
        wp_send_json_success(array('data' => $q));
    }

    public static function save() {
        self::check();
        $rfq_id = intval($_POST['rfq_id'] ?? 0);
        $supplier_id = intval($_POST['supplier_id'] ?? 0);
        $supplier_name = sanitize_text_field(wp_unslash($_POST['supplier_name'] ?? ''));
        $notes = sanitize_textarea_field(wp_unslash($_POST['notes'] ?? ''));
        $items = json_decode(stripslashes($_POST['items'] ?? '[]'), true) ?? array();

        // Validation
        if ($rfq_id <= 0) wp_send_json_error(array('message' => 'درخواست نامعتبر است'));
        if ($supplier_id <= 0) wp_send_json_error(array('message' => 'تامین‌کننده الزامی است'));
        if (empty($items)) wp_send_json_error(array('message' => 'حداقل یک ردیف محصول اضافه کنید'));

        // Check RFQ is submitted
        $rfq = B2B_Rfq_DB::get_rfq($rfq_id);
        if (!$rfq || $rfq->status !== 'submitted') {
            wp_send_json_error(array('message' => 'فقط درخواست‌های ارسال شده پیشنهاد قیمت می‌پذیرند'));
        }

        // Check one quotation per supplier
        $existing = B2B_Quotation_DB::get_quotation_by_rfq_supplier($rfq_id, $supplier_id);
        if ($existing) {
            wp_send_json_error(array('message' => 'این تامین‌کننده قبلاً پیشنهاد ارائه داده است'));
        }

        // Validate items
        foreach ($items as $item) {
            if (floatval($item['unit_price'] ?? 0) <= 0) {
                wp_send_json_error(array('message' => 'قیمت واحد باید بیشتر از صفر باشد'));
            }
            if (floatval($item['quantity'] ?? 0) <= 0) {
                wp_send_json_error(array('message' => 'تعداد باید بیشتر از صفر باشد'));
            }
            if (intval($item['delivery_days'] ?? 0) < 0) {
                wp_send_json_error(array('message' => 'زمان تحویل نمی‌تواند منفی باشد'));
            }
        }

        $id = B2B_Quotation_DB::create_quotation(array(
            'rfq_id' => $rfq_id,
            'supplier_id' => $supplier_id,
            'supplier_name' => $supplier_name,
            'notes' => $notes,
        ));

        if (is_wp_error($id)) wp_send_json_error(array('message' => $id->get_error_message()));

        $grand_total = B2B_Quotation_DB::save_items($id, $items);
        B2B_Quotation_DB::submit_quotation($id, $grand_total);

        wp_send_json_success(array('message' => 'پیشنهاد قیمت با موفقیت ارسال شد', 'id' => $id, 'grand_total' => $grand_total));
    }

    public static function compare() {
        self::check();
        $rfq_id = intval($_POST['rfq_id'] ?? 0);
        if ($rfq_id <= 0) wp_send_json_error(array('message' => 'درخواست نامعتبر'));

        $quotations = B2B_Quotation_DB::get_quotations_for_rfq($rfq_id);
        $items = B2B_Quotation_DB::get_items_for_comparison($rfq_id);

        $comparison = array();
        foreach ($items as $item) {
            $pid = $item->product_id;
            if (!isset($comparison[$pid])) {
                $comparison[$pid] = array(
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'suppliers' => array(),
                );
            }
            $comparison[$pid]['suppliers'][] = array(
                'supplier_id' => $item->supplier_id,
                'supplier_name' => $item->supplier_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
                'delivery_days' => $item->delivery_days,
            );
        }

        wp_send_json_success(array('quotations' => $quotations, 'comparison' => array_values($comparison)));
    }

    public static function select_winner() {
        self::check();
        $id = intval($_POST['quotation_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'پیشنهاد نامعتبر'));

        $quotation = B2B_Quotation_DB::get_quotation($id);
        if (!$quotation) wp_send_json_error(array('message' => 'پیشنهاد یافت نشد'));
        if ($quotation->status !== 'submitted') wp_send_json_error(array('message' => 'فقط پیشنهادات ارسال شده قابل انتخاب هستند'));

        // Check if already has a winner
        $rfq_quotations = B2B_Quotation_DB::get_quotations_for_rfq($quotation->rfq_id);
        foreach ($rfq_quotations as $q) {
            if ($q->status === 'selected') {
                wp_send_json_error(array('message' => 'این درخواست قبلاً برنده دارد'));
            }
        }

        B2B_Quotation_DB::select_winner($id);

        // Notify all admin/shop_manager users
        $users = get_users(array('role__in' => array('administrator', 'shop_manager'), 'fields' => 'ID'));
        foreach ($users as $user_id) {
            B2B_Notification_DB::create_notification_for_event('quotation_selected', $user_id, $quotation->rfq_id, 'quotation');
        }

        wp_send_json_success(array('message' => 'پیشنهاد برنده انتخاب شد'));
    }

    public static function delete_quotation() {
        self::check();
        $id = intval($_POST['quotation_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));

        $q = B2B_Quotation_DB::get_quotation($id);
        if (!$q) wp_send_json_error(array('message' => 'پیشنهاد یافت نشد'));
        if ($q->status !== 'draft') wp_send_json_error(array('message' => 'فقط پیشنهادات پیش‌نویس قابل حذف هستند'));

        B2B_Quotation_DB::delete_quotation($id);
        wp_send_json_success(array('message' => 'پیشنهاد حذف شد'));
    }
}
