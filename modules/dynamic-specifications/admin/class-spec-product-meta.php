<?php
namespace B2B\DynamicSpecs\Admin;

use B2B\DynamicSpecs\Database\Spec_DB;
use B2B\DynamicSpecs\Database\SpecValue_DB;
use B2B\DynamicSpecs\FieldType\Registry;

defined('ABSPATH') || exit;

class Spec_Product_Meta {

    private $meta_key_def = '_b2b_product_definition_id';

    public static function init() {
        $instance = new self();
        add_action('add_meta_boxes', array($instance, 'register'));
        add_action('save_post_product', array($instance, 'save'), 20, 2);
    }

    public function register() {
        add_meta_box(
            'b2b_dynamic_specs',
            'مشخصات فنی (Dynamic Specifications)',
            array($this, 'render'),
            'product',
            'normal',
            'high'
        );
    }

    public function render($post) {
        $def_id = get_post_meta($post->ID, $this->meta_key_def, true);
        $def_id = intval($def_id);

        if (!$def_id) {
            echo '<p class="description">ابتدا از بخش «تعریف محصولات» یک Product Definition برای این محصول انتخاب کنید. مشخصات فنی پس از انتخاب تعریف نمایش داده خواهند شد.</p>';
            return;
        }

        $specs = Spec_DB::get_by_definition($def_id);
        if (empty($specs)) {
            echo '<p class="description">هیچ مشخصات فنی برای این تعریف تعریف نشده است. از بخش «مشخصات فنی» در منوی مدیریت اقدام کنید.</p>';
            return;
        }

        $values = SpecValue_DB::get_values($post->ID);
        wp_nonce_field('b2b_spec_values_save', 'b2b_spec_values_nonce');
        ?>
        <div id="b2b-spec-product-fields" class="b2b-spec-product-fields">
            <?php foreach ($specs as $spec) : ?>
                <?php if (!$spec->is_active) continue; ?>
                <div class="b2b-spec-product-field" style="margin-bottom:12px;">
                    <label style="font-weight:600;font-size:13px;color:#1F2937;display:block;margin-bottom:4px;">
                        <?php echo esc_html($spec->label); ?>
                        <?php if ($spec->is_required) : ?><span style="color:#EF4444;">*</span><?php endif; ?>
                    </label>
                    <?php if ($spec->description) : ?>
                        <p class="description" style="margin-top:0;margin-bottom:4px;"><?php echo esc_html($spec->description); ?></p>
                    <?php endif; ?>
                    <?php $val = isset($values[$spec->field_key]) ? $values[$spec->field_key] : ''; ?>
                    <?php echo $this->render_field($spec, $val); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_field($spec, $value) {
        $key = 'b2b_spec_values[' . esc_attr($spec->field_key) . ']';
        $desc_attr = $spec->placeholder ? ' placeholder="' . esc_attr($spec->placeholder) . '"' : '';
        $req_attr = $spec->is_required ? ' required' : '';

        switch ($spec->field_type) {
            case 'textarea':
            case 'wysiwyg':
                return '<textarea name="' . $key . '" class="large-text" rows="3"' . $desc_attr . $req_attr . '>' . esc_textarea($value) . '</textarea>';

            case 'number':
                return '<input type="number" name="' . $key . '" class="regular-text" value="' . esc_attr($value) . '"' . $desc_attr . $req_attr . ' />';

            case 'decimal':
                return '<input type="number" name="' . $key . '" class="regular-text" step="0.01" value="' . esc_attr($value) . '"' . $desc_attr . $req_attr . ' />';

            case 'select':
                $html = '<select name="' . $key . '" class="b2b-select" style="width:100%;"' . $req_attr . '><option value="">— انتخاب —</option>';
                if (!empty($spec->options)) {
                    foreach ($spec->options as $opt) {
                        $html .= '<option value="' . esc_attr($opt) . '" ' . selected($value, $opt, false) . '>' . esc_html($opt) . '</option>';
                    }
                }
                $html .= '</select>';
                return $html;

            case 'radio':
                $html = '<div style="display:flex;flex-wrap:wrap;gap:12px;">';
                if (!empty($spec->options)) {
                    foreach ($spec->options as $opt) {
                        $html .= '<label style="display:flex;align-items:center;gap:4px;font-size:13px;"><input type="radio" name="' . $key . '" value="' . esc_attr($opt) . '" ' . checked($value, $opt, false) . $req_attr . '/> ' . esc_html($opt) . '</label>';
                    }
                }
                $html .= '</div>';
                return $html;

            case 'checkbox':
                return '<label style="display:flex;align-items:center;gap:6px;font-size:13px;"><input type="checkbox" name="' . $key . '" value="1" ' . checked($value, '1', false) . $req_attr . '/> فعال</label>';

            case 'switch':
                $checked = $value === '1' || $value === 'on';
                return '<label class="b2b-switch-label"><input type="checkbox" name="' . $key . '" value="1" ' . ($checked ? 'checked' : '') . $req_attr . ' class="b2b-switch-input" /><span class="b2b-switch-slider"></span></label>';

            case 'date':
                return '<input type="date" name="' . $key . '" class="regular-text" value="' . esc_attr($value) . '"' . $req_attr . ' />';

            case 'time':
                return '<input type="time" name="' . $key . '" class="regular-text" value="' . esc_attr($value) . '"' . $req_attr . ' />';

            case 'datetime':
                return '<input type="datetime-local" name="' . $key . '" class="regular-text" value="' . esc_attr($value) . '"' . $req_attr . ' />';

            case 'url':
                return '<input type="url" name="' . $key . '" class="regular-text" value="' . esc_url($value) . '"' . $desc_attr . $req_attr . ' />';

            case 'email':
                return '<input type="email" name="' . $key . '" class="regular-text" value="' . esc_attr($value) . '"' . $desc_attr . $req_attr . ' />';

            case 'phone':
                return '<input type="tel" name="' . $key . '" class="regular-text" value="' . esc_attr($value) . '"' . $desc_attr . $req_attr . ' />';

            case 'color':
                return '<input type="color" name="' . $key . '" value="' . esc_attr($value ?: '#7B2CBF') . '"' . $req_attr . ' style="width:60px;height:36px;" />';

            case 'range':
                $min = $spec->default_value ?: '0';
                return '<input type="range" name="' . $key . '" min="0" max="100" value="' . esc_attr($value ?: $min) . '"' . $req_attr . ' style="width:100%;" />';

            case 'image':
                return '<input type="number" name="' . $key . '" class="regular-text" value="' . esc_attr($value) . '" placeholder="Attachment ID" ' . $req_attr . ' />';

            case 'file':
                return '<input type="number" name="' . $key . '" class="regular-text" value="' . esc_attr($value) . '" placeholder="Attachment ID" ' . $req_attr . ' />';

            default:
                return '<input type="text" name="' . $key . '" class="regular-text" value="' . esc_attr($value) . '"' . $desc_attr . $req_attr . ' />';
        }
    }

    public function save($post_id, $post) {
        if (!isset($_POST['b2b_spec_values_nonce']) || !wp_verify_nonce($_POST['b2b_spec_values_nonce'], 'b2b_spec_values_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_product', $post_id)) return;

        $def_id = get_post_meta($post_id, $this->meta_key_def, true);
        if (!$def_id) return;

        $specs = Spec_DB::get_by_definition(intval($def_id));
        if (empty($specs)) return;

        $raw_values = isset($_POST['b2b_spec_values']) ? $_POST['b2b_spec_values'] : array();
        SpecValue_DB::save_values($post_id, $specs, $raw_values);
    }
}
