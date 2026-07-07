<?php
defined('ABSPATH') || exit;

class B2B_Product_Validator {

    private $errors = array();
    private $repo;

    public function __construct() {
        $this->repo = new B2B_Product_Repository();
    }

    public function validate($data, $is_update = false, $exclude_id = 0) {
        $this->errors = array();

        if (empty($data['sku'])) {
            $this->errors['sku'] = 'کد محصول الزامی است';
        } elseif (!preg_match('/^[A-Za-z0-9\-_]+$/', $data['sku'])) {
            $this->errors['sku'] = 'کد محصول فقط شامل حروف انگلیسی، اعداد و خط تیره باشد';
        } else {
            $existing = $this->repo->find_by_sku($data['sku']);
            if ($existing && (!$is_update || $existing->id != $exclude_id)) {
                $this->errors['sku'] = 'کد محصول تکراری است';
            }
        }

        if (empty($data['name_fa'])) {
            $this->errors['name_fa'] = 'نام فارسی الزامی است';
        } elseif (mb_strlen($data['name_fa']) > 255) {
            $this->errors['name_fa'] = 'نام فارسی حداکثر ۲۵۵ کاراکتر باشد';
        }

        if (empty($data['name_en'])) {
            $this->errors['name_en'] = 'نام انگلیسی الزامی است';
        } elseif (mb_strlen($data['name_en']) > 255) {
            $this->errors['name_en'] = 'نام انگلیسی حداکثر ۲۵۵ کاراکتر باشد';
        }

        if (isset($data['min_order_qty']) && floatval($data['min_order_qty']) < 0) {
            $this->errors['min_order_qty'] = 'حداقل تعداد سفارش نمی‌تواند منفی باشد';
        }

        if (isset($data['max_order_qty']) && $data['max_order_qty'] !== null && $data['max_order_qty'] !== '') {
            if (floatval($data['max_order_qty']) < floatval($data['min_order_qty'] ?? 0)) {
                $this->errors['max_order_qty'] = 'حداکثر تعداد نمی‌تواند کمتر از حداقل باشد';
            }
        }

        if (isset($data['lead_time_days']) && (intval($data['lead_time_days']) < 0 || intval($data['lead_time_days']) > 365)) {
            $this->errors['lead_time_days'] = 'زمان تحویل باید بین ۰ تا ۳۶۵ روز باشد';
        }

        $valid_statuses = array(0, 1);
        if (isset($data['status']) && !in_array(intval($data['status']), $valid_statuses)) {
            $this->errors['status'] = 'وضعیت معتبر نیست';
        }

        $valid_visibility = array(0, 1);
        if (isset($data['visibility']) && !in_array(intval($data['visibility']), $valid_visibility)) {
            $this->errors['visibility'] = 'قابلیت نمایش معتبر نیست';
        }

        return empty($this->errors);
    }

    public function is_valid() {
        return empty($this->errors);
    }

    public function get_errors() {
        return $this->errors;
    }

    public function get_error($field) {
        return isset($this->errors[$field]) ? $this->errors[$field] : null;
    }

    public function sanitize($data) {
        $clean = array();
        $clean['sku'] = sanitize_text_field($data['sku'] ?? '');
        $clean['name_fa'] = sanitize_text_field($data['name_fa'] ?? '');
        $clean['name_en'] = sanitize_text_field($data['name_en'] ?? '');
        $clean['slug'] = sanitize_title($data['name_en'] ?? '');
        $clean['description'] = wp_kses_post($data['description'] ?? '');
        $clean['short_desc'] = sanitize_text_field($data['short_desc'] ?? '');
        $clean['category_id'] = !empty($data['category_id']) ? intval($data['category_id']) : null;
        $clean['base_unit'] = sanitize_text_field($data['base_unit'] ?? 'pcs');
        $clean['weight'] = !empty($data['weight']) ? floatval($data['weight']) : null;
        $clean['weight_unit'] = sanitize_text_field($data['weight_unit'] ?? 'kg');
        $clean['min_order_qty'] = floatval($data['min_order_qty'] ?? 1);
        $clean['max_order_qty'] = !empty($data['max_order_qty']) ? floatval($data['max_order_qty']) : null;
        $clean['lead_time_days'] = intval($data['lead_time_days'] ?? 0);
        $clean['status'] = intval($data['status'] ?? 0);
        $clean['visibility'] = intval($data['visibility'] ?? 1);
        $clean['has_variants'] = intval($data['has_variants'] ?? 0);
        $clean['has_attributes'] = intval($data['has_attributes'] ?? 0);
        $clean['meta'] = isset($data['meta']) ? $data['meta'] : null;
        $clean['tags'] = isset($data['tags']) ? $data['tags'] : null;
        $clean['images'] = isset($data['images']) ? $data['images'] : null;
        return $clean;
    }
}
