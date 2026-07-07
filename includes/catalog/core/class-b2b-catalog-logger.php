<?php
defined('ABSPATH') || exit;

class B2B_Catalog_Logger {

    private static $log_file;
    private static $levels = array(
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
    );

    public static function init() {
        self::$log_file = WP_CONTENT_DIR . '/b2b-procurement/logs/catalog.log';
        $dir = dirname(self::$log_file);
        if (!file_exists($dir)) {
            @wp_mkdir_p($dir);
        }
    }

    public static function debug($message, $context = array()) {
        self::log('debug', $message, $context);
    }

    public static function info($message, $context = array()) {
        self::log('info', $message, $context);
    }

    public static function warning($message, $context = array()) {
        self::log('warning', $message, $context);
    }

    public static function error($message, $context = array()) {
        self::log('error', $message, $context);
    }

    private static function log($level, $message, $context = array()) {
        if (empty(self::$log_file)) {
            self::init();
        }

        $min_level = B2B_Catalog_Config::get('logging.level', 'info');
        if (self::$levels[$level] < self::$levels[$min_level]) {
            return;
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $entry = "[{$timestamp}] [{$level}] [User: {$user_id}] {$message}";

        if (!empty($context)) {
            $entry .= ' | ' . wp_json_encode($context);
        }

        $entry .= PHP_EOL;

        @file_put_contents(self::$log_file, $entry, FILE_APPEND | LOCK_EX);
    }

    public static function get_recent($lines = 50) {
        if (!file_exists(self::$log_file)) {
            return array();
        }

        $content = @file_get_contents(self::$log_file);
        if (empty($content)) {
            return array();
        }

        $all_lines = explode(PHP_EOL, $content);
        $all_lines = array_filter($all_lines);
        $all_lines = array_reverse($all_lines);

        return array_slice($all_lines, 0, $lines);
    }

    public static function clear() {
        if (file_exists(self::$log_file)) {
            @unlink(self::$log_file);
        }
    }
}
