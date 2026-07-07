<?php
defined('ABSPATH') || exit;

class B2B_Notification_DB {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . 'b2b_notifications';

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'info',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            related_module VARCHAR(50) DEFAULT '',
            related_id BIGINT UNSIGNED DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user (user_id),
            KEY idx_read (is_read),
            KEY idx_type (type),
            KEY idx_created (created_at),
            KEY idx_related (related_module, related_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('b2b_notification_db_version', '1.0.0');
    }

    public static function get_notifications($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_notifications';

        $defaults = array('user_id' => 0, 'is_read' => '', 'type' => '', 'per_page' => 20, 'page' => 1);
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['user_id'])) { $where[] = "user_id = %d"; $values[] = intval($args['user_id']); }
        if ($args['is_read'] !== '') { $where[] = "is_read = %d"; $values[] = intval($args['is_read']); }
        if (!empty($args['type'])) { $where[] = "type = %s"; $values[] = $args['type']; }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", $values));
            $params = array_merge($values, array($args['per_page'], $offset));
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d", $params));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}");
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        return array('items' => $items ? $items : array(), 'total' => $total, 'pages' => ceil($total / $args['per_page']), 'page' => $args['page'], 'per_page' => $args['per_page']);
    }

    public static function get_notification($id, $user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_notifications WHERE id = %d AND user_id = %d", intval($id), intval($user_id)));
    }

    public static function create_notification($data) {
        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix . 'b2b_notifications', array(
            'user_id' => intval($data['user_id']),
            'type' => sanitize_text_field($data['type'] ?? 'info'),
            'title' => sanitize_text_field($data['title']),
            'message' => sanitize_textarea_field($data['message']),
            'related_module' => sanitize_text_field($data['related_module'] ?? ''),
            'related_id' => intval($data['related_id'] ?? 0),
            'created_at' => current_time('mysql'),
        ));
        return $result ? $wpdb->insert_id : false;
    }

    public static function mark_read($id, $user_id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_notifications', array('is_read' => 1), array('id' => intval($id), 'user_id' => intval($user_id)));
    }

    public static function mark_all_read($user_id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_notifications', array('is_read' => 1), array('user_id' => intval($user_id), 'is_read' => 0));
    }

    public static function get_unread_count($user_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}b2b_notifications WHERE user_id = %d AND is_read = 0", intval($user_id)));
    }

    public static function create_notification_for_event($event, $user_id, $related_id, $related_module) {
        $events = array(
            'rfq_submitted' => array('type' => 'info', 'title' => 'درخواست خرید ارسال شد', 'message' => 'درخواست خرید جدیدی ارسال شده و منتظر پیشنهاد قیمت تامین‌کنندگان است.'),
            'quotation_selected' => array('type' => 'success', 'title' => 'پیشنهاد قیمت برنده انتخاب شد', 'message' => 'یک پیشنهاد قیمت به عنوان برنده انتخاب شده و آماده ایجاد سفارش خرید است.'),
            'po_confirmed' => array('type' => 'success', 'title' => 'سفارش خرید تأیید شد', 'message' => 'سفارش خرید با موفقیت تأیید شد و آماده ایجاد قرارداد است.'),
            'contract_activated' => array('type' => 'success', 'title' => 'قرارداد فعال شد', 'message' => 'قرارداد با موفقیت فعال شد.'),
            'contract_closed' => array('type' => 'warning', 'title' => 'قرارداد بسته شد', 'message' => 'قرارداد بسته شد و دیگر قابل ویرایش نیست.'),
        );

        if (!isset($events[$event])) return false;

        return self::create_notification(array(
            'user_id' => $user_id,
            'type' => $events[$event]['type'],
            'title' => $events[$event]['title'],
            'message' => $events[$event]['message'],
            'related_module' => $related_module,
            'related_id' => $related_id,
        ));
    }
}
