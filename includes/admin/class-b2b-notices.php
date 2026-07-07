<?php
/**
 * Notices - Reusable admin notice system.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Notices
 *
 * Manages admin notices with support for different types and dismissible notices.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Notices {

    /**
     * Transient key for storing notices.
     *
     * @var string
     */
    const TRANSIENT_KEY = 'b2b_admin_notices';

    /**
     * Initialize notice system.
     */
    public static function init() {
        add_action('admin_notices', array(__CLASS__, 'display_notices'));
    }

    /**
     * Add a notice.
     *
     * @param string $message Notice message.
     * @param string $type Notice type (success, error, warning, info).
     * @param bool $dismissible Whether the notice is dismissible.
     * @param string $unique_id Optional unique ID for deduplication.
     */
    public static function add($message, $type = 'info', $dismissible = true, $unique_id = '') {
        $notices = self::get_all();

        if ($unique_id) {
            foreach ($notices as $notice) {
                if (isset($notice['id']) && $notice['id'] === $unique_id) {
                    return;
                }
            }
        }

        $notices[] = array(
            'id' => $unique_id,
            'message' => $message,
            'type' => $type,
            'dismissible' => $dismissible,
            'time' => time(),
        );

        set_transient(self::TRANSIENT_KEY, $notices, 60);
    }

    /**
     * Get all pending notices.
     *
     * @return array Notices array.
     */
    public static function get_all() {
        $notices = get_transient(self::TRANSIENT_KEY);
        return is_array($notices) ? $notices : array();
    }

    /**
     * Clear all notices.
     */
    public static function clear() {
        delete_transient(self::TRANSIENT_KEY);
    }

    /**
     * Display all pending notices.
     */
    public static function display_notices() {
        $notices = self::get_all();

        if (empty($notices)) {
            return;
        }

        foreach ($notices as $notice) {
            $type_class = 'notice-' . esc_attr($notice['type']);
            $dismissible = !empty($notice['dismissible']) ? ' is-dismissible' : '';

            echo '<div class="notice ' . $type_class . $dismissible . '">';
            echo '<p>' . wp_kses_post($notice['message']) . '</p>';
            echo '</div>';
        }

        // Clear after displaying.
        self::clear();
    }

    /**
     * Add success notice.
     *
     * @param string $message Notice message.
     * @param bool $dismissible Whether dismissible.
     */
    public static function success($message, $dismissible = true) {
        self::add($message, 'success', $dismissible);
    }

    /**
     * Add error notice.
     *
     * @param string $message Notice message.
     * @param bool $dismissible Whether dismissible.
     */
    public static function error($message, $dismissible = false) {
        self::add($message, 'error', $dismissible);
    }

    /**
     * Add warning notice.
     *
     * @param string $message Notice message.
     * @param bool $dismissible Whether dismissible.
     */
    public static function warning($message, $dismissible = true) {
        self::add($message, 'warning', $dismissible);
    }

    /**
     * Add info notice.
     *
     * @param string $message Notice message.
     * @param bool $dismissible Whether dismissible.
     */
    public static function info($message, $dismissible = true) {
        self::add($message, 'info', $dismissible);
    }
}
