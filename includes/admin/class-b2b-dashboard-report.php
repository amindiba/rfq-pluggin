<?php
defined('ABSPATH') || exit;

class B2B_Dashboard_Report {

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function enqueue_assets($hook) {
        $pages = array('b2b-dashboard', 'b2b-report-rfqs', 'b2b-report-pos', 'b2b-report-contracts');
        foreach ($pages as $p) {
            if (strpos($hook, $p) !== false) {
                wp_enqueue_media();
                wp_enqueue_script('b2b-admin-js', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), B2B_PROCUREMENT_VERSION, true);
                wp_localize_script('b2b-admin-js', 'b2bProcurement', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
                    'version' => B2B_PROCUREMENT_VERSION,
                ));
                wp_enqueue_script('b2b-dashboard', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/dashboard.js', array('b2b-admin-js'), B2B_PROCUREMENT_VERSION, true);
                return;
            }
        }
    }

    // ==================== DASHBOARD ====================
    public static function render_dashboard() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $stats = self::get_all_stats();
        $financial = self::get_financial_stats();
        $pending = self::get_pending_actions();
        $timeline = self::get_timeline();

        B2B_Procurement_Admin::shell_start();
        ?>

        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title">داشبورد مدیریت</h1><p class="b2b-workspace-subtitle">نمای کلی سامانه خرید</p></div>
        </div>

        <!-- ===== بخش مالی ===== -->
        <div class="b2b-dash-section-title">&#128176; شاخص‌های مالی</div>
        <div class="b2b-card-grid b2b-card-grid-4 b2b-mb-6">
            <?php
            self::render_kpi_card('&#128176;', 'ارزش کل سفارشات', $financial['total_po_value'], 'تومان', 'primary', $financial['trend_po_total'], 'admin.php?page=b2b-pos');
            self::render_kpi_card('&#128200;', 'میانگین ارزش سفارش', $financial['avg_po_value'], 'تومان', 'info', $financial['trend_po_avg'], 'admin.php?page=b2b-pos');
            self::render_kpi_card('&#128221;', 'ارزش قراردادهای فعال', $financial['active_contract_value'], 'تومان', 'success', $financial['trend_contract'], 'admin.php?page=b2b-contracts');
            self::render_kpi_card('&#128179;', 'ارزش سفارشات تأیید شده', $financial['confirmed_po_value'], 'تومان', 'primary', $financial['trend_confirmed'], 'admin.php?page=b2b-pos');
            ?>
        </div>

        <!-- ===== بخش اجرایی ===== -->
        <div class="b2b-dash-section-title">&#128736; شاخص‌های اجرایی</div>
        <div class="b2b-card-grid b2b-card-grid-4 b2b-mb-6">
            <?php
            self::render_kpi_card('&#128220;', 'درخواست‌های باز', $stats['open_rfqs'], 'مورد', 'warning', null, 'admin.php?page=b2b-rfqs');
            self::render_kpi_card('&#128230;', 'سفارشات پیش‌نویس', $stats['po_draft'], 'مورد', 'primary', null, 'admin.php?page=b2b-pos');
            self::render_kpi_card('&#129309;', 'تامین‌کنندگان فعال', $stats['active_suppliers'], 'نفر', 'success', null, 'admin.php?page=b2b-suppliers');
            self::render_kpi_card('&#128276;', 'اعلان‌های خوانده نشده', $stats['unread_notifications'], 'مورد', 'warning', null, 'admin.php?page=b2b-notifications');
            ?>
        </div>

        <!-- ===== بخش آماری ===== -->
        <div class="b2b-dash-section-title">&#128202; شاخص‌های آماری</div>
        <div class="b2b-card-grid b2b-card-grid-4 b2b-mb-6">
            <?php
            self::render_kpi_card('&#128230;', 'کل محصولات', $stats['products'], 'محصول', 'primary', null, 'admin.php?page=b2b-products');
            self::render_kpi_card('&#128220;', 'کل درخواست‌ها', $stats['total_rfqs'], 'مورد', 'info', null, 'admin.php?page=b2b-rfqs');
            self::render_kpi_card('&#128176;', 'کل پیشنهادات', $stats['total_quotations'], 'مورد', 'success', null, 'admin.php?page=b2b-quotations');
            self::render_kpi_card('&#128221;', 'کل قراردادها', $stats['total_contracts'], 'قرارداد', 'primary', null, 'admin.php?page=b2b-contracts');
            ?>
        </div>

        <!-- ===== اقدامات منتظر ===== -->
        <?php if (!empty($pending)) : ?>
        <div class="b2b-card b2b-mb-6">
            <div class="b2b-card-header"><h2 class="b2b-card-title">&#9888; اقدامات منتظر</h2></div>
            <div class="b2b-card-body">
                <div class="b2b-dash-actions">
                    <?php foreach ($pending as $action) : ?>
                        <a href="<?php echo admin_url($action['url']); ?>" class="b2b-dash-action-item">
                            <div class="b2b-dash-action-icon b2b-dash-action-<?php echo $action['type']; ?>"><?php echo $action['icon']; ?></div>
                            <div class="b2b-dash-action-text">
                                <div class="b2b-dash-action-title"><?php echo esc_html($action['title']); ?></div>
                                <div class="b2b-dash-action-desc"><?php echo esc_html($action['desc']); ?></div>
                            </div>
                            <span class="b2b-badge b2b-badge-<?php echo $action['badge_type']; ?>"><?php echo self::to_persian_number($action['count']); ?></span>
                            <svg class="b2b-dash-action-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== نمودارهای وضعیت ===== -->
        <div class="b2b-card-grid b2b-card-grid-4 b2b-mb-6">
            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">وضعیت درخواست‌ها</h2></div>
                <div class="b2b-card-body">
                    <?php self::render_bar_chart(array(
                        array('label' => 'پیش‌نویس', 'value' => $stats['rfq_draft'], 'color' => '#6B7280'),
                        array('label' => 'ارسال شده', 'value' => $stats['rfq_submitted'], 'color' => '#7B2CBF'),
                        array('label' => 'تکمیل شده', 'value' => $stats['rfq_completed'], 'color' => '#22C55E'),
                        array('label' => 'بسته شده', 'value' => $stats['rfq_closed'], 'color' => '#EF4444'),
                    )); ?>
                </div>
            </div>
            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">وضعیت سفارشات</h2></div>
                <div class="b2b-card-body">
                    <?php self::render_bar_chart(array(
                        array('label' => 'پیش‌نویس', 'value' => $stats['po_draft'], 'color' => '#6B7280'),
                        array('label' => 'تأیید شده', 'value' => $stats['po_confirmed'], 'color' => '#22C55E'),
                        array('label' => 'لغو شده', 'value' => $stats['po_cancelled'], 'color' => '#EF4444'),
                    )); ?>
                </div>
            </div>
            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">وضعیت قراردادها</h2></div>
                <div class="b2b-card-body">
                    <?php self::render_bar_chart(array(
                        array('label' => 'پیش‌نویس', 'value' => $stats['contract_draft'], 'color' => '#6B7280'),
                        array('label' => 'فعال', 'value' => $stats['contract_active'], 'color' => '#22C55E'),
                        array('label' => 'بسته شده', 'value' => $stats['contract_closed'], 'color' => '#EF4444'),
                    )); ?>
                </div>
            </div>
            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">وضعیت پیشنهادات</h2></div>
                <div class="b2b-card-body">
                    <?php self::render_bar_chart(array(
                        array('label' => 'پیش‌نویس', 'value' => $stats['quotation_draft'], 'color' => '#6B7280'),
                        array('label' => 'ارسال شده', 'value' => $stats['quotation_submitted'], 'color' => '#7B2CBF'),
                        array('label' => 'انتخاب شده', 'value' => $stats['quotation_selected'], 'color' => '#22C55E'),
                        array('label' => 'رد شده', 'value' => $stats['quotation_rejected'], 'color' => '#EF4444'),
                    )); ?>
                </div>
            </div>
        </div>

        <!-- ===== آخرین فعالیت‌ها ===== -->
        <div class="b2b-card b2b-mb-6">
            <div class="b2b-card-header"><h2 class="b2b-card-title">&#128337; آخرین فعالیت‌ها</h2></div>
            <div class="b2b-card-body">
                <?php if (!empty($timeline)) : ?>
                <div class="b2b-dash-timeline">
                    <?php foreach ($timeline as $event) : ?>
                        <a href="<?php echo admin_url($event['url']); ?>" class="b2b-dash-timeline-item">
                            <div class="b2b-dash-timeline-dot" style="background: <?php echo $event['color']; ?>;"></div>
                            <div class="b2b-dash-timeline-content">
                                <span class="b2b-dash-timeline-module"><?php echo esc_html($event['module']); ?></span>
                                <span class="b2b-dash-timeline-desc"><?php echo esc_html($event['desc']); ?></span>
                            </div>
                            <span class="b2b-dash-timeline-time"><?php echo esc_html($event['time']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                    <div class="b2b-empty-state"><p>فعالیتی ثبت نشده</p></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== دکمه‌های سریع ===== -->
        <div class="b2b-card-grid b2b-card-grid-4">
            <a href="<?php echo admin_url('admin.php?page=b2b-rfq-add'); ?>" class="b2b-dash-quick-action">
                <span class="b2b-dash-quick-icon">&#128220;</span>
                <span>درخواست خرید جدید</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=b2b-quotations'); ?>" class="b2b-dash-quick-action">
                <span class="b2b-dash-quick-icon">&#128176;</span>
                <span>پیشنهادات در انتظار</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=b2b-pos'); ?>" class="b2b-dash-quick-action">
                <span class="b2b-dash-quick-icon">&#128230;</span>
                <span>سفارشات تأیید نشده</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=b2b-report-rfqs'); ?>" class="b2b-dash-quick-action">
                <span class="b2b-dash-quick-icon">&#128202;</span>
                <span>گزارشات</span>
            </a>
        </div>

        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== REPORTS ====================
    public static function render_rfq_report() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $search = sanitize_text_field(wp_unslash($_GET['search'] ?? ''));
        $status = sanitize_text_field(wp_unslash($_GET['status'] ?? ''));
        $date_from = sanitize_text_field(wp_unslash($_GET['date_from'] ?? ''));
        $date_to = sanitize_text_field(wp_unslash($_GET['date_to'] ?? ''));
        $page = max(1, intval($_GET['paged'] ?? 1));
        $args = array('search' => $search, 'status' => $status, 'per_page' => 20, 'page' => $page);
        $result = B2B_Rfq_DB::get_rfqs($args);
        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header"><div><h1 class="b2b-workspace-title">گزارش درخواست‌های خرید</h1></div></div>
        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">فیلترها</h2></div>
            <div class="b2b-card-body">
                <form method="get" class="b2b-flex b2b-gap-4" style="align-items:flex-end;">
                    <input type="hidden" name="page" value="b2b-report-rfqs" />
                    <div class="b2b-form-field" style="margin:0;"><label class="b2b-form-label">جستجو</label><input type="text" name="search" class="b2b-input" value="<?php echo esc_attr($search); ?>" /></div>
                    <div class="b2b-form-field" style="margin:0;"><label class="b2b-form-label">وضعیت</label><select name="status" class="b2b-select"><option value="">همه</option><option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>پیش‌نویس</option><option value="submitted" <?php echo $status === 'submitted' ? 'selected' : ''; ?>>ارسال شده</option><option value="quotation_completed" <?php echo $status === 'quotation_completed' ? 'selected' : ''; ?>>تکمیل شده</option><option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>بسته شده</option></select></div>
                    <button type="submit" class="b2b-btn b2b-btn-primary">فیلتر</button>
                </form>
            </div>
        </div>
        <?php
        self::render_report_table($result, 'b2b-rfq-detail', array('شماره', 'عنوان', 'وضعیت', 'تاریخ'), function($r) {
            return array($r->reference, $r->title, self::status_badge($r->status), $r->created_at);
        });
        B2B_Procurement_Admin::shell_end();
    }

    public static function render_po_report() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $search = sanitize_text_field(wp_unslash($_GET['search'] ?? ''));
        $status = sanitize_text_field(wp_unslash($_GET['status'] ?? ''));
        $page = max(1, intval($_GET['paged'] ?? 1));
        $args = array('search' => $search, 'status' => $status, 'per_page' => 20, 'page' => $page);
        $result = B2B_PO_DB::get_pos($args);
        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header"><div><h1 class="b2b-workspace-title">گزارش سفارشات خرید</h1></div></div>
        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">فیلترها</h2></div>
            <div class="b2b-card-body">
                <form method="get" class="b2b-flex b2b-gap-4" style="align-items:flex-end;">
                    <input type="hidden" name="page" value="b2b-report-pos" />
                    <div class="b2b-form-field" style="margin:0;"><label class="b2b-form-label">جستجو</label><input type="text" name="search" class="b2b-input" value="<?php echo esc_attr($search); ?>" /></div>
                    <div class="b2b-form-field" style="margin:0;"><label class="b2b-form-label">وضعیت</label><select name="status" class="b2b-select"><option value="">همه</option><option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>پیش‌نویس</option><option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>تأیید شده</option><option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>لغو شده</option></select></div>
                    <button type="submit" class="b2b-btn b2b-btn-primary">فیلتر</button>
                </form>
            </div>
        </div>
        <?php
        self::render_report_table($result, 'b2b-po-detail', array('شماره سفارش', 'تامین‌کننده', 'وضعیت', 'جمع کل', 'تاریخ'), function($r) {
            return array($r->po_number, $r->supplier_name, self::status_badge($r->status), number_format($r->grand_total) . ' تومان', $r->created_at);
        });
        B2B_Procurement_Admin::shell_end();
    }

    public static function render_contract_report() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $search = sanitize_text_field(wp_unslash($_GET['search'] ?? ''));
        $status = sanitize_text_field(wp_unslash($_GET['status'] ?? ''));
        $page = max(1, intval($_GET['paged'] ?? 1));
        $args = array('search' => $search, 'status' => $status, 'per_page' => 20, 'page' => $page);
        $result = B2B_Contract_DB::get_contracts($args);
        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header"><div><h1 class="b2b-workspace-title">گزارش قراردادها</h1></div></div>
        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">فیلترها</h2></div>
            <div class="b2b-card-body">
                <form method="get" class="b2b-flex b2b-gap-4" style="align-items:flex-end;">
                    <input type="hidden" name="page" value="b2b-report-contracts" />
                    <div class="b2b-form-field" style="margin:0;"><label class="b2b-form-label">جستجو</label><input type="text" name="search" class="b2b-input" value="<?php echo esc_attr($search); ?>" /></div>
                    <div class="b2b-form-field" style="margin:0;"><label class="b2b-form-label">وضعیت</label><select name="status" class="b2b-select"><option value="">همه</option><option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>پیش‌نویس</option><option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>فعال</option><option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>بسته شده</option></select></div>
                    <button type="submit" class="b2b-btn b2b-btn-primary">فیلتر</button>
                </form>
            </div>
        </div>
        <?php
        self::render_report_table($result, 'b2b-contract-detail', array('شماره قرارداد', 'عنوان', 'تامین‌کننده', 'وضعیت', 'ارزش'), function($c) {
            return array($c->contract_number, $c->title, $c->supplier_name, self::status_badge($c->status), number_format($c->contract_value) . ' تومان');
        });
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== HELPERS ====================
    private static function render_report_table($result, $detail_page, $headers, $row_callback) {
        if (empty($result['items'])) {
            echo '<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><p>داده‌ای یافت نشد</p></div></div></div>';
            return;
        }
        echo '<div class="b2b-card"><div class="b2b-card-body" style="padding:0;overflow-x:auto;"><table class="b2b-table"><thead><tr>';
        echo '<th>#</th>';
        foreach ($headers as $h) echo '<th>' . esc_html($h) . '</th>';
        echo '<th>عملیات</th>';
        echo '</tr></thead><tbody>';
        foreach ($result['items'] as $i => $item) {
            $row = $row_callback($item);
            $idx = ($result['page'] - 1) * $result['per_page'] + $i + 1;
            echo '<tr>';
            echo '<td>' . number_format($idx) . '</td>';
            foreach ($row as $cell) echo '<td>' . wp_kses_post($cell) . '</td>';
            echo '<td><a href="' . admin_url($detail_page . '&id=' . $item->id) . '" class="b2b-btn b2b-btn-sm b2b-btn-ghost">مشاهده</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div></div>';
        if ($result['pages'] > 1) {
            echo '<div class="b2b-table-pagination"><div class="b2b-pagination-info">صفحه ' . number_format($result['page']) . ' از ' . number_format($result['pages']) . '</div><div class="b2b-pagination-links">';
            for ($i = 1; $i <= $result['pages']; $i++) {
                $url = add_query_arg(array_merge($_GET, array('paged' => $i)));
                $cls = ($i === $result['page']) ? 'b2b-page-link b2b-page-active' : 'b2b-page-link';
                echo '<a href="' . esc_url($url) . '" class="' . $cls . '">' . number_format($i) . '</a>';
            }
            echo '</div></div>';
        }
    }

    private static function render_kpi_card($icon, $label, $value, $unit, $color, $trend = null, $link = '') {
        $formatted_value = self::to_persian_number(number_format($value));
        $trend_html = '';
        if ($trend && $trend['dir'] !== 'neutral') {
            $arrow = $trend['dir'] === 'up' ? '&#9650;' : '&#9660;';
            $cls = 'b2b-dash-trend-' . $trend['dir'];
            $trend_html = '<div class="b2b-dash-trend ' . $cls . '"><span>' . $arrow . '</span><span>' . self::to_persian_number($trend['pct']) . '%</span><span class="b2b-dash-trend-period">ماه گذشته</span></div>';
        }
        ?>
        <div class="b2b-kpi-card"<?php if ($link) echo ' style="cursor:pointer;" onclick="window.location=\'' . esc_url(admin_url($link)) . '\'"'; ?>>
            <div class="b2b-kpi-top">
                <div class="b2b-kpi-icon b2b-kpi-icon-<?php echo $color; ?>"><?php echo $icon; ?></div>
                <div class="b2b-kpi-title"><?php echo esc_html($label); ?></div>
                <?php if ($link) : ?>
                <a href="<?php echo admin_url($link); ?>" class="b2b-kpi-link" title="مشاهده" onclick="event.stopPropagation()">&#8592;</a>
                <?php endif; ?>
            </div>
            <div class="b2b-kpi-value">
                <span class="b2b-kpi-number"><?php echo $formatted_value; ?></span>
                <?php if ($unit) : ?>
                <span class="b2b-kpi-unit"><?php echo esc_html($unit); ?></span>
                <?php endif; ?>
            </div>
            <?php echo $trend_html; ?>
        </div>
        <?php
    }

    private static function render_bar_chart($items) {
        $max = max(array_column($items, 'value'));
        if ($max == 0) $max = 1;
        echo '<div style="display:flex;flex-direction:column;gap:14px;">';
        foreach ($items as $item) {
            $pct = ($item['value'] / $max) * 100;
            echo '<div>';
            echo '<div style="display:flex;justify-content:space-between;margin-bottom:5px;font-size:13px;">';
            echo '<span style="color:var(--b2b-text-secondary);">' . esc_html($item['label']) . '</span>';
            echo '<span style="font-weight:600;color:var(--b2b-text);">' . self::to_persian_number(number_format($item['value'])) . '</span>';
            echo '</div>';
            echo '<div style="height:10px;background:var(--b2b-border);border-radius:5px;overflow:hidden;">';
            echo '<div class="b2b-dash-chart-bar" style="height:100%;width:0%;background:' . $item['color'] . ';border-radius:5px;" data-width="' . $pct . '%"></div>';
            echo '</div></div>';
        }
        echo '</div>';
    }

    private static function status_badge($status) {
        $map = array(
            'draft' => array('پیش‌نویس', 'b2b-badge-default'),
            'submitted' => array('ارسال شده', 'b2b-badge-primary'),
            'quotation_completed' => array('تکمیل شده', 'b2b-badge-success'),
            'closed' => array('بسته شده', 'b2b-badge-danger'),
            'confirmed' => array('تأیید شده', 'b2b-badge-success'),
            'cancelled' => array('لغو شده', 'b2b-badge-danger'),
            'active' => array('فعال', 'b2b-badge-success'),
            'selected' => array('انتخاب شده', 'b2b-badge-success'),
            'rejected' => array('رد شده', 'b2b-badge-danger'),
        );
        $info = isset($map[$status]) ? $map[$status] : array('نامشخص', 'b2b-badge-default');
        return '<span class="b2b-badge ' . $info[1] . '">' . $info[0] . '</span>';
    }

    private static function to_persian_number($num) {
        $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        return str_replace(range(0, 9), $persian, $num);
    }

    private static function to_persian_date($date) {
        $months = array('ژانویه', 'فوریه', 'مارس', 'آوریل', 'مه', 'ژوئن', 'ژوئیه', 'اوت', 'سپتامبر', 'اکتبر', 'نوامبر', 'دسامبر');
        $d = new DateTime($date);
        return self::to_persian_number($d->format('j')) . ' ' . $months[(int)$d->format('n') - 1] . ' ' . self::to_persian_number($d->format('Y'));
    }

    private static function calc_trend($current, $previous) {
        if ($previous == 0) {
            return $current > 0 ? array('dir' => 'up', 'pct' => 100) : array('dir' => 'neutral', 'pct' => 0);
        }
        $diff = (($current - $previous) / $previous) * 100;
        $pct = (int) round(abs($diff));
        if ($pct > 999) $pct = 999;
        $dir = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'neutral');
        return array('dir' => $dir, 'pct' => $pct);
    }

    private static function get_system_health() {
        $items = array();
        global $wpdb;
        $db_ok = @$wpdb->get_var("SELECT 1");
        $items[] = array('label' => 'دیتابیس', 'type' => ($db_ok === '1') ? 'ok' : 'error');
        $items[] = array('label' => 'وردپرس ' . get_bloginfo('version'), 'type' => 'ok');
        $wc_ok = class_exists('WooCommerce');
        $items[] = array('label' => 'ووکامرس', 'type' => $wc_ok ? 'ok' : 'warn');
        $items[] = array('label' => 'PHP ' . PHP_VERSION, 'type' => 'ok');
        $ok_count = 0;
        $total = count($items);
        foreach ($items as $item) {
            if ($item['type'] === 'ok') $ok_count++;
        }
        $summary = ($ok_count === $total) ? 'همه سیستم‌ها فعال' : $ok_count . ' از ' . $total . ' سرویس فعال';
        return array('items' => $items, 'summary' => $summary);
    }

    private static function get_financial_stats() {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $total_po = (float) $wpdb->get_var("SELECT COALESCE(SUM(grand_total), 0) FROM {$prefix}b2b_purchase_orders WHERE deleted_at IS NULL");
        $po_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}b2b_purchase_orders WHERE deleted_at IS NULL");
        $avg_po = ($po_count > 0) ? ($total_po / $po_count) : 0;
        $active_contract = (float) $wpdb->get_var("SELECT COALESCE(SUM(contract_value), 0) FROM {$prefix}b2b_contracts WHERE status = 'active' AND deleted_at IS NULL");
        $current_suppliers = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}b2b_suppliers WHERE status = 1 AND deleted_at IS NULL");

        $prev_total = (float) $wpdb->get_var("SELECT COALESCE(SUM(grand_total), 0) FROM {$prefix}b2b_purchase_orders WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
        $prev_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}b2b_purchase_orders WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
        $prev_avg = ($prev_count > 0) ? ($prev_total / $prev_count) : 0;
        $prev_contract = (float) $wpdb->get_var("SELECT COALESCE(SUM(contract_value), 0) FROM {$prefix}b2b_contracts WHERE status = 'active' AND deleted_at IS NULL AND activated_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
        $prev_suppliers = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}b2b_suppliers WHERE status = 1 AND deleted_at IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");

        $confirmed_po = (float) $wpdb->get_var("SELECT COALESCE(SUM(grand_total), 0) FROM {$prefix}b2b_purchase_orders WHERE status = 'confirmed' AND deleted_at IS NULL");
        $prev_confirmed = (float) $wpdb->get_var("SELECT COALESCE(SUM(grand_total), 0) FROM {$prefix}b2b_purchase_orders WHERE status = 'confirmed' AND deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");

        return array(
            'total_po_value' => $total_po,
            'avg_po_value' => $avg_po,
            'active_contract_value' => $active_contract,
            'confirmed_po_value' => $confirmed_po,
            'trend_po_total' => self::calc_trend($total_po, $prev_total),
            'trend_po_avg' => self::calc_trend($avg_po, $prev_avg),
            'trend_contract' => self::calc_trend($active_contract, $prev_contract),
            'trend_suppliers' => self::calc_trend($current_suppliers, $prev_suppliers),
            'trend_confirmed' => self::calc_trend($confirmed_po, $prev_confirmed),
        );
    }

    private static function get_pending_actions() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $actions = array();

        $orphan_rfqs = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}b2b_rfqs r WHERE r.status = 'submitted' AND r.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM {$prefix}b2b_quotations q WHERE q.rfq_id = r.id AND q.deleted_at IS NULL)");
        if ($orphan_rfqs > 0) {
            $actions[] = array('icon' => '&#128220;', 'title' => 'درخواست‌های بدون پیشنهاد', 'desc' => 'درخواست‌هایی که هنوز پیشنهادی دریافت نکرده‌اند', 'count' => $orphan_rfqs, 'url' => 'admin.php?page=b2b-rfqs&status=submitted', 'type' => 'warning', 'badge_type' => 'warning');
        }

        $draft_pos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}b2b_purchase_orders WHERE status = 'draft' AND deleted_at IS NULL");
        if ($draft_pos > 0) {
            $actions[] = array('icon' => '&#128230;', 'title' => 'سفارشات پیش‌نویس', 'desc' => 'سفارشاتی که نیاز به تأیید دارند', 'count' => $draft_pos, 'url' => 'admin.php?page=b2b-pos', 'type' => 'primary', 'badge_type' => 'primary');
        }

        $expiring = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}b2b_contracts WHERE status = 'active' AND deleted_at IS NULL AND end_date IS NOT NULL AND end_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND end_date >= NOW()");
        if ($expiring > 0) {
            $actions[] = array('icon' => '&#9200;', 'title' => 'قراردادهای رو به انقضا', 'desc' => 'قراردادهایی که ظرف ۳۰ روز آینده منقضی می‌شوند', 'count' => $expiring, 'url' => 'admin.php?page=b2b-contracts', 'type' => 'danger', 'badge_type' => 'danger');
        }

        $user_id = get_current_user_id();
        $unread = B2B_Notification_DB::get_unread_count($user_id);
        if ($unread > 0) {
            $actions[] = array('icon' => '&#128276;', 'title' => 'اعلان‌های خوانده نشده', 'desc' => 'شما ' . self::to_persian_number($unread) . ' اعلان خوانده نشده دارید', 'count' => $unread, 'url' => 'admin.php?page=b2b-notifications', 'type' => 'warning', 'badge_type' => 'warning');
        }

        return $actions;
    }

    private static function get_timeline() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $events = array();

        $rfqs = $wpdb->get_results("SELECT id, reference, title, status, created_at FROM {$prefix}b2b_rfqs WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 3");
        foreach ($rfqs as $r) {
            $events[] = array('module' => 'درخواست خرید', 'desc' => $r->reference . ' — ' . mb_substr($r->title, 0, 25), 'url' => 'admin.php?page=b2b-rfq-detail&id=' . $r->id, 'time' => self::time_ago($r->created_at), 'color' => '#7B2CBF');
        }

        $pos = $wpdb->get_results("SELECT id, po_number, supplier_name, status, created_at FROM {$prefix}b2b_purchase_orders WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 3");
        foreach ($pos as $p) {
            $events[] = array('module' => 'سفارش خرید', 'desc' => $p->po_number . ' — ' . $p->supplier_name, 'url' => 'admin.php?page=b2b-po-detail&id=' . $p->id, 'time' => self::time_ago($p->created_at), 'color' => '#22C55E');
        }

        $contracts = $wpdb->get_results("SELECT id, contract_number, title, created_at FROM {$prefix}b2b_contracts WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 2");
        foreach ($contracts as $c) {
            $events[] = array('module' => 'قرارداد', 'desc' => $c->contract_number . ' — ' . mb_substr($c->title, 0, 25), 'url' => 'admin.php?page=b2b-contract-detail&id=' . $c->id, 'time' => self::time_ago($c->created_at), 'color' => '#3B82F6');
        }

        usort($events, function ($a, $b) { return strcmp($b['time'], $a['time']); });
        return array_slice($events, 0, 8);
    }

    private static function time_ago($datetime) {
        $now = new DateTime();
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        if ($diff->y > 0) return self::to_persian_number($diff->y) . ' سال پیش';
        if ($diff->m > 0) return self::to_persian_number($diff->m) . ' ماه پیش';
        if ($diff->d > 0) return self::to_persian_number($diff->d) . ' روز پیش';
        if ($diff->h > 0) return self::to_persian_number($diff->h) . ' ساعت پیش';
        if ($diff->i > 0) return self::to_persian_number($diff->i) . ' دقیقه پیش';
        return 'همین الان';
    }

    private static function get_all_stats() {
        global $wpdb;
        $products = (int) wp_count_posts('product')->publish;
        $active_suppliers = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}b2b_suppliers WHERE status = 1 AND deleted_at IS NULL");
        $rfq_stats = B2B_Rfq_DB::get_stats();
        $quotation_stats = B2B_Quotation_DB::get_stats();
        $po_stats = B2B_PO_DB::get_stats();
        $contract_stats = B2B_Contract_DB::get_stats();
        $unread = B2B_Notification_DB::get_unread_count(get_current_user_id());
        return array(
            'products' => $products, 'active_suppliers' => $active_suppliers,
            'total_rfqs' => $rfq_stats['total'], 'open_rfqs' => $rfq_stats['submitted'],
            'rfq_draft' => $rfq_stats['draft'], 'rfq_submitted' => $rfq_stats['submitted'],
            'rfq_completed' => isset($rfq_stats['quotation_completed']) ? $rfq_stats['quotation_completed'] : 0,
            'rfq_closed' => $rfq_stats['closed'],
            'total_quotations' => $quotation_stats['total'], 'quotation_draft' => $quotation_stats['draft'],
            'quotation_submitted' => $quotation_stats['submitted'], 'quotation_selected' => $quotation_stats['selected'],
            'quotation_rejected' => $quotation_stats['rejected'], 'total_pos' => $po_stats['total'],
            'po_draft' => $po_stats['draft'], 'po_confirmed' => $po_stats['confirmed'],
            'po_cancelled' => $po_stats['cancelled'], 'total_contracts' => $contract_stats['total'],
            'contract_draft' => $contract_stats['draft'], 'contract_active' => $contract_stats['active'],
            'contract_closed' => $contract_stats['closed'], 'unread_notifications' => $unread,
        );
    }
}
