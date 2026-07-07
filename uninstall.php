<?php
/**
 * Uninstall - Clean removal of all plugin data.
 *
 * Fired when the plugin is deleted from the WordPress admin.
 * Only runs when the plugin is fully uninstalled (not just deactivated).
 *
 * @package B2B_Procurement
 */

// Abort if not called by WordPress uninstaller.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Delete all plugin options from the database.
 */
$b2b_options = array(
    'b2b_procurement_version',
    'b2b_procurement_installed_at',
    'b2b_procurement_settings',
);

foreach ($b2b_options as $option) {
    delete_option($option);
}

/**
 * Delete all transients.
 */
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_b2b_procurement_%' OR option_name LIKE '_transient_timeout_b2b_procurement_%'"
);

/**
 * Remove storage directory.
 */
$storage_dir = WP_CONTENT_DIR . '/b2b-procurement/';
if (is_dir($storage_dir)) {
    // Recursive delete.
    $iterator = new RecursiveDirectoryIterator($storage_dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }

    rmdir($storage_dir);
}

/**
 * Remove scheduled events.
 */
wp_clear_scheduled_hook('b2b_procurement_cleanup_temp');
