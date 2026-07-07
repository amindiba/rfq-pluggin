<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Logs_Page {
    public static function render() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $log_file = defined('B2B_PROCUREMENT_LOG_FILE') ? B2B_PROCUREMENT_LOG_FILE : WP_CONTENT_DIR . '/b2b-procurement/logs/activity.log';

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header"><div><h1 class="b2b-workspace-title">لاگ‌ها</h1><p class="b2b-workspace-subtitle">مشاهده فایل‌های گزارش سامانه</p></div></div>

        <?php if (!file_exists($log_file)) : ?>
            <div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128196;</div><p class="b2b-empty-state-text">فایل لاگی موجود نیست.</p></div></div></div>
        <?php else:
            $log_content = @file_get_contents($log_file);
            if (empty($log_content)) : ?>
                <div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128196;</div><p class="b2b-empty-state-text">فایل لاگ خالی است.</p></div></div></div>
            <?php else:
                $lines = array_filter(explode("\n", $log_content));
                $lines = array_reverse($lines);
                $total = count($lines);
                $size = size_format(filesize($log_file));
                $modified = date('Y-m-d H:i:s', filemtime($log_file));
            ?>
            <div class="b2b-card-grid b2b-card-grid-3 b2b-mb-5">
                <div class="b2b-stat-card"><div class="b2b-stat-icon b2b-stat-icon-info">&#128196;</div><div class="b2b-stat-value"><?php echo $total; ?></div><div class="b2b-stat-label">تعداد ردیف</div></div>
                <div class="b2b-stat-card"><div class="b2b-stat-icon b2b-stat-icon-warning">&#128230;</div><div class="b2b-stat-value"><?php echo esc_html($size); ?></div><div class="b2b-stat-label">حجم فایل</div></div>
                <div class="b2b-stat-card"><div class="b2b-stat-icon b2b-stat-icon-success">&#128339;</div><div class="b2b-stat-value"><?php echo esc_html($modified); ?></div><div class="b2b-stat-label">آخرین بروزرسانی</div></div>
            </div>
            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">محتوای لاگ</h2><div class="b2b-card-actions"><input type="text" id="b2b-log-search" class="b2b-search-input" placeholder="جستجو..." style="max-width:250px;" /></div></div>
                <div class="b2b-card-body" style="padding:0;"><div class="b2b-log-viewer" style="border-radius:0 0 12px 12px;"><pre class="b2b-log-content" id="b2b-log-content"><?php foreach (array_slice($lines, 0, 500) as $line) { echo esc_html($line) . "\n"; } ?></pre></div></div>
            </div>
            <?php endif; endif;
        B2B_Procurement_Admin::shell_end();
    }
}
