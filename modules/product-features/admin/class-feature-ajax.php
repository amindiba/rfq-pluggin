<?php
namespace B2B\ProductFeatures\Admin;

use B2B\ProductFeatures\Database\Feature_DB;

defined('ABSPATH') || exit;

class Feature_Ajax {

    public static function init() {
        add_action('wp_ajax_b2b_pf_save', array(__CLASS__, 'handle_save'));
        add_action('wp_ajax_b2b_pf_delete', array(__CLASS__, 'handle_delete'));
        add_action('wp_ajax_b2b_pf_load_fields', array(__CLASS__, 'handle_load_fields'));
    }

    public static function handle_save() {
        check_ajax_referer(B2B_Procurement_Security::NONCE_ACTION, '_b2b_nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));

        $id   = intval($_POST['feature_id'] ?? 0);
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        if (empty($name)) wp_send_json_error(array('message' => 'نام الزامی است'));

        $data = array(
            'name'          => $name,
            'slug'          => sanitize_title(wp_unslash($_POST['slug'] ?? '')),
            'group_name'    => sanitize_text_field(wp_unslash($_POST['group_name'] ?? '')),
            'feature_type'  => sanitize_key($_POST['feature_type'] ?? 'text'),
            'unit'          => sanitize_text_field(wp_unslash($_POST['unit'] ?? '')),
            'is_required'   => isset($_POST['is_required']) ? 1 : 0,
            'is_searchable' => isset($_POST['is_searchable']) ? 1 : 0,
            'is_filterable' => isset($_POST['is_filterable']) ? 1 : 0,
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
            'sort_order'    => intval($_POST['sort_order'] ?? 0),
        );

        $ft = $data['feature_type'];
        if (in_array($ft, array('select', 'checkbox', 'radio'), true) && isset($_POST['options']) && is_array($_POST['options'])) {
            $data['options'] = array_filter(array_map('sanitize_text_field', array_map('wp_unslash', $_POST['options'])));
        }

        if ($id) {
            Feature_DB::update($id, $data);
            wp_send_json_success(array('message' => 'ویژگی بروزرسانی شد'));
        } else {
            $new_id = Feature_DB::insert($data);
            wp_send_json_success(array('message' => 'ویژگی ایجاد شد', 'id' => $new_id));
        }
    }

    public static function handle_delete() {
        check_ajax_referer(B2B_Procurement_Security::NONCE_ACTION, '_b2b_nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
        $id = intval($_POST['id'] ?? 0);
        if (!$id) wp_send_json_error(array('message' => 'شناسه نامعتبر'));
        Feature_DB::delete($id);
        wp_send_json_success(array('message' => 'ویژگی حذف شد'));
    }

    public static function handle_load_fields() {
        check_ajax_referer(B2B_Procurement_Security::NONCE_ACTION, '_b2b_nonce');
        $product_id = intval($_POST['product_id'] ?? 0);
        if (!$product_id) wp_send_json_error(array('message' => 'شناسه محصول نامعتبر'));

        $features = Feature_DB::get_active_all();
        $values   = \B2B\ProductFeatures\Database\FeatureValue_DB::get_values($product_id);

        $fields = array();
        foreach ($features as $feat) {
            $fields[] = array(
                'id'          => $feat->id,
                'name'        => $feat->name,
                'slug'        => $feat->slug,
                'group_name'  => $feat->group_name,
                'feature_type' => $feat->feature_type,
                'unit'        => $feat->unit,
                'options'     => $feat->options,
                'is_required' => $feat->is_required,
                'value'       => isset($values[$feat->slug]) ? $values[$feat->slug] : '',
            );
        }
        wp_send_json_success(array('fields' => $fields));
    }
}
