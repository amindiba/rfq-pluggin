<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Geography_Ajax {

    public static function init() {
        add_action('wp_ajax_b2b_geo_get_provinces', array(__CLASS__, 'get_provinces'));
        add_action('wp_ajax_b2b_geo_create_province', array(__CLASS__, 'create_province'));
        add_action('wp_ajax_b2b_geo_update_province', array(__CLASS__, 'update_province'));
        add_action('wp_ajax_b2b_geo_delete_province', array(__CLASS__, 'delete_province'));
        add_action('wp_ajax_b2b_geo_restore_province', array(__CLASS__, 'restore_province'));
        add_action('wp_ajax_b2b_geo_toggle_province', array(__CLASS__, 'toggle_province'));
        add_action('wp_ajax_b2b_geo_bulk_provinces', array(__CLASS__, 'bulk_provinces'));

        add_action('wp_ajax_b2b_geo_get_cities', array(__CLASS__, 'get_cities'));
        add_action('wp_ajax_b2b_geo_create_city', array(__CLASS__, 'create_city'));
        add_action('wp_ajax_b2b_geo_update_city', array(__CLASS__, 'update_city'));
        add_action('wp_ajax_b2b_geo_delete_city', array(__CLASS__, 'delete_city'));
        add_action('wp_ajax_b2b_geo_restore_city', array(__CLASS__, 'restore_city'));
        add_action('wp_ajax_b2b_geo_toggle_city', array(__CLASS__, 'toggle_city'));
        add_action('wp_ajax_b2b_geo_bulk_cities', array(__CLASS__, 'bulk_cities'));

        add_action('wp_ajax_b2b_geo_import_csv', array(__CLASS__, 'import_csv'));
        add_action('wp_ajax_b2b_geo_export_csv', array(__CLASS__, 'export_csv'));
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

    // ==================== PROVINCES ====================

    public static function get_provinces() {
        self::check();
        $args = array(
            'search' => sanitize_text_field(wp_unslash($_POST['search'] ?? '')),
            'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? '')),
            'orderby' => sanitize_text_field(wp_unslash($_POST['orderby'] ?? 'sort_order')),
            'order' => sanitize_text_field(wp_unslash($_POST['order'] ?? 'ASC')),
            'per_page' => max(1, intval($_POST['per_page'] ?? 20)),
            'page' => max(1, intval($_POST['page'] ?? 1)),
            'include_deleted' => !empty($_POST['include_deleted']),
        );
        wp_send_json_success(B2B_Procurement_Geography_DB::get_provinces($args));
    }

    public static function create_province() {
        self::check();
        $data = $_POST;
        if (empty($data['name_fa'])) wp_send_json_error(array('message' => 'نام فارسی الزامی است'));
        if (empty($data['name_en'])) wp_send_json_error(array('message' => 'نام انگلیسی الزامی است'));
        if (empty($data['code'])) wp_send_json_error(array('message' => 'کد الزامی است'));
        if (!preg_match('/^[a-zA-Z\s]+$/', $data['name_en'])) wp_send_json_error(array('message' => 'نام انگلیسی باید فقط حروف باشد'));
        $id = B2B_Procurement_Geography_DB::create_province($data);
        if (is_wp_error($id)) wp_send_json_error(array('message' => $id->get_error_message()));
        wp_send_json_success(array('message' => 'استان با موفقیت ایجاد شد', 'id' => $id));
    }

    public static function update_province() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        if (empty($_POST['name_fa'])) wp_send_json_error(array('message' => 'نام فارسی الزامی است'));
        if (empty($_POST['name_en'])) wp_send_json_error(array('message' => 'نام انگلیسی الزامی است'));
        if (!preg_match('/^[a-zA-Z\s]+$/', $_POST['name_en'])) wp_send_json_error(array('message' => 'نام انگلیسی باید فقط حروف باشد'));
        $result = B2B_Procurement_Geography_DB::update_province($id, $_POST);
        if ($result === false) wp_send_json_error(array('message' => 'خطا در بروزرسانی'));
        wp_send_json_success(array('message' => 'استان بروزرسانی شد'));
    }

    public static function delete_province() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        $permanent = !empty($_POST['permanent']);
        $result = B2B_Procurement_Geography_DB::delete_province($id, $permanent);
        if (is_wp_error($result)) wp_send_json_error(array('message' => $result->get_error_message()));
        wp_send_json_success(array('message' => $permanent ? 'حذف دائمی شد' : 'به زباله‌دان منتقل شد'));
    }

    public static function restore_province() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        B2B_Procurement_Geography_DB::restore_province($id);
        wp_send_json_success(array('message' => 'بازیابی شد'));
    }

    public static function toggle_province() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        B2B_Procurement_Geography_DB::toggle_province_status($id);
        wp_send_json_success(array('message' => 'وضعیت تغییر کرد'));
    }

    public static function bulk_provinces() {
        self::check();
        $action = sanitize_text_field(wp_unslash($_POST['bulk_action'] ?? ''));
        $ids = array_map('intval', $_POST['ids'] ?? array());
        if (empty($ids)) wp_send_json_error(array('message' => 'موردی انتخاب نشده'));
        if ($action === 'delete') {
            B2B_Procurement_Geography_DB::bulk_delete('b2b_provinces', $ids);
            wp_send_json_success(array('message' => count($ids) . ' استان به زباله‌دان منتقل شد'));
        } elseif ($action === 'restore') {
            B2B_Procurement_Geography_DB::bulk_restore('b2b_provinces', $ids);
            wp_send_json_success(array('message' => count($ids) . ' استان بازیابی شد'));
        } else {
            wp_send_json_error(array('message' => 'عملیات نامعتبر'));
        }
    }

    // ==================== CITIES ====================

    public static function get_cities() {
        self::check();
        $args = array(
            'search' => sanitize_text_field(wp_unslash($_POST['search'] ?? '')),
            'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? '')),
            'province_id' => intval($_POST['province_id'] ?? 0),
            'orderby' => sanitize_text_field(wp_unslash($_POST['orderby'] ?? 'c.sort_order')),
            'order' => sanitize_text_field(wp_unslash($_POST['order'] ?? 'ASC')),
            'per_page' => max(1, intval($_POST['per_page'] ?? 20)),
            'page' => max(1, intval($_POST['page'] ?? 1)),
            'include_deleted' => !empty($_POST['include_deleted']),
        );
        wp_send_json_success(B2B_Procurement_Geography_DB::get_cities($args));
    }

    public static function create_city() {
        self::check();
        $data = $_POST;
        if (empty($data['province_id'])) wp_send_json_error(array('message' => 'استان الزامی است'));
        if (empty($data['name_fa'])) wp_send_json_error(array('message' => 'نام فارسی الزامی است'));
        if (empty($data['name_en'])) wp_send_json_error(array('message' => 'نام انگلیسی الزامی است'));
        if (empty($data['code'])) wp_send_json_error(array('message' => 'کد الزامی است'));
        if (!preg_match('/^[a-zA-Z\s]+$/', $data['name_en'])) wp_send_json_error(array('message' => 'نام انگلیسی باید فقط حروف باشد'));
        $id = B2B_Procurement_Geography_DB::create_city($data);
        if (is_wp_error($id)) wp_send_json_error(array('message' => $id->get_error_message()));
        wp_send_json_success(array('message' => 'شهر با موفقیت ایجاد شد', 'id' => $id));
    }

    public static function update_city() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        if (empty($_POST['province_id'])) wp_send_json_error(array('message' => 'استان الزامی است'));
        if (empty($_POST['name_fa'])) wp_send_json_error(array('message' => 'نام فارسی الزامی است'));
        if (empty($_POST['name_en'])) wp_send_json_error(array('message' => 'نام انگلیسی الزامی است'));
        if (!preg_match('/^[a-zA-Z\s]+$/', $_POST['name_en'])) wp_send_json_error(array('message' => 'نام انگلیسی باید فقط حروف باشد'));
        $result = B2B_Procurement_Geography_DB::update_city($id, $_POST);
        if ($result === false) wp_send_json_error(array('message' => 'خطا در بروزرسانی'));
        wp_send_json_success(array('message' => 'شهر بروزرسانی شد'));
    }

    public static function delete_city() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        $permanent = !empty($_POST['permanent']);
        B2B_Procurement_Geography_DB::delete_city($id, $permanent);
        wp_send_json_success(array('message' => $permanent ? 'حذف دائمی شد' : 'به زباله‌دان منتقل شد'));
    }

    public static function restore_city() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        B2B_Procurement_Geography_DB::restore_city($id);
        wp_send_json_success(array('message' => 'بازیابی شد'));
    }

    public static function toggle_city() {
        self::check();
        $id = intval($_POST['item_id'] ?? 0);
        if ($id <= 0) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        B2B_Procurement_Geography_DB::toggle_city_status($id);
        wp_send_json_success(array('message' => 'وضعیت تغییر کرد'));
    }

    public static function bulk_cities() {
        self::check();
        $action = sanitize_text_field(wp_unslash($_POST['bulk_action'] ?? ''));
        $ids = array_map('intval', $_POST['ids'] ?? array());
        if (empty($ids)) wp_send_json_error(array('message' => 'موردی انتخاب نشده'));
        if ($action === 'delete') {
            B2B_Procurement_Geography_DB::bulk_delete('b2b_cities', $ids);
            wp_send_json_success(array('message' => count($ids) . ' شهر به زباله‌دان منتقل شد'));
        } elseif ($action === 'restore') {
            B2B_Procurement_Geography_DB::bulk_restore('b2b_cities', $ids);
            wp_send_json_success(array('message' => count($ids) . ' شهر بازیابی شد'));
        } else {
            wp_send_json_error(array('message' => 'عملیات نامعتبر'));
        }
    }

    // ==================== IMPORT ====================

    public static function import_csv() {
        self::check();
        $type = sanitize_text_field(wp_unslash($_POST['import_type'] ?? ''));

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'فایل آپلود نشد'));
        }

        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            wp_send_json_error(array('message' => 'فقط فایل CSV مجاز است'));
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            wp_send_json_error(array('message' => 'خطا در خواندن فایل'));
        }

        $imported = 0;
        $errors = 0;
        $line = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if ($line === 1) continue; // Skip header

            if ($type === 'provinces') {
                if (count($row) < 3) { $errors++; continue; }
                $data = array(
                    'name_fa' => trim($row[0]),
                    'name_en' => trim($row[1]),
                    'code' => trim($row[2]),
                    'status' => isset($row[3]) ? trim($row[3]) : 'active',
                    'sort_order' => isset($row[4]) ? intval($row[4]) : $line,
                );
                $result = B2B_Procurement_Geography_DB::create_province($data);
                if (is_wp_error($result)) { $errors++; } else { $imported++; }
            } elseif ($type === 'cities') {
                if (count($row) < 4) { $errors++; continue; }
                $data = array(
                    'province_id' => intval($row[0]),
                    'name_fa' => trim($row[1]),
                    'name_en' => trim($row[2]),
                    'code' => trim($row[3]),
                    'status' => isset($row[4]) ? trim($row[4]) : 'active',
                    'sort_order' => isset($row[5]) ? intval($row[5]) : $line,
                );
                $result = B2B_Procurement_Geography_DB::create_city($data);
                if (is_wp_error($result)) { $errors++; } else { $imported++; }
            }
        }

        fclose($handle);
        wp_send_json_success(array(
            'message' => " {$imported} رکورد با موفقیت وارد شد. {$errors} رکورد خطا داشت.",
            'imported' => $imported,
            'errors' => $errors,
        ));
    }

    public static function export_csv() {
        self::check();
        $type = sanitize_text_field(wp_unslash($_POST['export_type'] ?? ''));
        $status = sanitize_text_field(wp_unslash($_POST['status'] ?? ''));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $type . '_export_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        if ($type === 'provinces') {
            fputcsv($output, array('نام فارسی', 'نام انگلیسی', 'کد', 'وضعیت'));
            $args = array('per_page' => 9999, 'include_deleted' => !empty($_POST['include_deleted']));
            if ($status) $args['status'] = $status;
            $data = B2B_Procurement_Geography_DB::get_provinces($args);
            foreach ($data['items'] as $item) {
                fputcsv($output, array($item->name_fa, $item->name_en, $item->code, $item->status));
            }
        } elseif ($type === 'cities') {
            fputcsv($output, array('استان', 'نام فارسی', 'نام انگلیسی', 'کد', 'وضعیت'));
            $args = array('per_page' => 9999, 'include_deleted' => !empty($_POST['include_deleted']));
            if ($status) $args['status'] = $status;
            $data = B2B_Procurement_Geography_DB::get_cities($args);
            foreach ($data['items'] as $item) {
                fputcsv($output, array($item->province_name, $item->name_fa, $item->name_en, $item->code, $item->status));
            }
        }

        fclose($output);
        exit;
    }
}
