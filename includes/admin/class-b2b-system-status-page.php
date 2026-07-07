<?php
defined('ABSPATH') || exit;

class B2B_Procurement_System_Status_Page {
    public static function render() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        global $wpdb;

        $wc_installed = class_exists('WooCommerce');
        $wc_version = $wc_installed ? (defined('WC_VERSION') ? WC_VERSION : 'نامشخص') : 'نصب نشده';
        $upload_dir = wp_upload_dir();
        $storage_dir = WP_CONTENT_DIR . '/b2b-procurement/';
        $storage_exists = file_exists($storage_dir);
        $theme = wp_get_theme();

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header"><div><h1 class="b2b-workspace-title">وضعیت سیستم</h1><p class="b2b-workspace-subtitle">اطلاعات کامل سرور و محیط اجرا</p></div></div>

        <div class="b2b-card-grid b2b-card-grid-4 b2b-mb-6">
            <div class="b2b-kpi-card">
                <div class="b2b-kpi-top">
                    <div class="b2b-kpi-icon b2b-kpi-icon-info">&#128187;</div>
                    <div class="b2b-kpi-title">وردپرس</div>
                </div>
                <div class="b2b-kpi-value">
                    <span class="b2b-kpi-number"><?php echo esc_html(get_bloginfo('version')); ?></span>
                </div>
            </div>
            <div class="b2b-kpi-card">
                <div class="b2b-kpi-top">
                    <div class="b2b-kpi-icon b2b-kpi-icon-primary">&#9881;</div>
                    <div class="b2b-kpi-title">PHP</div>
                </div>
                <div class="b2b-kpi-value">
                    <span class="b2b-kpi-number"><?php echo esc_html(PHP_VERSION); ?></span>
                </div>
            </div>
            <div class="b2b-kpi-card">
                <div class="b2b-kpi-top">
                    <div class="b2b-kpi-icon b2b-kpi-icon-warning">&#128181;</div>
                    <div class="b2b-kpi-title">MySQL</div>
                </div>
                <div class="b2b-kpi-value">
                    <span class="b2b-kpi-number"><?php echo esc_html($wpdb->get_var('SELECT VERSION()')); ?></span>
                </div>
            </div>
            <div class="b2b-kpi-card">
                <div class="b2b-kpi-top">
                    <div class="b2b-kpi-icon b2b-kpi-icon-success">&#128722;</div>
                    <div class="b2b-kpi-title">ووکامرس</div>
                </div>
                <div class="b2b-kpi-value">
                    <span class="b2b-kpi-number"><?php echo esc_html($wc_version); ?></span>
                </div>
            </div>
        </div>

        <div class="b2b-card-grid b2b-card-grid-2">
            <?php
            $cards = array(
                'وردپرس' => array('ورژن' => get_bloginfo('version'), 'چندسایته' => is_multisite() ? 'بله' : 'خیر', 'حالت اشکال‌زدایی' => (defined('WP_DEBUG') && WP_DEBUG) ? 'فعال' : 'غیرفعال', 'REST API' => function_exists('rest_get_server') ? 'فعال' : 'غیرفعال'),
                'PHP' => array('ورژن' => PHP_VERSION, 'حافظه' => ini_get('memory_limit'), 'حداکثر POST' => ini_get('post_max_size'), 'حداکثر آپلود' => ini_get('upload_max_filesize')),
                'سرور' => array('نرم‌افزار' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'نامشخص', 'سیستم‌عامل' => php_uname('s') . ' ' . php_uname('r')),
                'ذخیره‌سازی' => array('آپلود' => $upload_dir['basedir'], 'وضعیت' => is_writable($upload_dir['basedir']) ? 'قابل نوشتن' : 'غیرقابل نوشتن', 'فضا' => $storage_exists ? size_format(B2B_Procurement_Storage::get_usage()['total']) : '0'),
                'قالب' => array('نام' => $theme->get('Name'), 'ورژن' => $theme->get('Version')),
                'افزونه‌ها' => array('فعال' => count(get_option('active_plugins', array())), 'کل' => count(get_plugins())),
            );
            foreach ($cards as $title => $items) : ?>
            <div class="b2b-card"><div class="b2b-card-header"><h2 class="b2b-card-title"><?php echo esc_html($title); ?></h2></div><div class="b2b-card-body"><table class="b2b-status-table">
            <?php foreach ($items as $label => $value) :
                $cls = in_array($value, array('فعال', 'بله', 'قابل نوشتن')) ? 'b2b-status-pill-active' : (in_array($value, array('غیرفعال', 'خیر', 'غیرقابل نوشتن', 'نصب نشده')) ? 'b2b-status-pill-inactive' : '');
            ?>
                <tr><td class="b2b-status-label"><?php echo esc_html($label); ?></td><td><?php echo $cls ? '<span class="b2b-status-pill ' . $cls . '">' . esc_html($value) . '</span>' : esc_html($value); ?></td></tr>
            <?php endforeach; ?>
            </table></div></div>
            <?php endforeach; ?>
        </div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
