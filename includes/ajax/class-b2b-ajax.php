<?php
/**
 * AJAX - Reusable AJAX infrastructure.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Ajax
 *
 * Provides centralized AJAX handler registration and response utilities.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Ajax {

    /**
     * Registered AJAX actions.
     *
     * @var array
     */
    private static $actions = array();

    /**
     * Initialize AJAX infrastructure.
     */
    public static function init() {
        // Register core AJAX actions here (none for bootstrap phase).
        // Example: self::register_action('my_action', 'handle_my_action');

        // Register all hooks.
        self::register_hooks();
    }

    /**
     * Register a new AJAX action.
     *
     * @param string $action   The AJAX action name (without 'wp_ajax_' prefix).
     * @param string $callback The static method name to call.
     * @param bool   $logged_in Whether to also register for logged-in users (default: true).
     */
    public static function register_action($action, $callback, $logged_in = true) {
        self::$actions[$action] = array(
            'callback'  => $callback,
            'logged_in' => $logged_in,
        );
    }

    /**
     * Register all AJAX hooks with WordPress.
     */
    private static function register_hooks() {
        foreach (self::$actions as $action => $config) {
            // Always register for logged-in users.
            add_action("wp_ajax_{$action}", array(__CLASS__, $config['callback']));

            // Register for non-logged-in users if configured.
            if ($config['logged_in']) {
                add_action("wp_ajax_nopriv_{$action}", array(__CLASS__, $config['callback']));
            }
        }
    }

    /**
     * Send a JSON success response.
     *
     * @param mixed $data The data to send.
     */
    public static function send_success($data = null) {
        wp_send_json_success($data);
    }

    /**
     * Send a JSON error response.
     *
     * @param string $message The error message.
     * @param int    $code    HTTP status code.
     */
    public static function send_error($message = '', $code = 400) {
        wp_send_json_error(array(
            'message' => $message,
            'code'    => $code,
        ));
    }

    /**
     * Verify AJAX nonce and capability.
     *
     * @param string $action     The nonce action name.
     * @param string $capability The required capability.
     * @return bool True if verified.
     */
    public static function verify_request($action = '', $capability = 'manage_woocommerce') {
        $action = $action ?: B2B_Procurement_Security::NONCE_ACTION;

        // Verify nonce.
        if (!isset($_POST['_b2b_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_b2b_nonce'])), $action)) {
            self::send_error('بررسی امنیتی ناموفق بود. لطفاً صفحه را رفرش کنید و دوباره تلاش کنید.', 403);
            return false;
        }

        // Check capability.
        if (!current_user_can($capability)) {
            self::send_error('شما اجازه انجام این عملیات را ندارید.', 403);
            return false;
        }

        return true;
    }

    /**
     * Get and sanitize a POST parameter.
     *
     * @param string $key     The parameter key.
     * @param mixed  $default Default value.
     * @return mixed Sanitized value.
     */
    public static function get_param($key, $default = null) {
        if (!isset($_POST[$key])) {
            return $default;
        }

        $value = wp_unslash($_POST[$key]);

        if (is_string($value)) {
            return sanitize_text_field($value);
        }

        return $value;
    }

    /**
     * Get and sanitize a JSON request body.
     *
     * @return array|null Decoded JSON data or null on failure.
     */
    public static function get_json_body() {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }
}
