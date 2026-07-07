<?php
/**
 * Deactivator - Handles plugin deactivation logic.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Deactivator
 *
 * Cleans up transient state on deactivation while preserving stored data.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Deactivator {

    /**
     * Plugin deactivation handler.
     *
     * Clears scheduled events and transient data.
     * Does NOT remove stored files or database tables (that is uninstall logic).
     */
    public static function deactivate() {
        // Clear all scheduled events.
        self::clear_scheduled_events();

        // Clear transient data.
        self::clear_transients();

        // Flush rewrite rules.
        flush_rewrite_rules();

        // Log deactivation.
        if (function_exists('error_log')) {
            error_log('[B2B Procurement] Plugin deactivated.');
        }
    }

    /**
     * Clear all scheduled cron events for this plugin.
     */
    private static function clear_scheduled_events() {
        $timestamp = wp_next_scheduled('b2b_procurement_cleanup_temp');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'b2b_procurement_cleanup_temp');
        }
    }

    /**
     * Clear plugin transients.
     */
    private static function clear_transients() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_b2b_procurement_%' OR option_name LIKE '_transient_timeout_b2b_procurement_%'"
        );
    }
}
