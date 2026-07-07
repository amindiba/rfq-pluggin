<?php
defined('ABSPATH') || exit;

class B2B_PC_Conversion {

    const JALALI_EPOCH = 1948321;
    const GREGORIAN_EPOCH = 1721426;

    private static $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    private static $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

    public static function gregorian_to_jalali($gy, $gm, $gd) {
        $gy = intval($gy);
        $gm = intval($gm);
        $gd = intval($gd);
        $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
        if ($gy > 2029) { echo 'year out of range'; return array(0, 0, 0); }
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + intval(($gy2 + 3) / 4) - intval(($gy2 + 99) / 100) + intval(($gy2 + 399) / 400) + $gd + $g_d_m[$gm - 1];
        $j = -1595 + (33 * intval($days / 12053));
        $days %= 12053;
        $j += 4 * intval($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $j += intval(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $je = 1 + intval($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $je = 7 + intval(($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return array($j, $je, $jd);
    }

    public static function jalali_to_gregorian($jy, $jm, $jd) {
        $jy = intval($jy);
        $jm = intval($jm);
        $jd = intval($jd);
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intval($jy / 33) * 8) + intval((($jy % 33) + 3) / 4) + $jd + ((($jm < 7) ? ($jm - 1) : ($jm - 7)) * 30) + $jm - 1;
        $gy = 400 * intval($days / 146097);
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * intval(--$days / 36524);
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        $gy += 4 * intval($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += intval(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        foreach (self::$g_days_in_month as $m => $dim) {
            if ($gd <= $dim) break;
            $gd -= $dim;
        }
        $gm = $m + 1;
        return array($gy, $gm, $gd);
    }

    public static function timestamp_to_jalali($timestamp) {
        $dt = new DateTime('@' . $timestamp);
        $dt->setTimezone(new DateTimeZone('Asia/Tehran'));
        return self::gregorian_to_jalali(
            intval($dt->format('Y')),
            intval($dt->format('n')),
            intval($dt->format('j'))
        );
    }

    public static function jalali_to_timestamp($jy, $jm, $jd) {
        list($gy, $gm, $gd) = self::jalali_to_gregorian($jy, $jm, $jd);
        $dt = new DateTime("$gy-$gm-$gd", new DateTimeZone('Asia/Tehran'));
        return $dt->getTimestamp();
    }

    public static function now_jalali() {
        $now = new DateTime('now', new DateTimeZone('Asia/Tehran'));
        return self::gregorian_to_jalali(
            intval($now->format('Y')),
            intval($now->format('n')),
            intval($now->format('j'))
        );
    }

    public static function days_in_jalali_month($jm) {
        if ($jm < 1 || $jm > 12) return 0;
        return ($jm <= 6) ? 31 : (($jm <= 11) ? 30 : 29);
    }

    public static function is_leap_year($jy) {
        $reference = 474;
        $cycle = ($jy - $reference + 1948320) % 2820;
        $years = ($cycle % 128);
        $leap = ($years % 4 === 0);
        return $leap;
    }

    public static function day_of_week($jy, $jm, $jd) {
        list($gy, $gm, $gd) = self::jalali_to_gregorian($jy, $jm, $jd);
        $dt = new DateTime("$gy-$gm-$gd");
        $dow = intval($dt->format('w'));
        return ($dow + 1) % 7;
    }
}
