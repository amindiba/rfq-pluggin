<?php
defined('ABSPATH') || exit;

class B2B_PO_Admin {

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function enqueue_assets($hook) {
        $pages = array('b2b-pos', 'b2b-po-detail');
        foreach ($pages as $p) {
            if (strpos($hook, $p) !== false) {
                wp_enqueue_media();
                wp_enqueue_script('b2b-admin-js', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), B2B_PROCUREMENT_VERSION, true);
                wp_localize_script('b2b-admin-js', 'b2bProcurement', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
                    'version' => B2B_PROCUREMENT_VERSION,
                ));
                wp_enqueue_script('b2b-po', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/po.js', array('b2b-admin-js'), B2B_PROCUREMENT_VERSION, true);
                return;
            }
        }
    }

    // ==================== LIST ====================
    public static function render_list() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title">سفارشات خرید</h1><p class="b2b-workspace-subtitle">مدیریت سفارشات خرید</p></div>
        </div>
        <div class="b2b-toolbar">
            <div class="b2b-toolbar-left">
                <input type="text" id="po-search" class="b2b-search-input" placeholder="جستجو..." style="max-width:300px;" />
                <select id="po-status" class="b2b-select" style="max-width:150px;">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="draft">پیش‌نویس</option>
                    <option value="confirmed">تأیید شده</option>
                    <option value="cancelled">لغو شده</option>
                </select>
            </div>
            <div class="b2b-toolbar-right"><span id="po-count" class="b2b-text-muted"></span></div>
        </div>
        <div id="po-table-container"></div>
        <div id="po-pagination"></div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== DETAIL ====================
    public static function render_detail() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $id = intval($_GET['id'] ?? 0);
        $po = B2B_PO_DB::get_po($id);
        if (!$po) wp_die('سفارش خرید یافت نشد');

        $po->items = B2B_PO_DB::get_items($id);
        $status_map = array('draft' => array('پیش‌نویس', 'b2b-badge-default'), 'confirmed' => array('تأیید شده', 'b2b-badge-success'), 'cancelled' => array('لغو شده', 'b2b-badge-danger'));
        $status_info = $status_map[$po->status] ?? array('نامشخص', 'b2b-badge-default');
        $can_edit = $po->status === 'draft';

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title">سفارش خرید <?php echo esc_html($po->po_number); ?></h1></div>
            <div class="b2b-workspace-actions">
                <?php if ($can_edit) : ?>
                    <button type="button" class="b2b-btn b2b-btn-primary" onclick="B2BPO.confirm(<?php echo $id; ?>)">تأیید سفارش</button>
                    <button type="button" class="b2b-btn b2b-btn-danger" onclick="B2BPO.cancel(<?php echo $id; ?>)">لغو سفارش</button>
                <?php endif; ?>
                <?php if ($po->status === 'confirmed' || $po->status === 'cancelled') : ?>
                    <button type="button" class="b2b-btn b2b-btn-danger" onclick="B2BPO.cancel(<?php echo $id; ?>)">لغو سفارش</button>
                <?php endif; ?>
                <a href="<?php echo admin_url('admin.php?page=b2b-pos'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>

        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات سفارش</h2></div>
            <div class="b2b-card-body">
                <div class="b2b-card-grid b2b-card-grid-3">
                    <div><strong>شماره سفارش:</strong> <?php echo esc_html($po->po_number); ?></div>
                    <div><strong>وضعیت:</strong> <span class="b2b-badge <?php echo $status_info[1]; ?>"><?php echo $status_info[0]; ?></span></div>
                    <div><strong>تامین‌کننده:</strong> <?php echo esc_html($po->supplier_name); ?></div>
                    <div><strong>شماره درخواست:</strong> <?php echo esc_html($po->rfq_reference); ?></div>
                    <div><strong>شماره پیشنهاد:</strong> <?php echo esc_html($po->quotation_reference); ?></div>
                    <div><strong>جمع کل:</strong> <?php echo number_format($po->grand_total); ?> تومان</div>
                    <div><strong>تاریخ ایجاد:</strong> <?php echo esc_html($po->created_at); ?></div>
                    <div><strong>تاریخ تأیید:</strong> <?php echo esc_html($po->confirmed_at ?: '-'); ?></div>
                    <div><strong>تاریخ لغو:</strong> <?php echo esc_html($po->cancelled_at ?: '-'); ?></div>
                </div>
                <?php if (!empty($po->notes)) : ?>
                    <div style="margin-top:16px;"><strong>یادداشت:</strong><br><?php echo nl2br(esc_html($po->notes)); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">محصولات سفارش</h2></div>
            <div class="b2b-card-body">
                <?php if (!empty($po->items)) : ?>
                <table class="b2b-table">
                    <thead><tr><th>محصول</th><th>کد</th><th>قیمت واحد</th><th>تعداد</th><th>جمع</th><th>زمان تحویل</th><th>یادداشت</th></tr></thead>
                    <tbody>
                    <?php foreach ($po->items as $item) : ?>
                        <tr>
                            <td><?php echo esc_html($item->product_name); ?></td>
                            <td><span class="b2b-badge b2b-badge-primary"><?php echo esc_html($item->product_sku); ?></span></td>
                            <td><?php echo number_format($item->unit_price); ?> تومان</td>
                            <td><?php echo esc_html($item->quantity); ?></td>
                            <td><?php echo number_format($item->line_total); ?> تومان</td>
                            <td><?php echo esc_html($item->delivery_days); ?> روز</td>
                            <td><?php echo esc_html($item->supplier_note); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                    <p>محصولی ثبت نشده.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
