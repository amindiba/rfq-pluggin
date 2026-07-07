<?php
defined('ABSPATH') || exit;

class B2B_Notification_Ajax {

    public static function init() {
        add_action('wp_ajax_b2b_notification_get_list', array(__CLASS__, 'get_list'));
        add_action('wp_ajax_b2b_notification_get', array(__CLASS__, 'get_notification'));
        add_action('wp_ajax_b2b_notification_mark_read', array(__CLASS__, 'mark_read'));
        add_action('wp_ajax_b2b_notification_mark_all_read', array(__CLASS__, 'mark_all_read'));
        add_action('wp_ajax_b2b_notification_unread_count', array(__CLASS__, 'unread_count'));
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
        $user_id = get_current_user_id();
        $args = array(
            'user_id' => $user_id,
            'is_read' => isset($_POST['is_read']) ? intval($_POST['is_read']) : '',
            'type' => sanitize_text_field(wp_unslash($_POST['type'] ?? '')),
            'per_page' => max(1, intval($_POST['per_page'] ?? 20)),
            'page' => max(1, intval($_POST['page'] ?? 1)),
        );
        wp_send_json_success(B2B_Notification_DB::get_notifications($args));
    }

    public static function get_notification() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        $user_id = get_current_user_id();
        $notification = B2B_Notification_DB::get_notification($id, $user_id);

        if (!$notification) wp_send_json_error(array('message' => 'اعلان یافت نشد'));

        // Mark as read
        if (!$notification->is_read) {
            B2B_Notification_DB::mark_read($id, $user_id);
            $notification->is_read = 1;
        }

        wp_send_json_success(array('data' => $notification));
    }

    public static function mark_read() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        $user_id = get_current_user_id();

        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));

        B2B_Notification_DB::mark_read($id, $user_id);
        wp_send_json_success(array('message' => 'اعلان خوانده شد'));
    }

    public static function mark_all_read() {
        self::check();
        $user_id = get_current_user_id();
        B2B_Notification_DB::mark_all_read($user_id);
        wp_send_json_success(array('message' => 'همه اعلان‌ها خوانده شد'));
    }

    public static function unread_count() {
        self::check();
        $user_id = get_current_user_id();
        $count = B2B_Notification_DB::get_unread_count($user_id);
        wp_send_json_success(array('count' => $count));
    }
}
