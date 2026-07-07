<?php
/**
 * Storage - Manages plugin file storage outside WordPress media library.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Storage
 *
 * Creates and manages dedicated storage directories for plugin operations.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Storage {

    /**
     * Base storage path.
     *
     * @var string
     */
    private static $base_path;

    /**
     * Base storage URL.
     *
     * @var string
     */
    private static $base_url;

    /**
     * Initialize storage.
     */
    public static function init() {
        self::$base_path = WP_CONTENT_DIR . '/b2b-procurement/';
        self::$base_url = content_url('/b2b-procurement/');

        self::ensure_directories();
    }

    /**
     * Ensure all storage directories exist.
     */
    private static function ensure_directories() {
        $directories = array(
            '',
            'documents',
            'temp',
            'logs',
            'exports',
        );

        foreach ($directories as $dir) {
            $path = self::$base_path . $dir;
            if (!file_exists($path)) {
                wp_mkdir_p($path);
            }
        }
    }

    /**
     * Get the path for a storage subdirectory.
     *
     * @param string $subdir The subdirectory name.
     * @return string Full path.
     */
    public static function get_path($subdir = '') {
        $path = self::$base_path . $subdir;
        if (!file_exists($path)) {
            wp_mkdir_p($path);
        }
        return trailingslashit($path);
    }

    /**
     * Get the URL for a storage subdirectory.
     *
     * @param string $subdir The subdirectory name.
     * @return string Full URL.
     */
    public static function get_url($subdir = '') {
        return trailingslashit(self::$base_url . $subdir);
    }

    /**
     * Get a unique file path in a subdirectory.
     *
     * @param string $subdir    The subdirectory.
     * @param string $filename  The desired filename.
     * @return string Unique file path.
     */
    public static function get_unique_path($subdir, $filename) {
        $dir = self::get_path($subdir);
        $path = $dir . $filename;

        if (!file_exists($path)) {
            return $path;
        }

        $info = pathinfo($filename);
        $base = $info['filename'];
        $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
        $counter = 1;

        while (file_exists($path)) {
            $path = $dir . $base . '-' . $counter . $ext;
            $counter++;
        }

        return $path;
    }

    /**
     * Store a file from a WordPress upload.
     *
     * @param array  $file    The $_FILES array entry.
     * @param string $subdir  The subdirectory to store in.
     * @return array|WP_Error Array with file info or error.
     */
    public static function store_upload($file, $subdir = 'documents') {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('invalid_upload', __('Invalid file upload.', 'b2b-procurement'));
        }

        $target = self::get_unique_path($subdir, basename($file['name']));

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return new WP_Error('move_failed', __('Failed to move uploaded file.', 'b2b-procurement'));
        }

        return array(
            'path' => $target,
            'url'  => str_replace(WP_CONTENT_DIR, content_url(), $target),
            'name' => basename($target),
            'size' => $file['size'],
            'type' => $file['type'],
        );
    }

    /**
     * Delete a file from storage.
     *
     * @param string $filepath Full path to the file.
     * @return bool True on success.
     */
    public static function delete_file($filepath) {
        // Security: ensure the file is within our storage directory.
        $real_base = realpath(self::$base_path);
        $real_file = realpath($filepath);

        if (strpos($real_file, $real_base) !== 0) {
            return false;
        }

        if (file_exists($real_file)) {
            return @unlink($real_file);
        }

        return false;
    }

    /**
     * Clean up temp directory (delete files older than 24 hours).
     */
    public static function cleanup_temp() {
        $temp_dir = self::get_path('temp');

        if (!is_dir($temp_dir)) {
            return;
        }

        $files = glob($temp_dir . '*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > DAY_IN_SECONDS) {
                @unlink($file);
            }
        }
    }

    /**
     * Get storage usage statistics.
     *
     * @return array Storage usage info.
     */
    public static function get_usage() {
        $base = self::$base_path;
        $dirs = array('documents', 'temp', 'logs', 'exports');
        $usage = array(
            'total' => 0,
            'dirs'  => array(),
        );

        foreach ($dirs as $dir) {
            $path = $base . $dir;
            $size = is_dir($path) ? self::get_directory_size($path) : 0;
            $usage['dirs'][$dir] = $size;
            $usage['total'] += $size;
        }

        return $usage;
    }

    /**
     * Calculate directory size recursively.
     *
     * @param string $directory Directory path.
     * @return int Total size in bytes.
     */
    private static function get_directory_size($directory) {
        $size = 0;

        if (!is_dir($directory)) {
            return 0;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
