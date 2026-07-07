<?php
namespace B2B\DynamicSpecs\FieldType;

defined('ABSPATH') || exit;

class Registry {

    private static $types = array();

    public static function init() {
        self::register_defaults();
    }

    private static function register_defaults() {
        $types = array(
            'text'       => array('label' => 'متن', 'icon' => '&#9998;'),
            'textarea'   => array('label' => 'متن چندخطی', 'icon' => '&#128221;'),
            'number'     => array('label' => 'عدد', 'icon' => '&#128290;'),
            'decimal'    => array('label' => 'عدد اعشاری', 'icon' => '&#128200;'),
            'select'     => array('label' => 'انتخابی', 'icon' => '&#9660;'),
            'multi_select' => array('label' => 'انتخاب چندگانه', 'icon' => '&#9745;'),
            'checkbox'   => array('label' => 'چک‌باکس', 'icon' => '&#9745;'),
            'radio'      => array('label' => 'دکمه رادیویی', 'icon' => '&#9898;'),
            'switch'     => array('label' => 'کلید', 'icon' => '&#9889;'),
            'date'       => array('label' => 'تاریخ', 'icon' => '&#128197;'),
            'time'       => array('label' => 'زمان', 'icon' => '&#128336;'),
            'datetime'   => array('label' => 'تاریخ و زمان', 'icon' => '&#128337;'),
            'url'        => array('label' => 'لینک', 'icon' => '&#128279;'),
            'email'      => array('label' => 'ایمیل', 'icon' => '&#128231;'),
            'phone'      => array('label' => 'تلفن', 'icon' => '&#128222;'),
            'color'      => array('label' => 'رنگ', 'icon' => '&#127912;'),
            'range'      => array('label' => 'محدوده', 'icon' => '&#128207;'),
            'image'      => array('label' => 'تصویر', 'icon' => '&#128247;'),
            'file'       => array('label' => 'فایل', 'icon' => '&#128194;'),
            'wysiwyg'    => array('label' => 'ویرایشگر متن', 'icon' => '&#128221;'),
        );
        foreach ($types as $key => $type) {
            self::$types[$key] = $type;
        }
    }

    public static function register($key, $label, $icon = '') {
        self::$types[$key] = array('label' => $label, 'icon' => $icon);
    }

    public static function get_all() {
        return self::$types;
    }

    public static function get_label($key) {
        return isset(self::$types[$key]) ? self::$types[$key]['label'] : $key;
    }

    public static function has_options($type) {
        return in_array($type, array('select', 'multi_select', 'radio'), true);
    }
}
