<?php
defined('ABSPATH') || exit;

class B2B_Product_Service {

    private $repo;
    private $validator;
    private $attr_repo;

    public function __construct() {
        $this->repo = new B2B_Product_Repository();
        $this->validator = new B2B_Product_Validator();
        $this->attr_repo = new B2B_Attribute_Repository();
    }

    public function get_products($args = array()) {
        return $this->repo->find_all($args);
    }

    public function get_product($id) {
        $product = $this->repo->find($id);
        if ($product && $product->has_attributes) {
            $product->attributes = $this->attr_repo->get_product_attributes($id);
        }
        return $product;
    }

    public function create($data) {
        $clean = $this->validator->sanitize($data);
        if (!$this->validator->validate($clean)) {
            return array('success' => false, 'errors' => $this->validator->get_errors());
        }

        $model = new B2B_Product_Model();
        foreach ($clean as $key => $value) {
            if (property_exists($model, $key)) {
                $model->$key = $value;
            }
        }

        $id = $this->repo->save($model);
        if (!$id) {
            return array('success' => false, 'errors' => array('general' => 'خطا در ذخیره‌سازی'));
        }

        // Update category product count
        if (!empty($clean['category_id'])) {
            $this->repo->update_category_count($clean['category_id']);
        }

        return array('success' => true, 'id' => $id, 'message' => 'محصول با موفقیت ایجاد شد');
    }

    public function update($id, $data) {
        $existing = $this->repo->find($id);
        if (!$existing) {
            return array('success' => false, 'errors' => array('general' => 'محصول یافت نشد'));
        }

        $clean = $this->validator->sanitize($data);
        if (!$this->validator->validate($clean, true, $id)) {
            return array('success' => false, 'errors' => $this->validator->get_errors());
        }

        $old_category = $existing->category_id;

        foreach ($clean as $key => $value) {
            if (property_exists($existing, $key)) {
                $existing->$key = $value;
            }
        }

        $this->repo->save($existing);

        // Update category product counts
        if ($old_category) {
            $this->repo->update_category_count($old_category);
        }
        if (!empty($clean['category_id']) && $clean['category_id'] != $old_category) {
            $this->repo->update_category_count($clean['category_id']);
        }

        return array('success' => true, 'message' => 'محصول با موفقیت بروزرسانی شد');
    }

    public function delete($id, $permanent = false) {
        $existing = $this->repo->find($id);
        if (!$existing) {
            return array('success' => false, 'errors' => array('general' => 'محصول یافت نشد'));
        }

        $cat_id = $existing->category_id;
        $this->repo->delete($id, $permanent);

        if ($cat_id) {
            $this->repo->update_category_count($cat_id);
        }

        $msg = $permanent ? 'محصول برای همیشه حذف شد' : 'محصول به زباله‌دان منتقل شد';
        return array('success' => true, 'message' => $msg);
    }

    public function restore($id) {
        $this->repo->restore($id);
        $product = $this->repo->find($id);
        if ($product && $product->category_id) {
            $this->repo->update_category_count($product->category_id);
        }
        return array('success' => true, 'message' => 'محصول بازیابی شد');
    }

    public function toggle_status($id) {
        $existing = $this->repo->find($id);
        if (!$existing) {
            return array('success' => false, 'errors' => array('general' => 'محصول یافت نشد'));
        }

        global $wpdb;
        $new_status = $existing->status == 1 ? 0 : 1;
        $wpdb->update($wpdb->prefix . 'b2b_products', array('status' => $new_status, 'updated_at' => current_time('mysql')), array('id' => intval($id)));

        if ($existing->category_id) {
            $this->repo->update_category_count($existing->category_id);
        }

        return array('success' => true, 'message' => 'وضعیت محصول تغییر کرد');
    }

    public function get_product_attributes($product_id) {
        return $this->attr_repo->get_product_attributes($product_id);
    }

    public function set_product_attributes($product_id, $attributes) {
        foreach ($attributes as $attr) {
            $attr_id = intval($attr['attribute_id'] ?? 0);
            $value = $attr['value_text'] ?? ($attr['value_number'] ?? '');
            if ($attr_id && ($value !== '' && $value !== null)) {
                $this->attr_repo->set_product_attribute($product_id, $attr_id, $value);
            }
        }
        return array('success' => true, 'message' => 'ویژگی‌ها با موفقیت بروزرسانی شدند');
    }

    public function get_stats() {
        return array(
            'total' => $this->repo->count(),
            'active' => $this->repo->count(array('status' => 1)),
            'inactive' => $this->repo->count(array('status' => 0)),
            'draft' => $this->repo->count(array('status' => 0)),
        );
    }
}
