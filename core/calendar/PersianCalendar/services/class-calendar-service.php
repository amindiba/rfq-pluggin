<?php
defined('ABSPATH') || exit;

class B2B_PC_Calendar {

    public static function get_month_data($jy, $jm) {
        $days = B2B_PC_Conversion::days_in_jalali_month($jm);
        $first_dow = B2B_PC_Conversion::day_of_week($jy, $jm, 1);
        return array(
            'year'  => $jy,
            'month' => $jm,
            'days'  => $days,
            'first_day_of_week' => $first_dow,
            'month_name' => B2B_PC_Localization::get_month_name($jm),
        );
    }

    public static function get_calendar_grid($jy, $jm) {
        $data = self::get_month_data($jy, $jm);
        $grid = array();
        $day = 1;
        for ($week = 0; $week < 6; $week++) {
            $row = array();
            for ($dow = 0; $dow < 7; $dow++) {
                if ($week === 0 && $dow < $data['first_day_of_week']) {
                    $row[] = null;
                } elseif ($day > $data['days']) {
                    $row[] = null;
                } else {
                    $row[] = $day;
                    $day++;
                }
            }
            $grid[] = $row;
            if ($day > $data['days']) break;
        }
        return $grid;
    }

    public static function get_year_months() {
        return B2B_PC_Localization::get_months();
    }

    public static function get_weekdays() {
        return B2B_PC_Localization::get_weekdays();
    }
}
