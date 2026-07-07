<?php
/**
 * Environment Checker - Validates system requirements before plugin activation.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Environment_Checker
 *
 * Checks WordPress version, PHP version, WooCommerce status,
 * REST API, pretty permalinks, write permissions, and required extensions.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Environment_Checker {

    /**
     * Required PHP extensions.
     *
     * @var array
     */
    private $required_extensions = array(
        'curl',
        'json',
        'mbstring',
        'openssl',
        'xml',
        'zip',
        'gd',
    );

    /**
     * Minimum memory limit in bytes (128MB).
     *
     * @var int
     */
    private $min_memory_limit = 134217728;

    /**
     * Run all environment checks.
     *
     * @return array List of failure messages. Empty array means all checks passed.
     */
    public function check_all() {
        $failures = array();

        $this->check_wordpress_version($failures);
        $this->check_php_version($failures);
        $this->check_required_extensions($failures);
        $this->check_memory_limit($failures);
        $this->check_woocommerce($failures);
        $this->check_rest_api($failures);
        $this->check_pretty_permalinks($failures);
        $this->check_write_permissions($failures);

        return $failures;
    }

    /**
     * Check WordPress version.
     *
     * @param array &$failures Array to append failure messages to.
     */
    private function check_wordpress_version(&$failures) {
        global $wp_version;

        if (version_compare($wp_version, B2B_PROCUREMENT_MIN_WP_VERSION, '<')) {
            $failures[] = sprintf(
                'ورژن وردپرس %1$s یا بالاتر مورد نیاز است. ورژن فعلی شما %2$s می‌باشد.',
                B2B_PROCUREMENT_MIN_WP_VERSION,
                $wp_version
            );
        }
    }

    /**
     * Check PHP version.
     *
     * @param array &$failures Array to append failure messages to.
     */
    private function check_php_version(&$failures) {
        if (version_compare(PHP_VERSION, B2B_PROCUREMENT_MIN_PHP_VERSION, '<')) {
            $failures[] = sprintf(
                'ورژن PHP %1$s یا بالاتر مورد نیاز است. ورژن فعلی شما %2$s می‌باشد.',
                B2B_PROCUREMENT_MIN_PHP_VERSION,
                PHP_VERSION
            );
        }
    }

    /**
     * Check required PHP extensions.
     *
     * @param array &$failures Array to append failure messages to.
     */
    private function check_required_extensions(&$failures) {
        $missing = array();

        foreach ($this->required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        if (!empty($missing)) {
            $failures[] = sprintf(
                'افزونه‌های PHP مورد نیاز نصب نیستند: %s. لطفاً این افزونه‌ها را از بخش PHP Selector در سی‌پنل فعال کنید.',
                implode(', ', $missing)
            );
        }
    }

    /**
     * Check memory limit.
     *
     * @param array &$failures Array to append failure messages to.
     */
    private function check_memory_limit(&$failures) {
        $memory = ini_get('memory_limit');

        if (false !== $memory) {
            $bytes = $this->convert_to_bytes($memory);

            if ($bytes < $this->min_memory_limit) {
                $failures[] = sprintf(
                    'حداقل حافظه مورد نیاز %s است. تنظیم فعلی شما %s می‌باشد. لطفاً مقدار memory_limit را در فایل php.ini افزایش دهید.',
                    '128M',
                    $memory
                );
            }
        }
    }

    /**
     * Check WooCommerce installation and activation.
     *
     * @param array &$failures Array to append failure messages to.
     */
    private function check_woocommerce(&$failures) {
        // Check if WooCommerce is installed.
        if (!class_exists('WooCommerce')) {
            $failures[] = 'افزونه ووکامرس نصب نیست. لطفاً افزونه WooCommerce را نصب و فعال کنید. این پلاگین بدون ووکامرس کار نمی‌کند.';
            return;
        }

        // Check WooCommerce version.
        $wc_version = defined('WC_VERSION') ? WC_VERSION : '0.0.0';

        if (version_compare($wc_version, B2B_PROCUREMENT_MIN_WC_VERSION, '<')) {
            $failures[] = sprintf(
                'ورژن ووکامرس %1$s یا بالاتر مورد نیاز است. ورژن فعلی شما %2$s می‌باشد. لطفاً ووکامرس را به‌روزرسانی کنید.',
                B2B_PROCUREMENT_MIN_WC_VERSION,
                $wc_version
            );
        }
    }

    /**
     * Check if REST API is available.
     *
     * @param array &$failures Array to append failure messages to.
     */
    private function check_rest_api(&$failures) {
        if (!function_exists('rest_get_server')) {
            $failures[] = 'REST API وردپرس غیرفعال است. لطفاً از بخش تنظیمات > لینک‌های یکتا، یکی از ساختارهای پیوند یکتا به جز «ساده» را انتخاب کنید.';
        }
    }

    /**
     * Check if pretty permalinks are enabled.
     *
     * @param array &$failures Array to append failure messages to.
     */
    private function check_pretty_permalinks(&$failures) {
        if (!get_option('permalink_structure')) {
            $failures[] = 'لینک‌های یکتای زیبا (Pretty Permalinks) غیرفعال هستند. لطفاً به تنظیمات > لینک‌های یکتا بروید و یکی از گزینه‌های غیر از «ساده» را انتخاب کنید.';
        }
    }

    /**
     * Check write permissions for uploads directory.
     *
     * @param array &$failures Array to append failure messages to.
     */
    private function check_write_permissions(&$failures) {
        $upload_dir = wp_upload_dir();

        if (!is_writable($upload_dir['basedir'])) {
            $failures[] = sprintf(
                'پوشه آپلوز قابل نوشتن نیست: %s. لطفاً دسترسی پوشه uploads را روی 755 تنظیم کنید.',
                $upload_dir['basedir']
            );
        }
    }

    /**
     * Display admin notice for failed requirements.
     *
     * @param array $failures List of failure messages.
     */
    public function display_admin_notice($failures) {
        echo '<div class="notice notice-error"><p><strong>';
        echo 'سیستم مدیریت خرید B2B';
        echo '</strong> &mdash; ';
        echo 'برای فعال‌سازی این پلاگین، شرایط زیر باید برآورده شوند:';
        echo '</p><ul style="list-style: disc; margin-left: 20px;">';

        foreach ($failures as $failure) {
            echo '<li>' . esc_html($failure) . '</li>';
        }

        echo '</ul></div>';
    }

    /**
     * Convert a size string to bytes.
     *
     * @param string $size Size string (e.g., '128M', '1G').
     * @return int Size in bytes.
     */
    private function convert_to_bytes($size) {
        $units = array(
            'K' => 1024,
            'M' => 1048576,
            'G' => 1073741824,
            'T' => 1099511627776,
        );

        $size = trim($size);
        $unit = strtoupper(substr($size, -1));
        $value = (int) $size;

        if (isset($units[$unit])) {
            $value = (int) $value * $units[$unit];
        }

        return $value;
    }
}
