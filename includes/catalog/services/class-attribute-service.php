<?php
defined('ABSPATH') || exit;

class B2B_Attribute_Service {

    private $repo;
    private $validator;

    public function __construct() {
        $this->repo = new B2B_Attribute_Repository();
        $this->validator = new B2B_Attribute_Validator();
    }

    public function get_attributes($args = array()) {
        return $this->repo->find_all($args);
    }

    public function get_attribute($id) {
        return $this->repo->find($id);
    }

    public function create($data) {
        $clean = $this->validator->sanitize($data);
        if (!$this->validator->validate($clean)) {
            return array('success' => false, 'errors' => $this->validator->get_errors());
        }

        // Check code uniqueness
        $existing = $this->repo->find_by_code($clean['code']);
        if ($existing) {
            return array('success' => false, 'errors' => array('code' => 'کد ویژگی تکراری است'));
        }

        $model = new B2B_Attribute_Model();
        foreach ($clean as $key => $value) {
            if (property_exists($model, $key)) {
                $model->$key = $value;
            }
        }

        $id = $this->repo->save($model);
        if (!$id) {
            return array('success' => false, 'errors' => array('general' => 'خطا در ذخیره‌سازی'));
        }

        return array('success' => true, 'id' => $id, 'message' => 'ویژگی با موفقیت ایجاد شد');
    }

    public function update($id, $data) {
        $existing = $this->repo->find($id);
        if (!$existing) {
            return array('success' => false, 'errors' => array('general' => 'ویژگی یافت نشد'));
        }

        $clean = $this->validator->sanitize($data);
        if (!$this->validator->validate($clean, true, $id)) {
            return array('success' => false, 'errors' => $this->validator->get_errors());
        }

        // Check code uniqueness if changed
        if ($clean['code'] !== $existing->code) {
            $dup = $this->repo->find_by_code($clean['code']);
            if ($dup) {
                return array('success' => false, 'errors' => array('code' => 'کد ویژگی تکراری است'));
            }
        }

        foreach ($clean as $key => $value) {
            if (property_exists($existing, $key)) {
                $existing->$key = $value;
            }
        }

        $this->repo->save($existing);
        return array('success' => true, 'message' => 'ویژگی با موفقیت بروزرسانی شد');
    }

    public function delete($id, $permanent = false) {
        $existing = $this->repo->find($id);
        if (!$existing) {
            return array('success' => false, 'errors' => array('general' => 'ویژگی یافت نشد'));
        }

        $this->repo->delete($id, $permanent);
        $msg = $permanent ? 'ویژگی برای همیشه حذف شد' : 'ویژگی به زباله‌دان منتقل شد';
        return array('success' => true, 'message' => $msg);
    }

    public function restore($id) {
        $this->repo->restore($id);
        return array('success' => true, 'message' => 'ویژگی بازیابی شد');
    }

    public function get_stats() {
        return array(
            'total' => $this->repo->count(),
            'active' => $this->repo->count(array('status' => 1)),
            'inactive' => $this->repo->count(array('status' => 0)),
        );
    }
}
