<?php
/**
 * Logger - Centralized logging service.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Logger
 *
 * Provides file-based logging with log levels and rotation support.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Logger {

    /**
     * Log levels.
     *
     * @var string
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO  = 'info';
    const LEVEL_WARN  = 'warning';
    const LEVEL_ERROR = 'error';

    /**
     * Log file path.
     *
     * @var string
     */
    private static $log_file;

    /**
     * Maximum log file size before rotation (5MB).
     *
     * @var int
     */
    private static $max_size = 5242880;

    /**
     * Initialize the logger.
     */
    public static function init() {
        self::$log_file = defined('B2B_PROCUREMENT_LOG_FILE')
            ? B2B_PROCUREMENT_LOG_FILE
            : WP_CONTENT_DIR . '/b2b-procurement/logs/activity.log';

        // Ensure log directory exists.
        $log_dir = dirname(self::$log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
    }

    /**
     * Log a debug message.
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     */
    public static function debug($message, $context = array()) {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     */
    public static function info($message, $context = array()) {
        self::log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     */
    public static function warning($message, $context = array()) {
        self::log(self::LEVEL_WARN, $message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     */
    public static function error($message, $context = array()) {
        self::log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Write a log entry.
     *
     * @param string $level   The log level.
     * @param string $message The log message.
     * @param array  $context Additional context data.
     */
    private static function log($level, $message, $context = array()) {
        if (empty(self::$log_file)) {
            self::init();
        }

        // Rotate if needed.
        self::rotate_if_needed();

        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $ip = self::get_client_ip();

        $entry = sprintf(
            "[%s] [%s] [User: %d] [IP: %s] %s",
            $timestamp,
            strtoupper($level),
            $user_id,
            $ip,
            $message
        );

        if (!empty($context)) {
            $entry .= ' | Context: ' . wp_json_encode($context);
        }

        $entry .= PHP_EOL;

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        @file_put_contents(self::$log_file, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Rotate log file if it exceeds maximum size.
     */
    private static function rotate_if_needed() {
        if (!file_exists(self::$log_file)) {
            return;
        }

        if (filesize(self::$log_file) < self::$max_size) {
            return;
        }

        $rotated = self::$log_file . '.' . date('Y-m-d-His') . '.log';
        rename(self::$log_file, $rotated);

        // Keep only last 5 rotated files.
        self::cleanup_old_logs();
    }

    /**
     * Remove old rotated log files.
     */
    private static function cleanup_old_logs() {
        $log_dir = dirname(self::$log_file);
        $files = glob($log_dir . '/*.log.*');

        if (count($files) <= 5) {
            return;
        }

        // Sort by modification time.
        usort($files, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Remove oldest files.
        $to_remove = array_slice($files, 0, count($files) - 5);
        foreach ($to_remove as $file) {
            @unlink($file);
        }
    }

    /**
     * Get client IP address.
     *
     * @return string IP address.
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', sanitize_text_field(wp_unslash($_SERVER[$key])));
                return trim($ips[0]);
            }
        }

        return '0.0.0.0';
    }
}
