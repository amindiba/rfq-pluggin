<?php
defined('ABSPATH') || exit;

class B2B_Category_Validator {

    private $errors = array();

    public function validate($data, $is_update = false) {
        $this->errors = array();

        if (empty($data['name_fa'])) {
            $this->errors['name_fa'] = 'نام فارسی الزامی است';
        } elseif (mb_strlen($data['name_fa']) > 150) {
            $this->errors['name_fa'] = 'نام فارسی حداکثر ۱۵۰ کاراکتر باشد';
        }

        if (empty($data['name_en'])) {
            $this->errors['name_en'] = 'نام انگلیسی الزامی است';
        } elseif (mb_strlen($data['name_en']) > 150) {
            $this->errors['name_en'] = 'نام انگلیسی حداکثر ۱۵۰ کاراکتر باشد';
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $data['name_en'])) {
            $this->errors['name_en'] = 'نام انگلیسی فقط شامل حروف، اعداد و فاصله باشد';
        }

        if (isset($data['parent_id']) && $data['parent_id'] && intval($data['parent_id']) > 0) {
            // Parent existence check would go here if needed
        }

        if (isset($data['sort_order']) && (intval($data['sort_order']) < 0 || intval($data['sort_order']) > 9999)) {
            $this->errors['sort_order'] = 'ترتیب نمایش باید بین ۰ تا ۹۹۹۹ باشد';
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
        $clean['name_fa'] = sanitize_text_field($data['name_fa'] ?? '');
        $clean['name_en'] = sanitize_text_field($data['name_en'] ?? '');
        $clean['slug'] = sanitize_title($data['name_en'] ?? '');
        $clean['description'] = sanitize_textarea_field($data['description'] ?? '');
        $clean['icon'] = sanitize_text_field($data['icon'] ?? '');
        $clean['image_url'] = esc_url_raw($data['image_url'] ?? '');
        $clean['sort_order'] = intval($data['sort_order'] ?? 0);
        $clean['status'] = intval($data['status'] ?? 1);
        $clean['parent_id'] = !empty($data['parent_id']) ? intval($data['parent_id']) : null;
        return $clean;
    }
}
