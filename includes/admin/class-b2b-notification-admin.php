<?php
defined('ABSPATH') || exit;

class B2B_Notification_Admin {

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function enqueue_assets($hook) {
        $pages = array('b2b-notifications');
        foreach ($pages as $p) {
            if (strpos($hook, $p) !== false) {
                wp_enqueue_media();
                wp_enqueue_script('b2b-admin-js', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), B2B_PROCUREMENT_VERSION, true);
                wp_localize_script('b2b-admin-js', 'b2bProcurement', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
                    'version' => B2B_PROCUREMENT_VERSION,
                ));
                wp_enqueue_script('b2b-notification', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/notification.js', array('b2b-admin-js'), B2B_PROCUREMENT_VERSION, true);
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
            <div><h1 class="b2b-workspace-title">اعلان‌ها</h1><p class="b2b-workspace-subtitle">مرکز اعلان‌های سامانه</p></div>
            <div class="b2b-workspace-actions">
                <button type="button" class="b2b-btn b2b-btn-secondary" onclick="B2BNotification.markAllRead()">خواندن همه</button>
            </div>
        </div>
        <div class="b2b-toolbar">
            <div class="b2b-toolbar-left">
                <select id="notification-filter" class="b2b-select" style="max-width:150px;">
                    <option value="">همه</option>
                    <option value="0">خوانده نشده</option>
                    <option value="1">خوانده شده</option>
                </select>
                <select id="notification-type" class="b2b-select" style="max-width:150px;">
                    <option value="">همه انواع</option>
                    <option value="info">اطلاعات</option>
                    <option value="success">موفقیت</option>
                    <option value="warning">هشدار</option>
                </select>
            </div>
            <div class="b2b-toolbar-right"><span id="notification-count" class="b2b-text-muted"></span></div>
        </div>
        <div id="notification-table-container"></div>
        <div id="notification-pagination"></div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== DETAIL ====================
    public static function render_detail() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $id = intval($_GET['id'] ?? 0);
        $user_id = get_current_user_id();
        $notification = B2B_Notification_DB::get_notification($id, $user_id);

        if (!$notification) wp_die('اعلان یافت نشد');

        // Mark as read
        if (!$notification->is_read) {
            B2B_Notification_DB::mark_read($id, $user_id);
        }

        $type_map = array('info' => array('اطلاعات', 'b2b-badge-info', '&#128336;'), 'success' => array('موفقیت', 'b2b-badge-success', '&#9989;'), 'warning' => array('هشدار', 'b2b-badge-warning', '&#9888;'));
        $type_info = $type_map[$notification->type] ?? array('نامشخص', 'b2b-badge-default', '&#10067;');

        // Related URL
        $related_url = '';
        if ($notification->related_module && $notification->related_id) {
            $urls = array(
                'rfq' => 'admin.php?page=b2b-rfq-detail&id=',
                'quotation' => 'admin.php?page=b2b-quotation-detail&id=',
                'purchase_order' => 'admin.php?page=b2b-po-detail&id=',
                'contract' => 'admin.php?page=b2b-contract-detail&id=',
            );
            if (isset($urls[$notification->related_module])) {
                $related_url = admin_url($urls[$notification->related_module] . $notification->related_id);
            }
        }

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title">جزئیات اعلان</h1></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-notifications'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>

        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title"><?php echo esc_html($notification->title); ?></h2></div>
            <div class="b2b-card-body">
                <div class="b2b-card-grid b2b-card-grid-3">
                    <div><strong>نوع:</strong> <span class="b2b-badge <?php echo $type_info[1]; ?>"><?php echo $type_info[0]; ?></span></div>
                    <div><strong>تاریخ:</strong> <?php echo esc_html($notification->created_at); ?></div>
                    <div><strong>وضعیت:</strong> <?php echo $notification->is_read ? '<span class="b2b-badge b2b-badge-default">خوانده شده</span>' : '<span class="b2b-badge b2b-badge-primary">خوانده نشده</span>'; ?></div>
                </div>
                <div style="margin-top:16px;padding:16px;background:var(--b2b-gray-50);border-radius:8px;">
                    <?php echo nl2br(esc_html($notification->message)); ?>
                </div>
                <?php if ($related_url) : ?>
                    <div style="margin-top:16px;">
                        <a href="<?php echo esc_url($related_url); ?>" class="b2b-btn b2b-btn-primary">مشاهده مورد مرتبط</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
