<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Master_Data_Ajax {

    public static function init() {
        add_action('wp_ajax_b2b_md_get_units', array(__CLASS__, 'get_units'));
        add_action('wp_ajax_b2b_md_create_unit', array(__CLASS__, 'create_unit'));
        add_action('wp_ajax_b2b_md_update_unit', array(__CLASS__, 'update_unit'));
        add_action('wp_ajax_b2b_md_delete_unit', array(__CLASS__, 'delete_unit'));
        add_action('wp_ajax_b2b_md_restore_unit', array(__CLASS__, 'restore_unit'));
        add_action('wp_ajax_b2b_md_toggle_unit', array(__CLASS__, 'toggle_unit'));
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

    public static function get_units() {
        self::check();

        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';

        // Check table exists
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) {
            B2B_Procurement_Master_Data_DB::create_tables();
        }

        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 20;
        $offset = ($page - 1) * $per_page;

        $where = "deleted_at IS NULL";
        $params = array();

        if ($search !== '') {
            $where .= " AND (title LIKE %s OR short_name LIKE %s)";
            $s = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $s;
            $params[] = $s;
        }

        if ($status !== '') {
            $where .= " AND status = %s";
            $params[] = $status;
        }

        // Total count
        $sql_count = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        if (!empty($params)) {
            $total = (int) $wpdb->get_var($wpdb->prepare($sql_count, $params));
        } else {
            $total = (int) $wpdb->get_var($sql_count);
        }

        // Data
        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY sort_order ASC, id ASC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        $items = $wpdb->get_results($wpdb->prepare($sql, $params));

        wp_send_json_success(array(
            'items' => $items ? $items : array(),
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'page' => $page,
            'per_page' => $per_page,
        ));
    }

    public static function create_unit() {
        self::check();

        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) {
            B2B_Procurement_Master_Data_DB::create_tables();
        }

        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $short_name = sanitize_text_field(wp_unslash($_POST['short_name'] ?? ''));

        if ($title === '') {
            wp_send_json_error(array('message' => 'عنوان الزامی است'));
        }
        if ($short_name === '') {
            wp_send_json_error(array('message' => 'نام اختصاری الزامی است'));
        }
        if (!preg_match('/^[a-zA-Z]+$/', $short_name)) {
            wp_send_json_error(array('message' => 'نام اختصاری باید فقط شامل حروف انگلیسی باشد'));
        }

        $id = B2B_Procurement_Master_Data_DB::create_unit($_POST);

        if (is_wp_error($id)) {
            wp_send_json_error(array('message' => $id->get_error_message()));
        }

        wp_send_json_success(array('message' => 'واحد با موفقیت ایجاد شد', 'id' => $id));
    }

    public static function update_unit() {
        self::check();

        $id = intval($_POST['unit_id'] ?? 0);
        if ($id <= 0) {
            wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        }

        $short_name = sanitize_text_field(wp_unslash($_POST['short_name'] ?? ''));
        if (!preg_match('/^[a-zA-Z]+$/', $short_name)) {
            wp_send_json_error(array('message' => 'نام اختصاری باید فقط شامل حروف انگلیسی باشد'));
        }

        $result = B2B_Procurement_Master_Data_DB::update_unit($id, $_POST);
        if ($result === false) {
            wp_send_json_error(array('message' => 'خطا در بروزرسانی'));
        }

        wp_send_json_success(array('message' => 'واحد بروزرسانی شد'));
    }

    public static function delete_unit() {
        self::check();

        $id = intval($_POST['unit_id'] ?? 0);
        if ($id <= 0) {
            wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        }

        $permanent = !empty($_POST['permanent']);
        B2B_Procurement_Master_Data_DB::delete_unit($id, $permanent);

        wp_send_json_success(array('message' => $permanent ? 'حذف دائمی شد' : 'به زباله‌دان منتقل شد'));
    }

    public static function restore_unit() {
        self::check();

        $id = intval($_POST['unit_id'] ?? 0);
        if ($id <= 0) {
            wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        }

        B2B_Procurement_Master_Data_DB::restore_unit($id);
        wp_send_json_success(array('message' => 'بازیابی شد'));
    }

    public static function toggle_unit() {
        self::check();

        $id = intval($_POST['unit_id'] ?? 0);
        if ($id <= 0) {
            wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        }

        B2B_Procurement_Master_Data_DB::toggle_unit_status($id);
        wp_send_json_success(array('message' => 'وضعیت تغییر کرد'));
    }
}
