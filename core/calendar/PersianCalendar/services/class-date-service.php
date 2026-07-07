<?php
defined('ABSPATH') || exit;

class B2B_PC_Date {

    public static function today() {
        list($y, $m, $d) = B2B_PC_Conversion::now_jalali();
        return array('year' => $y, 'month' => $m, 'day' => $d);
    }

    public static function now() {
        $dt = new DateTime('now', new DateTimeZone('Asia/Tehran'));
        list($y, $m, $d) = B2B_PC_Conversion::now_jalali();
        return array(
            'year' => $y, 'month' => $m, 'day' => $d,
            'hour' => intval($dt->format('H')),
            'minute' => intval($dt->format('i')),
            'second' => intval($dt->format('s')),
        );
    }

    public static function to_jalali($date_string) {
        if (empty($date_string)) return null;
        $dt = new DateTime($date_string, new DateTimeZone('Asia/Tehran'));
        if (!$dt) return null;
        list($y, $m, $d) = B2B_PC_Conversion::gregorian_to_jalali(
            intval($dt->format('Y')), intval($dt->format('n')), intval($dt->format('j'))
        );
        return array('year' => $y, 'month' => $m, 'day' => $d);
    }

    public static function to_gregorian($jy, $jm, $jd) {
        list($gy, $gm, $gd) = B2B_PC_Conversion::jalali_to_gregorian($jy, $jm, $gd);
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    public static function is_weekend($jy, $jm, $jd) {
        $dow = B2B_PC_Conversion::day_of_week($jy, $jm, $jd);
        return ($dow === 5 || $dow === 6);
    }

    public static function is_holiday($jy, $jm, $jd) {
        $holidays = array(
            array(1, 1), array(1, 2), array(1, 3), array(1, 4),
            array(1, 12), array(1, 13),
            array(3, 14), array(3, 15),
            array(11, 22),
        );
        foreach ($holidays as $h) {
            if ($jm === $h[0] && $jd === $h[1]) return true;
        }
        return false;
    }

    public static function is_disabled($jy, $jm, $jd, $options = array()) {
        if (isset($options['disable_weekends']) && $options['disable_weekends'] && self::is_weekend($jy, $jm, $jd)) return true;
        if (isset($options['disable_holidays']) && $options['disable_holidays'] && self::is_holiday($jy, $jm, $jd)) return true;
        if (isset($options['min_date']) && $options['min_date']) {
            $min = self::to_jalali($options['min_date']);
            if ($min && self::compare($jy, $jm, $jd, $min['year'], $min['month'], $min['day']) < 0) return true;
        }
        if (isset($options['max_date']) && $options['max_date']) {
            $max = self::to_jalali($options['max_date']);
            if ($max && self::compare($jy, $jm, $jd, $max['year'], $max['month'], $max['day']) > 0) return true;
        }
        return false;
    }

    public static function compare($y1, $m1, $d1, $y2, $m2, $d2) {
        if ($y1 !== $y2) return ($y1 < $y2) ? -1 : 1;
        if ($m1 !== $m2) return ($m1 < $m2) ? -1 : 1;
        if ($d1 !== $d2) return ($d1 < $d2) ? -1 : 1;
        return 0;
    }
}
