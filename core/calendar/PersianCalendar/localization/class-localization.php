<?php
defined('ABSPATH') || exit;

class B2B_PC_Localization {

    private static $months = array(
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
    );

    private static $weekdays = array(
        'شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'
    );

    private static $short_weekdays = array(
        'ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'
    );

    public static function init() {}

    public static function get_month_name($month) {
        return isset(self::$months[$month - 1]) ? self::$months[$month - 1] : '';
    }

    public static function get_months() {
        return self::$months;
    }

    public static function get_day_name($day_index) {
        return isset(self::$weekdays[$day_index]) ? self::$weekdays[$day_index] : '';
    }

    public static function get_weekdays() {
        return self::$short_weekdays;
    }
}
