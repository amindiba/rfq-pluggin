<?php
defined('ABSPATH') || exit;

class B2B_PC_Validator {

    public static function is_valid_date($jy, $jm, $jd) {
        if ($jy < 1 || $jy > 9999) return false;
        if ($jm < 1 || $jm > 12) return false;
        $max_day = B2B_PC_Conversion::days_in_jalali_month($jm);
        if ($jd < 1 || $jd > $max_day) return false;
        return true;
    }

    public static function parse($input) {
        $input = trim($input);
        $input = B2B_PC_Formatter::to_latin_num($input);

        // Format: YYYY/MM/DD or YYYY-MM-DD
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $input, $m)) {
            return array('year' => intval($m[1]), 'month' => intval($m[2]), 'day' => intval($m[3]));
        }

        // Format: YYYY/MM/DD HH:ii or YYYY/MM/DD HH:ii:ss
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})\s+(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $input, $m)) {
            return array('year' => intval($m[1]), 'month' => intval($m[2]), 'day' => intval($m[3]),
                'hour' => intval($m[4]), 'minute' => intval($m[5]), 'second' => isset($m[6]) ? intval($m[6]) : 0);
        }

        return false;
    }

    public static function validate_range($y, $m, $d, $min = null, $max = null) {
        if (!self::is_valid_date($y, $m, $d)) return false;
        if ($min) {
            $min_d = self::parse($min);
            if ($min_d && B2B_PC_Date::compare($y, $m, $d, $min_d['year'], $min_d['month'], $min_d['day']) < 0) return false;
        }
        if ($max) {
            $max_d = self::parse($max);
            if ($max_d && B2B_PC_Date::compare($y, $m, $d, $max_d['year'], $max_d['month'], $max_d['day']) > 0) return false;
        }
        return true;
    }
}
