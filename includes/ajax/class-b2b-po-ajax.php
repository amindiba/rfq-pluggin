<?php
defined('ABSPATH') || exit;

class B2B_PO_Ajax {

    public static function init() {
        add_action('wp_ajax_b2b_po_get_list', array(__CLASS__, 'get_list'));
        add_action('wp_ajax_b2b_po_get', array(__CLASS__, 'get_po'));
        add_action('wp_ajax_b2b_po_save', array(__CLASS__, 'save'));
        add_action('wp_ajax_b2b_po_create_from_quotation', array(__CLASS__, 'create_from_quotation'));
        add_action('wp_ajax_b2b_po_confirm', array(__CLASS__, 'confirm'));
        add_action('wp_ajax_b2b_po_cancel', array(__CLASS__, 'cancel'));
        add_action('wp_ajax_b2b_po_delete', array(__CLASS__, 'delete_po'));
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
        wp_send_json_success(B2B_PO_DB::get_pos($args));
    }

    public static function get_po() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        $po = B2B_PO_DB::get_po($id);
        if (!$po) wp_send_json_error(array('message' => 'سفارش خرید یافت نشد'));
        $po->items = B2B_PO_DB::get_items($id);
        wp_send_json_success(array('data' => $po));
    }

    public static function create_from_quotation() {
        self::check();
        $quotation_id = intval($_POST['quotation_id'] ?? 0);
        if ($quotation_id <= 0) wp_send_json_error(array('message' => 'شناسه پیشنهاد نامعتبر'));

        // Check quotation exists and is selected
        $quotation = B2B_Quotation_DB::get_quotation($quotation_id);
        if (!$quotation) wp_send_json_error(array('message' => 'پیشنهاد یافت نشد'));
        if ($quotation->status !== 'selected') wp_send_json_error(array('message' => 'فقط پیشنهادات انتخاب شده قابل تبدیل به سفارش خرید هستند'));

        // Check if PO already exists
        $existing = B2B_PO_DB::get_po_by_quotation($quotation_id);
        if ($existing) wp_send_json_error(array('message' => 'برای این پیشنهاد قبلاً سفارش خرید ایجاد شده'));

        $rfq = B2B_Rfq_DB::get_rfq($quotation->rfq_id);
        $result = B2B_PO_DB::create_po(array(
            'rfq_id' => $quotation->rfq_id,
            'quotation_id' => $quotation_id,
            'supplier_id' => $quotation->supplier_id,
            'supplier_name' => $quotation->supplier_name,
            'rfq_reference' => $rfq ? $rfq->reference : '',
            'quotation_reference' => 'QUO-' . $quotation_id,
        ));

        if (is_wp_error($result)) wp_send_json_error(array('message' => $result->get_error_message()));

        // Copy quotation items to PO items
        $q_items = B2B_Quotation_DB::get_items($quotation_id);
        if (!empty($q_items)) {
            $grand_total = 0;
            $po_items = array();
            foreach ($q_items as $qi) {
                $po_items[] = array(
                    'product_id' => $qi->product_id,
                    'product_name' => $qi->product_name,
                    'product_sku' => $qi->product_sku,
                    'unit_price' => $qi->unit_price,
                    'quantity' => $qi->quantity,
                    'line_total' => $qi->line_total,
                    'delivery_days' => $qi->delivery_days,
                    'supplier_note' => $qi->supplier_note,
                );
                $grand_total += $qi->line_total;
            }
            B2B_PO_DB::save_items($result['id'], $po_items, $grand_total);
        }

        wp_send_json_success(array('message' => 'سفارش خرید با موفقیت ایجاد شد', 'id' => $result['id'], 'po_number' => $result['po_number']));
    }

    public static function save() {
        self::check();
        $id = intval($_POST['po_id'] ?? 0);
        $rfq = B2B_PO_DB::get_po($id);
        if (!$rfq) wp_send_json_error(array('message' => 'سفارش یافت نشد'));
        if ($rfq->status !== 'draft') wp_send_json_error(array('message' => 'فقط سفارشات پیش‌نویس قابل ویرایش هستند'));

        $notes = sanitize_textarea_field(wp_unslash($_POST['notes'] ?? ''));
        $items = json_decode(stripslashes($_POST['items'] ?? '[]'), true) ?? array();
        $grand_total = 0;

        foreach ($items as $item) {
            if (floatval($item['unit_price'] ?? 0) <= 0) wp_send_json_error(array('message' => 'قیمت واحد باید بیشتر از صفر باشد'));
            if (floatval($item['quantity'] ?? 0) <= 0) wp_send_json_error(array('message' => 'تعداد باید بیشتر از صفر باشد'));
            $grand_total += floatval($item['unit_price']) * floatval($item['quantity']);
        }

        B2B_PO_DB::update_po($id, array('notes' => $notes));
        B2B_PO_DB::save_items($id, $items, $grand_total);

        wp_send_json_success(array('message' => 'سفارش بروزرسانی شد'));
    }

    public static function confirm() {
        self::check();
        $id = intval($_POST['po_id'] ?? 0);
        $po = B2B_PO_DB::get_po($id);
        if (!$po) wp_send_json_error(array('message' => 'سفارش یافت نشد'));
        if ($po->status !== 'draft') wp_send_json_error(array('message' => 'فقط سفارشات پیش‌نویس قابل تأیید هستند'));

        B2B_PO_DB::confirm_po($id);

        $users = get_users(array('role__in' => array('administrator', 'shop_manager'), 'fields' => 'ID'));
        foreach ($users as $user_id) {
            B2B_Notification_DB::create_notification_for_event('po_confirmed', $user_id, $id, 'purchase_order');
        }

        wp_send_json_success(array('message' => 'سفارش خرید تأیید شد'));
    }

    public static function cancel() {
        self::check();
        $id = intval($_POST['po_id'] ?? 0);
        $po = B2B_PO_DB::get_po($id);
        if (!$po) wp_send_json_error(array('message' => 'سفارش یافت نشد'));
        if ($po->status === 'cancelled') wp_send_json_error(array('message' => 'سفارش قبلاً لغو شده'));

        B2B_PO_DB::cancel_po($id);
        wp_send_json_success(array('message' => 'سفارش لغو شد'));
    }

    public static function delete_po() {
        self::check();
        $id = intval($_POST['po_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        B2B_PO_DB::delete_po($id);
        wp_send_json_success(array('message' => 'سفارش حذف شد'));
    }
}
