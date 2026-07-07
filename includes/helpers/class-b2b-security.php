<?php
/**
 * Security - Nonce, capability, and sanitization utilities.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Security
 *
 * Centralized security helpers for nonces, capabilities, and sanitization.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Security {

    /**
     * Plugin nonce action prefix.
     *
     * @var string
     */
    const NONCE_ACTION = 'b2b_procurement_nonce';

    /**
     * Verify a nonce for a given action.
     *
     * @param string $nonce  The nonce value to verify.
     * @param string $action The action name.
     * @return bool True if valid, false otherwise.
     */
    public static function verify_nonce($nonce, $action = '') {
        $action = $action ?: self::NONCE_ACTION;
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Check if the current user has a required capability.
     *
     * @param string $capability The capability to check.
     * @return bool True if user has capability.
     */
    public static function check_capability($capability = 'manage_woocommerce') {
        return current_user_can($capability);
    }

    /**
     * Require a capability or die.
     *
     * @param string $capability The capability required.
     */
    public static function require_capability($capability = 'manage_woocommerce') {
        if (!self::check_capability($capability)) {
            wp_die(
                'شما دسترسی کافی برای مشاهده این صفحه را ندارید. لطفاً با حساب مدیر وارد شوید.',
                'دسترسی رد شد',
                array('response' => 403)
            );
        }
    }

    /**
     * Sanitize a text field.
     *
     * @param string $input Raw input.
     * @return string Sanitized output.
     */
    public static function sanitize_text($input) {
        return sanitize_text_field($input);
    }

    /**
     * Sanitize an email field.
     *
     * @param string $input Raw input.
     * @return string Sanitized output.
     */
    public static function sanitize_email($input) {
        return sanitize_email($input);
    }

    /**
     * Sanitize a textarea field.
     *
     * @param string $input Raw input.
     * @return string Sanitized output.
     */
    public static function sanitize_textarea($input) {
        return sanitize_textarea_field($input);
    }

    /**
     * Sanitize an integer field.
     *
     * @param mixed  $input Raw input.
     * @param int    $default Default value.
     * @return int Sanitized integer.
     */
    public static function sanitize_integer($input, $default = 0) {
        return absint($input) ?: $default;
    }

    /**
     * Sanitize a URL field.
     *
     * @param string $input Raw input.
     * @return string Sanitized URL.
     */
    public static function sanitize_url($input) {
        return esc_url_raw($input);
    }

    /**
     * Escape output for HTML display.
     *
     * @param string $input Raw output.
     * @return string Escaped output.
     */
    public static function escape_html($input) {
        return esc_html($input);
    }

    /**
     * Escape output for attribute display.
     *
     * @param string $input Raw output.
     * @return string Escaped output.
     */
    public static function escape_attr($input) {
        return esc_attr($input);
    }

    /**
     * Escape output for URL display.
     *
     * @param string $input Raw output.
     * @return string Escaped output.
     */
    public static function escape_url($input) {
        return esc_url($input);
    }

    /**
     * Generate a nonce field for forms.
     *
     * @param string $action The action name.
     * @return string HTML nonce field.
     */
    public static function nonce_field($action = '') {
        $action = $action ?: self::NONCE_ACTION;
        return wp_nonce_field($action, '_b2b_nonce', true, false);
    }

    /**
     * Generate a nonce URL.
     *
     * @param string $url    The URL to add nonce to.
     * @param string $action The action name.
     * @return string URL with nonce.
     */
    public static function nonce_url($url, $action = '') {
        $action = $action ?: self::NONCE_ACTION;
        return wp_nonce_url($url, $action);
    }
}
