<?php
defined('ABSPATH') || exit;

final class B2B_Persian_Calendar {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        if (!is_admin()) return;

        require_once __DIR__ . '/services/class-date-service.php';
        require_once __DIR__ . '/services/class-calendar-service.php';
        require_once __DIR__ . '/services/class-conversion-service.php';
        require_once __DIR__ . '/services/class-formatter.php';
        require_once __DIR__ . '/services/class-validator.php';
        require_once __DIR__ . '/services/class-ajax-service.php';
        require_once __DIR__ . '/localization/class-localization.php';
        require_once __DIR__ . '/ui/class-persian-datepicker.php';

        new B2B_PC_Ajax();
        B2B_PC_Localization::init();

        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_b2b_pc_convert', array('B2B_PC_Ajax', 'convert'));
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'b2b-') === false && strpos($hook, 'toplevel_page_b2b') === false) return;

        $base_url = plugin_dir_url(__FILE__);
        wp_enqueue_style('b2b-pc-picker', $base_url . 'assets/css/persian-datepicker.css', array(), '1.0.0');
        wp_enqueue_script('b2b-pc-picker', $base_url . 'assets/js/persian-datepicker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('b2b-pc-picker', 'b2bPC', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('b2b_persian_calendar'),
            'months'  => array('فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'),
            'weekdays' => array('شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'),
            'today' => 'امروز',
            'clear' => 'پاک کردن',
        ));
    }
}
