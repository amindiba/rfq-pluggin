<?php
defined('ABSPATH') || exit;

class B2B_Catalog_Config {

    private static $config = array();

    public static function init() {
        self::$config = array(
            'database' => array(
                'prefix' => $wpdb->prefix ?? 'wp_',
                'charset' => 'utf8mb4',
            ),
            'pagination' => array(
                'default_per_page' => 20,
                'max_per_page' => 100,
            ),
            'upload' => array(
                'max_file_size' => 5 * 1024 * 1024,
                'allowed_types' => array('csv'),
            ),
            'logging' => array(
                'enabled' => true,
                'level' => 'info',
                'file' => WP_CONTENT_DIR . '/b2b-procurement/logs/catalog.log',
            ),
        );
    }

    public static function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = self::$config;
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }

    public static function set($key, $value) {
        $keys = explode('.', $key);
        $ref = &self::$config;
        foreach ($keys as $k) {
            if (!isset($ref[$k])) {
                $ref[$k] = array();
            }
            $ref = &$ref[$k];
        }
        $ref = $value;
    }
}
