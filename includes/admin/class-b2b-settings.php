<?php
/**
 * Settings - Reusable settings engine.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Settings
 *
 * Provides a reusable settings framework for admin pages.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Settings {

    /**
     * Registered settings groups.
     *
     * @var array
     */
    private static $groups = array();

    /**
     * Register a settings group.
     *
     * @param string $id Group ID.
     * @param array $args Group arguments.
     */
    public static function register_group($id, $args = array()) {
        $defaults = array(
            'title' => '',
            'capability' => 'manage_woocommerce',
            'option_prefix' => 'b2b_',
        );
        $args = wp_parse_args($args, $defaults);
        self::$groups[$id] = $args;
    }

    /**
     * Get a setting value.
     *
     * @param string $option Option name (without prefix).
     * @param mixed $default Default value.
     * @param string $group Group ID.
     * @return mixed Option value.
     */
    public static function get($option, $default = '', $group = 'general') {
        $option_name = self::get_option_name($option, $group);
        $value = get_option($option_name, $default);
        return apply_filters('b2b_setting_get', $value, $option, $group);
    }

    /**
     * Update a setting value.
     *
     * @param string $option Option name (without prefix).
     * @param mixed $value Option value.
     * @param string $group Group ID.
     * @return bool Update result.
     */
    public static function update($option, $value, $group = 'general') {
        $option_name = self::get_option_name($option, $group);
        $value = apply_filters('b2b_setting_update', $value, $option, $group);
        $result = update_option($option_name, $value);

        if ($result) {
            do_action('b2b_setting_updated', $option, $value, $group);
        }

        return $result;
    }

    /**
     * Delete a setting value.
     *
     * @param string $option Option name (without prefix).
     * @param string $group Group ID.
     * @return bool Delete result.
     */
    public static function delete($option, $group = 'general') {
        $option_name = self::get_option_name($option, $group);
        return delete_option($option_name);
    }

    /**
     * Get full option name with prefix.
     *
     * @param string $option Option name.
     * @param string $group Group ID.
     * @return string Full option name.
     */
    private static function get_option_name($option, $group = 'general') {
        $prefix = isset(self::$groups[$group]) ? self::$groups[$group]['option_prefix'] : 'b2b_';
        return $prefix . $option;
    }

    /**
     * Render a settings field.
     *
     * @param array $field Field definition.
     * @param mixed $value Current value.
     */
    public static function render_field($field, $value = '') {
        $field = wp_parse_args($field, array(
            'type' => 'text',
            'id' => '',
            'name' => '',
            'label' => '',
            'description' => '',
            'placeholder' => '',
            'default' => '',
            'options' => array(),
            'class' => '',
            'attrs' => array(),
            'repeatable' => false,
            'min' => '',
            'max' => '',
            'step' => '',
        ));

        $field_id = $field['id'];
        $field_name = $field['name'] ?: $field['id'];

        if ($value === '' && $field['default'] !== '') {
            $value = $field['default'];
        }

        echo '<div class="b2b-field b2b-field-' . esc_attr($field['type']) . '">';
        if ($field['label']) {
            echo '<label for="' . esc_attr($field_id) . '" class="b2b-field-label">' . esc_html($field['label']) . '</label>';
        }

        switch ($field['type']) {
            case 'text':
                self::render_text($field, $value);
                break;
            case 'textarea':
                self::render_textarea($field, $value);
                break;
            case 'number':
                self::render_number($field, $value);
                break;
            case 'email':
                self::render_email($field, $value);
                break;
            case 'url':
                self::render_url($field, $value);
                break;
            case 'select':
                self::render_select($field, $value);
                break;
            case 'checkbox':
                self::render_checkbox($field, $value);
                break;
            case 'switch':
                self::render_switch($field, $value);
                break;
            case 'radio':
                self::render_radio($field, $value);
                break;
            case 'color':
                self::render_color($field, $value);
                break;
            case 'media':
                self::render_media($field, $value);
                break;
            case 'hidden':
                self::render_hidden($field, $value);
                break;
            default:
                do_action('b2b_render_field_' . $field['type'], $field, $value);
                break;
        }

        if ($field['description']) {
            echo '<p class="b2b-field-desc">' . wp_kses_post($field['description']) . '</p>';
        }
        echo '</div>';
    }

    /**
     * Render text input.
     */
    private static function render_text($field, $value) {
        $attrs = self::build_attrs($field);
        echo '<input type="text" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" class="b2b-input ' . esc_attr($field['class']) . '" placeholder="' . esc_attr($field['placeholder']) . '"' . $attrs . ' />';
    }

    /**
     * Render textarea.
     */
    private static function render_textarea($field, $value) {
        $attrs = self::build_attrs($field);
        echo '<textarea id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="b2b-textarea ' . esc_attr($field['class']) . '" placeholder="' . esc_attr($field['placeholder']) . '" rows="4"' . $attrs . '>' . esc_textarea($value) . '</textarea>';
    }

    /**
     * Render number input.
     */
    private static function render_number($field, $value) {
        $attrs = self::build_attrs($field);
        $min = $field['min'] !== '' ? ' min="' . esc_attr($field['min']) . '"' : '';
        $max = $field['max'] !== '' ? ' max="' . esc_attr($field['max']) . '"' : '';
        $step = $field['step'] !== '' ? ' step="' . esc_attr($field['step']) . '"' : '';
        echo '<input type="number" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" class="b2b-input b2b-input-number ' . esc_attr($field['class']) . '"' . $min . $max . $step . $attrs . ' />';
    }

    /**
     * Render email input.
     */
    private static function render_email($field, $value) {
        $attrs = self::build_attrs($field);
        echo '<input type="email" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" class="b2b-input ' . esc_attr($field['class']) . '" placeholder="' . esc_attr($field['placeholder']) . '"' . $attrs . ' />';
    }

    /**
     * Render URL input.
     */
    private static function render_url($field, $value) {
        $attrs = self::build_attrs($field);
        echo '<input type="url" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_url($value) . '" class="b2b-input ' . esc_attr($field['class']) . '" placeholder="' . esc_attr($field['placeholder']) . '"' . $attrs . ' />';
    }

    /**
     * Render select dropdown.
     */
    private static function render_select($field, $value) {
        $attrs = self::build_attrs($field);
        echo '<select id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="b2b-select ' . esc_attr($field['class']) . '"' . $attrs . '>';
        foreach ($field['options'] as $opt_value => $opt_label) {
            $selected = selected($value, $opt_value, false);
            echo '<option value="' . esc_attr($opt_value) . '"' . $selected . '>' . esc_html($opt_label) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Render checkbox.
     */
    private static function render_checkbox($field, $value) {
        $attrs = self::build_attrs($field);
        $checked = checked($value, '1', false);
        echo '<label class="b2b-checkbox-label">';
        echo '<input type="checkbox" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="1" class="b2b-checkbox ' . esc_attr($field['class']) . '"' . $checked . $attrs . ' />';
        if ($field['label']) {
            echo '<span class="b2b-checkbox-text">' . esc_html($field['label']) . '</span>';
        }
        echo '</label>';
    }

    /**
     * Render switch toggle.
     */
    private static function render_switch($field, $value) {
        $attrs = self::build_attrs($field);
        $checked = checked($value, '1', false);
        echo '<label class="b2b-switch">';
        echo '<input type="checkbox" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="1" class="b2b-switch-input ' . esc_attr($field['class']) . '"' . $checked . $attrs . ' />';
        echo '<span class="b2b-switch-slider"></span>';
        echo '</label>';
    }

    /**
     * Render radio buttons.
     */
    private static function render_radio($field, $value) {
        $attrs = self::build_attrs($field);
        echo '<div class="b2b-radio-group">';
        foreach ($field['options'] as $opt_value => $opt_label) {
            $checked = checked($value, $opt_value, false);
            echo '<label class="b2b-radio-label">';
            echo '<input type="radio" name="' . esc_attr($field['name']) . '" value="' . esc_attr($opt_value) . '" class="b2b-radio ' . esc_attr($field['class']) . '"' . $checked . $attrs . ' />';
            echo '<span class="b2b-radio-text">' . esc_html($opt_label) . '</span>';
            echo '</label>';
        }
        echo '</div>';
    }

    /**
     * Render color picker.
     */
    private static function render_color($field, $value) {
        $attrs = self::build_attrs($field);
        echo '<div class="b2b-color-picker-wrap">';
        echo '<input type="text" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" class="b2b-color-picker ' . esc_attr($field['class']) . '"' . $attrs . ' />';
        echo '</div>';
    }

    /**
     * Render media uploader.
     */
    private static function render_media($field, $value) {
        $attrs = self::build_attrs($field);
        $preview = $value ? '<img src="' . esc_url($value) . '" />' : '';
        echo '<div class="b2b-media-wrap">';
        echo '<input type="hidden" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_url($value) . '" class="b2b-media-input ' . esc_attr($field['class']) . '"' . $attrs . ' />';
        echo '<div class="b2b-media-preview">' . $preview . '</div>';
        echo '<button type="button" class="b2b-btn b2b-btn-secondary b2b-media-upload">انتخاب فایل</button>';
        echo '<button type="button" class="b2b-btn b2b-btn-link b2b-media-remove">حذف</button>';
        echo '</div>';
    }

    /**
     * Render hidden input.
     */
    private static function render_hidden($field, $value) {
        $attrs = self::build_attrs($field);
        echo '<input type="hidden" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '"' . $attrs . ' />';
    }

    /**
     * Render section divider.
     *
     * @param array $field Section field definition.
     */
    public static function render_section($field) {
        echo '<div class="b2b-settings-section">';
        if (!empty($field['title'])) {
            echo '<h3 class="b2b-settings-section-title">' . esc_html($field['title']) . '</h3>';
        }
        if (!empty($field['description'])) {
            echo '<p class="b2b-settings-section-desc">' . wp_kses_post($field['description']) . '</p>';
        }
        echo '<hr class="b2b-settings-section-divider" />';
        echo '</div>';
    }

    /**
     * Build HTML attributes string.
     *
     * @param array $field Field definition.
     * @return string Attributes string.
     */
    private static function build_attrs($field) {
        $attrs = '';
        if (!empty($field['attrs'])) {
            foreach ($field['attrs'] as $key => $val) {
                $attrs .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
            }
        }
        return $attrs;
    }

    /**
     * Render settings form.
     *
     * @param string $group Settings group ID.
     * @param array $sections Settings sections with fields.
     */
    public static function render_form($group, $sections) {
        $group_data = isset(self::$groups[$group]) ? self::$groups[$group] : array();

        if (!empty($_POST['b2b_save_settings'])) {
            check_admin_referer('b2b_settings_' . $group);
            self::save_settings($group, $sections, $_POST);
        }

        echo '<form method="post">';
        wp_nonce_field('b2b_settings_' . $group);

        foreach ($sections as $section) {
            if (isset($section['type']) && $section['type'] === 'section') {
                self::render_section($section);
                continue;
            }

            $value = self::get($section['name'], isset($section['default']) ? $section['default'] : '', $group);
            self::render_field($section, $value);
        }

        submit_button('ذخیره تنظیمات', 'primary', 'b2b_save_settings', false);
        echo '</form>';
    }

    /**
     * Save settings from form data.
     *
     * @param string $group Settings group ID.
     * @param array $sections Settings sections.
     * @param array $data Form data.
     */
    private static function save_settings($group, $sections, $data) {
        foreach ($sections as $section) {
            if (isset($section['type']) && $section['type'] === 'section') {
                continue;
            }
            if (empty($section['name'])) {
                continue;
            }

            $field_name = $section['name'];
            $field_type = isset($section['type']) ? $section['type'] : 'text';

            if ($field_type === 'checkbox' || $field_type === 'switch') {
                $value = isset($data[$field_name]) ? '1' : '0';
            } else {
                $value = isset($data[$field_name]) ? sanitize_text_field(wp_unslash($data[$field_name])) : '';
            }

            self::update($field_name, $value, $group);
        }

        B2B_Procurement_Notices::add('تنظیمات با موفقیت ذخیره شد.', 'success');
    }
}
