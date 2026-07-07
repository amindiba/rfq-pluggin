<?php
defined('ABSPATH') || exit;

class B2B_Category_Service {

    private $repo;
    private $validator;

    public function __construct() {
        $this->repo = new B2B_Category_Repository();
        $this->validator = new B2B_Category_Validator();
    }

    public function get_categories($args = array()) {
        return $this->repo->find_all($args);
    }

    public function get_category($id) {
        return $this->repo->find($id);
    }

    public function get_tree() {
        return $this->repo->find_tree();
    }

    public function create($data) {
        $clean = $this->validator->sanitize($data);
        if (!$this->validator->validate($clean)) {
            return array('success' => false, 'errors' => $this->validator->get_errors());
        }

        $model = new B2B_Category_Model();
        $model->name_fa = $clean['name_fa'];
        $model->name_en = $clean['name_en'];
        $model->slug = $clean['slug'];
        $model->description = $clean['description'];
        $model->icon = $clean['icon'];
        $model->image_url = $clean['image_url'];
        $model->sort_order = $clean['sort_order'];
        $model->status = $clean['status'];
        $model->parent_id = $clean['parent_id'];

        // Calculate depth and path
        if ($model->parent_id) {
            $parent = $this->repo->find($model->parent_id);
            if ($parent) {
                $model->depth = $parent->depth + 1;
                $model->path = $parent->path . $parent->id . '/';
            }
        }

        $id = $this->repo->save($model);
        if (!$id) {
            return array('success' => false, 'errors' => array('general' => 'خطا در ذخیره‌سازی'));
        }

        return array('success' => true, 'id' => $id, 'message' => 'دسته‌بندی با موفقیت ایجاد شد');
    }

    public function update($id, $data) {
        $existing = $this->repo->find($id);
        if (!$existing) {
            return array('success' => false, 'errors' => array('general' => 'دسته‌بندی یافت نشد'));
        }

        $clean = $this->validator->sanitize($data);
        if (!$this->validator->validate($clean, true)) {
            return array('success' => false, 'errors' => $this->validator->get_errors());
        }

        $existing->name_fa = $clean['name_fa'];
        $existing->name_en = $clean['name_en'];
        $existing->slug = $clean['slug'];
        $existing->description = $clean['description'];
        $existing->icon = $clean['icon'];
        $existing->image_url = $clean['image_url'];
        $existing->sort_order = $clean['sort_order'];
        $existing->status = $clean['status'];
        $existing->parent_id = $clean['parent_id'];

        // Recalculate depth and path
        if ($existing->parent_id && $existing->parent_id != $id) {
            $parent = $this->repo->find($existing->parent_id);
            if ($parent) {
                $existing->depth = $parent->depth + 1;
                $existing->path = $parent->path . $parent->id . '/';
            }
        } else {
            $existing->depth = 0;
            $existing->path = '/';
        }

        $this->repo->save($existing);
        return array('success' => true, 'message' => 'دسته‌بندی با موفقیت بروزرسانی شد');
    }

    public function delete($id, $permanent = false) {
        $existing = $this->repo->find($id);
        if (!$existing) {
            return array('success' => false, 'errors' => array('general' => 'دسته‌بندی یافت نشد'));
        }

        // Check for children
        $children = $this->repo->find_children($id);
        if (!empty($children) && !$permanent) {
            return array('success' => false, 'errors' => array('general' => 'این دسته‌بندی دارای زیرمجموعه است و قابل حذف نیست'));
        }

        // Check for products
        $product_repo = new B2B_Product_Repository();
        $product_count = $product_repo->count(array('category_id' => $id));
        if ($product_count > 0 && !$permanent) {
            return array('success' => false, 'errors' => array('general' => 'این دسته‌بندی دارای محصول است و قابل حذف نیست'));
        }

        $this->repo->delete($id, $permanent);
        $msg = $permanent ? 'دسته‌بندی برای همیشه حذف شد' : 'دسته‌بندی به زباله‌دان منتقل شد';
        return array('success' => true, 'message' => $msg);
    }

    public function restore($id) {
        $this->repo->restore($id);
        return array('success' => true, 'message' => 'دسته‌بندی بازیابی شد');
    }

    public function toggle_status($id) {
        $existing = $this->repo->find($id);
        if (!$existing) {
            return array('success' => false, 'errors' => array('general' => 'دسته‌بندی یافت نشد'));
        }

        global $wpdb;
        $new_status = $existing->status == 1 ? 0 : 1;
        $wpdb->update($wpdb->prefix . 'b2b_categories', array('status' => $new_status, 'updated_at' => current_time('mysql')), array('id' => intval($id)));

        return array('success' => true, 'message' => 'وضعیت دسته‌بندی تغییر کرد');
    }

    public function get_stats() {
        return array(
            'total' => $this->repo->count(),
            'active' => $this->repo->count(array('status' => 1)),
            'inactive' => $this->repo->count(array('status' => 0)),
        );
    }
}
