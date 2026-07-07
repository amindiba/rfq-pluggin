<?php
defined('ABSPATH') || exit;

class B2B_Attribute_Validator {

    private $errors = array();

    public function validate($data, $is_update = false, $exclude_id = 0) {
        $this->errors = array();

        $valid_types = array('text', 'textarea', 'number', 'select', 'multiselect', 'date', 'boolean');
        if (empty($data['type']) || !in_array($data['type'], $valid_types)) {
            $this->errors['type'] = 'نوع ویژگی معتبر نیست';
        }

        if (empty($data['name_fa'])) {
            $this->errors['name_fa'] = 'نام فارسی الزامی است';
        } elseif (mb_strlen($data['name_fa']) > 100) {
            $this->errors['name_fa'] = 'نام فارسی حداکثر ۱۰۰ کاراکتر باشد';
        }

        if (empty($data['name_en'])) {
            $this->errors['name_en'] = 'نام انگلیسی الزامی است';
        } elseif (mb_strlen($data['name_en']) > 100) {
            $this->errors['name_en'] = 'نام انگلیسی حداکثر ۱۰۰ کاراکتر باشد';
        }

        if (empty($data['code'])) {
            $this->errors['code'] = 'کد ویژگی الزامی است';
        } elseif (!preg_match('/^[a-z0-9_]+$/', $data['code'])) {
            $this->errors['code'] = 'کد ویژگی فقط شامل حروف کوچک، اعداد و خط زیر باشد';
        } elseif (mb_strlen($data['code']) > 50) {
            $this->errors['code'] = 'کد ویژگی حداکثر ۵۰ کاراکتر باشد';
        }

        if (isset($data['options']) && is_array($data['options'])) {
            if (in_array($data['type'], array('select', 'multiselect')) && empty($data['options'])) {
                $this->errors['options'] = 'برای نوع انتخابی باید گزینه‌هایی تعریف شود';
            }
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
        $clean['code'] = sanitize_text_field($data['code'] ?? '');
        $clean['type'] = sanitize_text_field($data['type'] ?? 'text');
        $clean['options'] = isset($data['options']) && is_array($data['options']) ? array_map('sanitize_text_field', $data['options']) : null;
        $clean['is_required'] = intval($data['is_required'] ?? 0);
        $clean['is_filterable'] = intval($data['is_filterable'] ?? 0);
        $clean['is_searchable'] = intval($data['is_searchable'] ?? 0);
        $clean['sort_order'] = intval($data['sort_order'] ?? 0);
        $clean['status'] = intval($data['status'] ?? 1);
        return $clean;
    }
}
