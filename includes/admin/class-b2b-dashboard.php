<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Dashboard {

    public static function render() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        global $wpdb;

        $wc_installed = class_exists('WooCommerce');
        $wc_version = $wc_installed && defined('WC_VERSION') ? WC_VERSION : '-';
        $theme = wp_get_theme();

        B2B_Procurement_Admin::shell_start();
        ?>

        <div class="b2b-workspace-header">
            <div>
                <h1 class="b2b-workspace-title">داشبورد</h1>
                <p class="b2b-workspace-subtitle">خوش آمدید به سیستم مدیریت خرید B2B</p>
            </div>
        </div>

        <div class="b2b-card-grid b2b-card-grid-4 b2b-mb-6">
            <?php
            $stats = array(
                array('icon' => '&#128202;', 'label' => 'ورژن پلاگین', 'value' => B2B_PROCUREMENT_VERSION, 'color' => 'primary'),
                array('icon' => '&#128187;', 'label' => 'وردپرس', 'value' => get_bloginfo('version'), 'color' => 'info'),
                array('icon' => '&#128722;', 'label' => 'ووکامرس', 'value' => $wc_version, 'color' => 'success'),
                array('icon' => '&#9881;', 'label' => 'PHP', 'value' => PHP_VERSION, 'color' => 'warning'),
            );
            foreach ($stats as $s) : ?>
                <div class="b2b-stat-card">
                    <div class="b2b-stat-icon b2b-stat-icon-<?php echo $s['color']; ?>"><?php echo $s['icon']; ?></div>
                    <div class="b2b-stat-value"><?php echo esc_html($s['value']); ?></div>
                    <div class="b2b-stat-label"><?php echo esc_html($s['label']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="b2b-card-grid b2b-card-grid-2">
            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات پلاگین</h2></div>
                <div class="b2b-card-body">
                    <table class="b2b-status-table">
                        <tr><td class="b2b-status-label">نام پلاگین</td><td>سیستم مدیریت خرید B2B</td></tr>
                        <tr><td class="b2b-status-label">ورژن</td><td><?php echo B2B_PROCUREMENT_VERSION; ?></td></tr>
                        <tr><td class="b2b-status-label">وضعیت</td><td><span class="b2b-status-pill b2b-status-pill-active">فعال</span></td></tr>
                        <tr><td class="b2b-status-label">سازنده</td><td>امین دیبا</td></tr>
                    </table>
                </div>
            </div>
            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">وضعیت سیستم</h2></div>
                <div class="b2b-card-body">
                    <table class="b2b-status-table">
                        <tr><td class="b2b-status-label">ورژن PHP</td><td><?php echo PHP_VERSION; ?></td></tr>
                        <tr><td class="b2b-status-label">حافظه</td><td><?php echo ini_get('memory_limit'); ?></td></tr>
                        <tr><td class="b2b-status-label">REST API</td><td><?php echo function_exists('rest_get_server') ? '<span class="b2b-status-pill b2b-status-pill-active">فعال</span>' : '<span class="b2b-status-pill b2b-status-pill-inactive">غیرفعال</span>'; ?></td></tr>
                        <tr><td class="b2b-status-label">لینک‌های یکتا</td><td><?php echo get_option('permalink_structure') ? '<span class="b2b-status-pill b2b-status-pill-active">فعال</span>' : '<span class="b2b-status-pill b2b-status-pill-warning">غیرفعال</span>'; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
