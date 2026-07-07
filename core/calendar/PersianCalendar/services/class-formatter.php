<?php
defined('ABSPATH') || exit;

class B2B_PC_Formatter {

    private static $formats = array(
        'short'     => 'Y/m/d',
        'medium'    => 'Y/m/d',
        'long'      => 'j F Y',
        'full'      => 'l j F Y',
        'datetime'  => 'Y/m/d H:i',
        'time'      => 'H:i',
    );

    public static function format($jy, $jm, $jd, $format = 'short', $options = array()) {
        $month_name = B2B_PC_Localization::get_month_name($jm);
        $day_name = B2B_PC_Localization::get_day_name(B2B_PC_Conversion::day_of_week($jy, $jm, $jd));
        $day = self::to_farsi_num($jd);
        $month = self::to_farsi_num($jm);
        $year = self::to_farsi_num($jy);

        $replacements = array(
            'l' => $day_name,
            'j' => $day,
            'F' => $month_name,
            'n' => $month,
            'Y' => $year,
            'y' => substr($year, -2),
            'H' => isset($options['hour']) ? self::to_farsi_num(str_pad($options['hour'], 2, '0', STR_PAD_LEFT)) : '00',
            'i' => isset($options['minute']) ? self::to_farsi_num(str_pad($options['minute'], 2, '0', STR_PAD_LEFT)) : '00',
            's' => isset($options['second']) ? self::to_farsi_num(str_pad($options['second'], 2, '0', STR_PAD_LEFT)) : '00',
        );

        if (isset(self::$formats[$format])) {
            $format = self::$formats[$format];
        }

        return strtr($format, $replacements);
    }

    public static function format_timestamp($timestamp, $format = 'short') {
        list($y, $m, $d) = B2B_PC_Conversion::timestamp_to_jalali($timestamp);
        $dt = new DateTime('@' . $timestamp, new DateTimeZone('Asia/Tehran'));
        return self::format($y, $m, $d, $format, array(
            'hour' => intval($dt->format('H')),
            'minute' => intval($dt->format('i')),
        ));
    }

    public static function format_gregorian($date_string, $format = 'short') {
        list($y, $m, $d) = B2B_PC_Conversion::gregorian_to_jalali(
            intval(substr($date_string, 0, 4)),
            intval(substr($date_string, 5, 2)),
            intval(substr($date_string, 8, 2))
        );
        return self::format($y, $m, $d, $format);
    }

    public static function to_farsi_num($num) {
        $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        return str_replace(range(0, 9), $persian, $num);
    }

    public static function to_latin_num($num) {
        $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $latin = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        return str_replace($persian, $latin, $num);
    }
}
