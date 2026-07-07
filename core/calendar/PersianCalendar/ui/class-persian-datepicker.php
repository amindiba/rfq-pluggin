<?php
defined('ABSPATH') || exit;

class B2B_PC_Persian_Datepicker {

    public static function render($name, $value = '', $options = array()) {
        $id = $options['id'] ?? $name;
        $placeholder = $options['placeholder'] ?? 'انتخاب تاریخ';
        $class = $options['class'] ?? 'b2b-pc-input';
        $required = !empty($options['required']) ? ' required' : '';
        $disabled = !empty($options['disabled']) ? ' disabled' : '';
        $min = isset($options['min']) ? esc_attr($options['min']) : '';
        $max = isset($options['max']) ? esc_attr($options['max']) : '';
        $format = $options['format'] ?? 'Y/m/d';
        $type = $options['type'] ?? 'date';
        $has_time = ($type === 'datetime' || $type === 'time');

        // Convert value to display
        $display_value = '';
        if ($value) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                $display_value = B2B_PC_Formatter::format_gregorian($value, str_replace('H:i', 'H:i', $format));
            } elseif (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}/', $value)) {
                $display_value = B2B_PC_Formatter::to_latin_num($value);
            } else {
                $display_value = $value;
            }
        }

        echo '<div class="b2b-pc-wrapper" data-type="' . esc_attr($type) . '" data-format="' . esc_attr($format) . '" data-min="' . $min . '" data-max="' . $max . '">';
        echo '<div style="position:relative;">';
        echo '<input type="text" name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" class="' . esc_attr($class) . ' b2b-pc-field" value="' . esc_attr($display_value) . '" placeholder="' . esc_attr($placeholder) . '"' . $required . $disabled . ' autocomplete="off" readonly />';
        echo '<span class="b2b-pc-icon" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9CA3AF;font-size:18px;">&#128197;</span>';
        echo '</div>';
        echo '</div>';
    }

    public static function render_time($name, $value = '', $options = array()) {
        $options['type'] = 'time';
        $options['format'] = 'H:i';
        $options['placeholder'] = $options['placeholder'] ?? 'انتخاب زمان';
        self::render($name, $value, $options);
    }

    public static function render_datetime($name, $value = '', $options = array()) {
        $options['type'] = 'datetime';
        $options['format'] = 'Y/m/d H:i';
        $options['placeholder'] = $options['placeholder'] ?? 'انتخاب تاریخ و زمان';
        self::render($name, $value, $options);
    }
}
