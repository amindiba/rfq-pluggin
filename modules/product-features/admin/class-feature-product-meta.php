<?php
namespace B2B\ProductFeatures\Admin;

use B2B\ProductFeatures\Database\Feature_DB;
use B2B\ProductFeatures\Database\FeatureValue_DB;

defined('ABSPATH') || exit;

class Feature_Product_Meta {

    public static function init() {
        $instance = new self();
        add_action('add_meta_boxes', array($instance, 'register'));
        add_action('save_post_product', array($instance, 'save'), 30, 2);
    }

    public function register() {
        add_meta_box(
            'b2b_product_features',
            'ویژگی‌های محصول (Product Features)',
            array($this, 'render'),
            'product',
            'normal',
            'high'
        );
    }

    public function render($post) {
        $features = Feature_DB::get_active_all();
        if (empty($features)) {
            echo '<p class="description">هیچ ویژگی فعالی تعریف نشده است. از منوی «ویژگی‌های محصولات» ابتدا ویژگی تعریف کنید.</p>';
            return;
        }

        wp_nonce_field('b2b_pf_values_save', 'b2b_pf_values_nonce');
        $values = FeatureValue_DB::get_values($post->ID);

        // Group features
        $grouped = array();
        foreach ($features as $feat) {
            $g = $feat->group_name ?: 'عمومی';
            $grouped[$g][] = $feat;
        }
        ?>
        <div id="b2b-pf-product-fields">
            <p class="description" style="margin-bottom:12px;">ویژگی‌های تعریف‌شده در سامانه را برای این محصول تکمیل کنید.</p>
            <?php foreach ($grouped as $group_name => $gfeatures) : ?>
                <div style="margin-bottom:16px;">
                    <h4 style="font-size:13px;font-weight:700;color:#7B2CBF;margin:0 0 8px;padding-bottom:4px;border-bottom:1px solid #ECE6F8;"><?php echo esc_html($group_name); ?></h4>
                    <?php foreach ($gfeatures as $feat) :
                        $val = isset($values[$feat->slug]) ? $values[$feat->slug] : '';
                    ?>
                        <div style="margin-bottom:10px;">
                            <label style="font-weight:600;font-size:13px;color:#1F2937;display:block;margin-bottom:3px;">
                                <?php echo esc_html($feat->name); ?>
                                <?php if ($feat->unit) echo '<span style="color:#9CA3AF;font-weight:400;font-size:12px;">(' . esc_html($feat->unit) . ')</span>'; ?>
                                <?php if ($feat->is_required) echo '<span style="color:#EF4444;"> *</span>'; ?>
                            </label>
                            <?php echo $this->render_field($feat, $val); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_field($feat, $value) {
        $name = 'b2b_features[' . esc_attr($feat->slug) . ']';
        $req  = $feat->is_required ? ' required' : '';
        $ph   = '';

        switch ($feat->feature_type) {
            case 'textarea':
                return '<textarea name="' . $name . '" class="large-text" rows="2"' . $req . '>' . esc_textarea($value) . '</textarea>';
            case 'number':
            case 'decimal':
                $step = $feat->feature_type === 'decimal' ? ' step="0.01"' : '';
                return '<input type="number" name="' . $name . '" class="regular-text" value="' . esc_attr($value) . '"' . $step . $req . ' />';
            case 'select':
                $html = '<select name="' . $name . '" class="b2b-select" style="width:100%;"' . $req . '><option value="">— انتخاب —</option>';
                foreach ($feat->options as $opt) {
                    $html .= '<option value="' . esc_attr($opt) . '" ' . selected($value, $opt, false) . '>' . esc_html($opt) . '</option>';
                }
                $html .= '</select>';
                return $html;
            case 'checkbox':
                return '<label style="display:flex;align-items:center;gap:6px;font-size:13px;"><input type="checkbox" name="' . $name . '" value="1" ' . checked($value, '1', false) . $req . '/> فعال</label>';
            case 'radio':
                $html = '<div style="display:flex;flex-wrap:wrap;gap:12px;">';
                foreach ($feat->options as $opt) {
                    $html .= '<label style="display:flex;align-items:center;gap:4px;font-size:13px;"><input type="radio" name="' . $name . '" value="' . esc_attr($opt) . '" ' . checked($value, $opt, false) . $req . '/> ' . esc_html($opt) . '</label>';
                }
                $html .= '</div>';
                return $html;
            case 'date':
                return '<input type="date" name="' . $name . '" class="regular-text" value="' . esc_attr($value) . '"' . $req . ' />';
            case 'url':
                return '<input type="url" name="' . $name . '" class="regular-text" value="' . esc_url($value) . '"' . $req . ' />';
            case 'email':
                return '<input type="email" name="' . $name . '" class="regular-text" value="' . esc_attr($value) . '"' . $req . ' />';
            case 'phone':
                return '<input type="tel" name="' . $name . '" class="regular-text" value="' . esc_attr($value) . '"' . $req . ' />';
            case 'color':
                return '<input type="color" name="' . $name . '" value="' . esc_attr($value ?: '#7B2CBF') . '"' . $req . ' style="width:60px;height:36px;" />';
            case 'switch':
                $checked = $value === '1';
                return '<label class="b2b-switch-label"><input type="checkbox" name="' . $name . '" value="1" ' . ($checked ? 'checked' : '') . $req . ' class="b2b-switch-input" /><span class="b2b-switch-slider"></span></label>';
            default:
                return '<input type="text" name="' . $name . '" class="regular-text" value="' . esc_attr($value) . '"' . $req . ' />';
        }
    }

    public function save($post_id, $post) {
        if (!isset($_POST['b2b_pf_values_nonce']) || !wp_verify_nonce($_POST['b2b_pf_values_nonce'], 'b2b_pf_values_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_product', $post_id)) return;

        $features = Feature_DB::get_active_all();
        if (empty($features)) return;

        $raw = isset($_POST['b2b_features']) ? $_POST['b2b_features'] : array();
        FeatureValue_DB::save_values($post_id, $features, $raw);
    }
}
