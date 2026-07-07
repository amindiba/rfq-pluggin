<?php
namespace B2B\DynamicSpecs\Admin;

use B2B\DynamicSpecs\Database\Spec_DB;
use B2B\DynamicSpecs\Database\SpecValue_DB;

defined('ABSPATH') || exit;

class Spec_Ajax {

    public static function init() {
        add_action('wp_ajax_b2b_spec_save', array(__CLASS__, 'handle_save'));
        add_action('wp_ajax_b2b_spec_delete', array(__CLASS__, 'handle_delete'));
        add_action('wp_ajax_b2b_spec_load_fields', array(__CLASS__, 'handle_load_fields'));
    }

    public static function handle_save() {
        check_ajax_referer(B2B_Procurement_Security::NONCE_ACTION, '_b2b_nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));

        $definition_id = intval($_POST['definition_id'] ?? 0);
        $raw_specs = isset($_POST['specs']) ? $_POST['specs'] : array();

        if (!$definition_id || !is_array($raw_specs)) {
            wp_send_json_error(array('message' => 'داده نامعتبر'));
        }

        foreach ($raw_specs as $spec_data) {
            $label = sanitize_text_field(wp_unslash($spec_data['label'] ?? ''));
            if (empty($label)) continue;

            $existing_id = intval($spec_data['spec_id'] ?? 0);
            $data = array(
                'definition_id' => $definition_id,
                'label'         => $label,
                'field_key'     => sanitize_key($spec_data['field_key'] ?? ''),
                'field_type'    => sanitize_key($spec_data['field_type'] ?? 'text'),
                'description'   => sanitize_text_field(wp_unslash($spec_data['description'] ?? '')),
                'placeholder'   => sanitize_text_field(wp_unslash($spec_data['placeholder'] ?? '')),
                'default_value' => sanitize_text_field(wp_unslash($spec_data['default_value'] ?? '')),
                'is_required'   => isset($spec_data['is_required']) ? 1 : 0,
                'is_searchable' => isset($spec_data['is_searchable']) ? 1 : 0,
                'is_filterable' => isset($spec_data['is_filterable']) ? 1 : 0,
                'sort_order'    => intval($spec_data['sort_order'] ?? 0),
                'is_active'     => isset($spec_data['is_active']) ? 1 : 0,
            );

            // Handle options for select/multi/radio
            if (isset($spec_data['options']) && is_array($spec_data['options'])) {
                $opts = array_filter(array_map('sanitize_text_field', array_map('wp_unslash', $spec_data['options'])));
                $data['options'] = $opts;
            }

            if ($existing_id) {
                Spec_DB::update($existing_id, $data);
            } else {
                Spec_DB::insert($data);
            }
        }

        wp_send_json_success(array('message' => 'مشخصات ذخیره شد'));
    }

    public static function handle_delete() {
        check_ajax_referer(B2B_Procurement_Security::NONCE_ACTION, '_b2b_nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));

        $spec_id = intval($_POST['spec_id'] ?? 0);
        if (!$spec_id) wp_send_json_error(array('message' => 'شناسه نامعتبر'));

        Spec_DB::delete($spec_id);
        wp_send_json_success(array('message' => 'فیلد حذف شد'));
    }

    public static function handle_load_fields() {
        check_ajax_referer(B2B_Procurement_Security::NONCE_ACTION, '_b2b_nonce');

        $def_id = intval($_POST['definition_id'] ?? 0);
        if (!$def_id) wp_send_json_error(array('message' => 'شناسه نامعتبر'));

        $specs = Spec_DB::get_by_definition($def_id);
        $values = SpecValue_DB::get_values(intval($_POST['product_id'] ?? 0));

        $fields = array();
        foreach ($specs as $spec) {
            if (!$spec->is_active) continue;
            $fields[] = array(
                'id'            => $spec->id,
                'label'         => $spec->label,
                'field_key'     => $spec->field_key,
                'field_type'    => $spec->field_type,
                'description'   => $spec->description,
                'placeholder'   => $spec->placeholder,
                'default_value' => $spec->default_value,
                'options'       => $spec->options,
                'is_required'   => $spec->is_required,
                'value'         => isset($values[$spec->field_key]) ? $values[$spec->field_key] : $spec->default_value,
            );
        }

        wp_send_json_success(array('fields' => $fields));
    }
}
