<?php
defined('ABSPATH') || exit;

class B2B_PC_Ajax {

    public function __construct() {
        add_action('wp_ajax_b2b_pc_convert', array($this, 'convert'));
        add_action('wp_ajax_b2b_pc_get_month', array($this, 'get_month'));
        add_action('wp_ajax_b2b_pc_now', array($this, 'now'));
    }

    public function convert() {
        check_ajax_referer('b2b_persian_calendar', 'nonce');
        $direction = sanitize_text_field(wp_unslash($_POST['direction'] ?? ''));
        $value = sanitize_text_field(wp_unslash($_POST['value'] ?? ''));

        if ($direction === 'shamsi_to_gregorian') {
            $parsed = B2B_PC_Validator::parse($value);
            if (!$parsed) wp_send_json_error(array('message' => 'تاریخ نامعتبر'));
            $gregorian = B2B_PC_Date::to_gregorian($parsed['year'], $parsed['month'], $parsed['day']);
            wp_send_json_success(array('result' => $gregorian));
        } elseif ($direction === 'gregorian_to_shamsi') {
            $parts = explode('-', $value);
            if (count($parts) !== 3) wp_send_json_error(array('message' => 'تاریخ نامعتبر'));
            list($y, $m, $d) = B2B_PC_Conversion::gregorian_to_gregorian(intval($parts[0]), intval($parts[1]), intval($parts[2]));
            $jalali = B2B_PC_Formatter::format($y, $m, $d, 'short');
            wp_send_json_success(array('result' => $jalali));
        }

        wp_send_json_error(array('message' => 'جهت تبدیل نامعتبر'));
    }

    public function get_month() {
        check_ajax_referer('b2b_persian_calendar', 'nonce');
        $jy = intval($_POST['year'] ?? 0);
        $jm = intval($_POST['month'] ?? 0);

        if (!$jy || !$jm) {
            list($jy, $jm, $jd) = B2B_PC_Conversion::now_jalali();
        }

        $grid = B2B_PC_Calendar::get_calendar_grid($jy, $jm);
        wp_send_json_success(array(
            'year'  => $jy,
            'month' => $jm,
            'grid'  => $grid,
            'month_name' => B2B_PC_Localization::get_month_name($jm),
        ));
    }

    public function now() {
        check_ajax_referer('b2b_persian_calendar', 'nonce');
        wp_send_json_success(B2B_PC_Date::now());
    }
}
