<?php
/**
 * Form - Reusable form engine.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Form
 *
 * Provides a reusable form framework with validation and AJAX support.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Form {

    /**
     * Form fields.
     *
     * @var array
     */
    private $fields = array();

    /**
     * Form ID.
     *
     * @var string
     */
    private $form_id = '';

    /**
     * Form method.
     *
     * @var string
     */
    private $method = 'post';

    /**
     * AJAX action.
     *
     * @var string
     */
    private $ajax_action = '';

    /**
     * Form data.
     *
     * @var array
     */
    private $data = array();

    /**
     * Validation errors.
     *
     * @var array
     */
    private $errors = array();

    /**
     * Constructor.
     *
     * @param string $form_id Form ID.
     * @param array $args Form arguments.
     */
    public function __construct($form_id, $args = array()) {
        $this->form_id = $form_id;
        $this->method = isset($args['method']) ? $args['method'] : 'post';
        $this->ajax_action = isset($args['ajax_action']) ? $args['ajax_action'] : '';
    }

    /**
     * Add a field to the form.
     *
     * @param array $field Field definition.
     */
    public function add_field($field) {
        $field = wp_parse_args($field, array(
            'type' => 'text',
            'name' => '',
            'label' => '',
            'description' => '',
            'placeholder' => '',
            'default' => '',
            'required' => false,
            'options' => array(),
            'class' => '',
            'wrapper_class' => '',
            'attrs' => array(),
            'rules' => array(),
        ));

        if (empty($field['name'])) {
            $field['name'] = $this->form_id . '_' . count($this->fields);
        }

        $this->fields[] = $field;
    }

    /**
     * Set form data.
     *
     * @param array $data Form data.
     */
    public function set_data($data) {
        $this->data = $data;
    }

    /**
     * Get field value.
     *
     * @param string $field_name Field name.
     * @return mixed Field value.
     */
    public function get_value($field_name) {
        if (isset($this->data[$field_name])) {
            return $this->data[$field_name];
        }

        foreach ($this->fields as $field) {
            if ($field['name'] === $field_name && isset($field['default'])) {
                return $field['default'];
            }
        }

        return '';
    }

    /**
     * Validate form data.
     *
     * @return bool Whether validation passed.
     */
    public function validate() {
        $this->errors = array();

        foreach ($this->fields as $field) {
            $value = $this->get_value($field['name']);
            $field_rules = isset($field['rules']) ? $field['rules'] : array();

            if ($field['required'] && empty($value) && $value !== '0') {
                $this->errors[$field['name']] = sprintf('فیلد %s الزامی است.', $field['label'] ?: $field['name']);
                continue;
            }

            if (!empty($value)) {
                foreach ($field_rules as $rule => $rule_value) {
                    $error = $this->validate_rule($field, $value, $rule, $rule_value);
                    if ($error) {
                        $this->errors[$field['name']] = $error;
                        break;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate a single rule.
     *
     * @param array $field Field definition.
     * @param mixed $value Field value.
     * @param string $rule Rule name.
     * @param mixed $rule_value Rule value.
     * @return string|false Error message or false if valid.
     */
    private function validate_rule($field, $value, $rule, $rule_value) {
        switch ($rule) {
            case 'email':
                if (!is_email($value)) {
                    return 'فیلد ' . ($field['label'] ?: $field['name']) . ' باید یک ایمیل معتبر باشد.';
                }
                break;
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return 'فیلد ' . ($field['label'] ?: $field['name']) . ' باید یک URL معتبر باشد.';
                }
                break;
            case 'min_length':
                if (strlen($value) < intval($rule_value)) {
                    return sprintf('فیلد %s باید حداقل %d کاراکتر باشد.', $field['label'] ?: $field['name'], $rule_value);
                }
                break;
            case 'max_length':
                if (strlen($value) > intval($rule_value)) {
                    return sprintf('فیلد %s نباید بیشتر از %d کاراکتر باشد.', $field['label'] ?: $field['name'], $rule_value);
                }
                break;
            case 'numeric':
                if (!is_numeric($value)) {
                    return 'فیلد ' . ($field['label'] ?: $field['name']) . ' باید عددی باشد.';
                }
                break;
            case 'in':
                $allowed = explode(',', $rule_value);
                if (!in_array($value, $allowed, true)) {
                    return 'مقدار وارد شده برای فیلد ' . ($field['label'] ?: $field['name']) . ' معتبر نیست.';
                }
                break;
            case 'regex':
                if (!preg_match($rule_value, $value)) {
                    return 'فرمت فیلد ' . ($field['label'] ?: $field['name']) . ' صحیح نیست.';
                }
                break;
        }

        return false;
    }

    /**
     * Get validation errors.
     *
     * @return array Errors array.
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get error for a specific field.
     *
     * @param string $field_name Field name.
     * @return string|false Error message or false.
     */
    public function get_error($field_name) {
        return isset($this->errors[$field_name]) ? $this->errors[$field_name] : false;
    }

    /**
     * Render the form.
     *
     * @param string $submit_label Submit button label.
     */
    public function render($submit_label = 'ذخیره') {
        $ajax_class = $this->ajax_action ? 'b2b-ajax-form' : '';

        echo '<form id="' . esc_attr($this->form_id) . '" method="' . esc_attr($this->method) . '" class="b2b-form ' . $ajax_class . '" ' . ($this->ajax_action ? 'data-action="' . esc_attr($this->ajax_action) . '"' : '') . '>';

        wp_nonce_field($this->ajax_action ?: $this->form_id);

        foreach ($this->fields as $field) {
            $this->render_field($field);
        }

        echo '<div class="b2b-form-actions">';
        B2B_Procurement_UI::button($submit_label, array(
            'tag' => 'button',
            'type' => 'submit',
            'class' => 'b2b-btn b2b-btn-primary b2b-form-submit',
            'variant' => 'primary',
        ));
        echo '</div>';

        echo '</form>';
    }

    /**
     * Render a single field.
     *
     * @param array $field Field definition.
     */
    private function render_field($field) {
        $value = $this->get_value($field['name']);
        $error = $this->get_error($field['name']);
        $wrapper_class = 'b2b-form-field';
        if ($error) {
            $wrapper_class .= ' b2b-field-error';
        }
        if ($field['wrapper_class']) {
            $wrapper_class .= ' ' . $field['wrapper_class'];
        }

        echo '<div class="' . esc_attr($wrapper_class) . '">';

        if ($field['label']) {
            echo '<label for="' . esc_attr($field['name']) . '" class="b2b-form-label">';
            echo esc_html($field['label']);
            if ($field['required']) {
                echo ' <span class="b2b-required">*</span>';
            }
            echo '</label>';
        }

        $field_config = array_merge($field, array('id' => $field['name']));
        B2B_Procurement_Settings::render_field($field_config, $value);

        if ($error) {
            echo '<p class="b2b-form-error">' . esc_html($error) . '</p>';
        }

        if ($field['description']) {
            echo '<p class="b2b-form-desc">' . wp_kses_post($field['description']) . '</p>';
        }

        echo '</div>';
    }

    /**
     * Handle AJAX form submission.
     *
     * @return array Response data.
     */
    public function handle_ajax_submit() {
        // Verify nonce.
        if (!isset($_POST['_b2b_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_b2b_nonce'])), $this->ajax_action)) {
            wp_send_json_error(array('message' => 'بررسی امنیتی ناموفق بود.'));
        }

        // Check capability.
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'شما اجازه انجام این عملیات را ندارید.'));
        }

        // Get form data.
        $this->set_data($_POST);

        // Validate.
        if (!$this->validate()) {
            wp_send_json_error(array(
                'message' => 'لطفاً خطاهای فرم را برطرف کنید.',
                'errors' => $this->errors,
            ));
        }

        // Process form (to be extended by child classes or callbacks).
        $result = apply_filters('b2b_form_process_' . $this->form_id, array('success' => true), $this->data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'فرم با موفقیت ذخیره شد.'));
    }
}
