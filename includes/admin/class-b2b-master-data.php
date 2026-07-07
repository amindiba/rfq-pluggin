<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Master_Data {

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function enqueue_assets($hook) {
        $pages = array('b2b-master-data', 'b2b-measurement-units');
        $found = false;
        foreach ($pages as $p) { if (strpos($hook, $p) !== false) { $found = true; break; } }
        if (!$found) return;
        wp_enqueue_script('b2b-master-data', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/master-data.js', array('b2b-admin'), B2B_PROCUREMENT_VERSION, true);
    }

    // ==================== DASHBOARD ====================
    public static function render_dashboard() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $stats = B2B_Procurement_Master_Data_DB::get_unit_stats();

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title">اطلاعات پایه</h1><p class="b2b-workspace-subtitle">مدیریت اطلاعات مرجع سامانه</p></div>
        </div>
        <div class="b2b-card-grid b2b-card-grid-4 b2b-mb-6">
            <div class="b2b-stat-card"><div class="b2b-stat-icon b2b-stat-icon-primary">&#128202;</div><div class="b2b-stat-value"><?php echo $stats['total']; ?></div><div class="b2b-stat-label">کل واحدها</div></div>
            <div class="b2b-stat-card"><div class="b2b-stat-icon b2b-stat-icon-success">&#9989;</div><div class="b2b-stat-value"><?php echo $stats['active']; ?></div><div class="b2b-stat-label">فعال</div></div>
            <div class="b2b-stat-card"><div class="b2b-stat-icon b2b-stat-icon-warning">&#10060;</div><div class="b2b-stat-value"><?php echo $stats['inactive']; ?></div><div class="b2b-stat-label">غیرفعال</div></div>
            <div class="b2b-stat-card"><div class="b2b-stat-icon b2b-stat-icon-danger">&#128465;</div><div class="b2b-stat-value"><?php echo $stats['deleted']; ?></div><div class="b2b-stat-label">حذف شده</div></div>
        </div>
        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">دسترسی سریع</h2></div>
            <div class="b2b-card-body">
                <div class="b2b-flex b2b-gap-4">
                    <a href="<?php echo admin_url('admin.php?page=b2b-measurement-units'); ?>" class="b2b-btn b2b-btn-primary">واحدهای اندازه‌گیری</a>
                    <a href="<?php echo admin_url('admin.php?page=b2b-geography'); ?>" class="b2b-btn b2b-btn-secondary">جغرافیای ایران</a>
                </div>
            </div>
        </div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== UNITS ====================
    public static function render_units() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title">واحدهای اندازه‌گیری</h1><p class="b2b-workspace-subtitle">مدیریت واحدهای اندازه‌گیری سامانه</p></div>
            <div class="b2b-workspace-actions">
                <button type="button" class="b2b-btn b2b-btn-primary" onclick="B2BUnits.openCreate()">&#10010; افزودن واحد</button>
            </div>
        </div>
        <div class="b2b-toolbar">
            <div class="b2b-toolbar-left">
                <input type="text" id="units-search" class="b2b-search-input" placeholder="جستجو..." style="max-width:300px;" />
                <select id="units-status-filter" class="b2b-select" style="max-width:150px;"><option value="">همه وضعیت‌ها</option><option value="active">فعال</option><option value="inactive">غیرفعال</option></select>
            </div>
            <div class="b2b-toolbar-right"><span id="units-count" class="b2b-text-muted"></span></div>
        </div>
        <div id="units-table-container"></div>
        <div id="units-pagination"></div>
        <?php
        self::render_unit_modals();
        B2B_Procurement_Admin::shell_end();
    }

    private static function render_unit_modals() {
        ?>
        <div id="unit-create-modal" class="b2b-modal b2b-modal-md"><div class="b2b-modal-overlay"></div><div class="b2b-modal-dialog"><div class="b2b-modal-header"><h3 class="b2b-modal-title">افزودن واحد جدید</h3><button type="button" class="b2b-modal-close">&times;</button></div><div class="b2b-modal-body"><form id="unit-create-form" class="b2b-form"><div class="b2b-form-field"><label class="b2b-form-label">عنوان <span class="b2b-required">*</span></label><input type="text" name="title" class="b2b-input" required /></div><div class="b2b-form-field"><label class="b2b-form-label">نام اختصاری <span class="b2b-required">*</span></label><input type="text" name="short_name" class="b2b-input" required /></div><div class="b2b-form-field"><label class="b2b-form-label">توضیحات</label><textarea name="description" class="b2b-textarea" rows="3"></textarea></div><div class="b2b-form-row"><div class="b2b-form-field"><label class="b2b-form-label">ترتیب</label><input type="number" name="sort_order" class="b2b-input" value="0" /></div><div class="b2b-form-field"><label class="b2b-form-label">وضعیت</label><select name="status" class="b2b-select"><option value="active">فعال</option><option value="inactive">غیرفعال</option></select></div></div></form></div><div class="b2b-modal-footer"><button type="button" class="b2b-btn b2b-btn-secondary b2b-modal-cancel">انصراف</button><button type="button" class="b2b-btn b2b-btn-primary" onclick="B2BUnits.saveCreate()">ذخیره</button></div></div></div>
        <div id="unit-edit-modal" class="b2b-modal b2b-modal-md"><div class="b2b-modal-overlay"></div><div class="b2b-modal-dialog"><div class="b2b-modal-header"><h3 class="b2b-modal-title">ویرایش واحد</h3><button type="button" class="b2b-modal-close">&times;</button></div><div class="b2b-modal-body"><form id="unit-edit-form" class="b2b-form"><input type="hidden" name="unit_id" /><div class="b2b-form-field"><label class="b2b-form-label">عنوان <span class="b2b-required">*</span></label><input type="text" name="title" class="b2b-input" required /></div><div class="b2b-form-field"><label class="b2b-form-label">نام اختصاری <span class="b2b-required">*</span></label><input type="text" name="short_name" class="b2b-input" required /></div><div class="b2b-form-field"><label class="b2b-form-label">توضیحات</label><textarea name="description" class="b2b-textarea" rows="3"></textarea></div><div class="b2b-form-row"><div class="b2b-form-field"><label class="b2b-form-label">ترتیب</label><input type="number" name="sort_order" class="b2b-input" value="0" /></div><div class="b2b-form-field"><label class="b2b-form-label">وضعیت</label><select name="status" class="b2b-select"><option value="active">فعال</option><option value="inactive">غیرفعال</option></select></div></div></form></div><div class="b2b-modal-footer"><button type="button" class="b2b-btn b2b-btn-secondary b2b-modal-cancel">انصراف</button><button type="button" class="b2b-btn b2b-btn-primary" onclick="B2BUnits.saveEdit()">ذخیره تغییرات</button></div></div></div>
        <div id="unit-delete-modal" class="b2b-modal b2b-modal-sm"><div class="b2b-modal-overlay"></div><div class="b2b-modal-dialog"><div class="b2b-modal-header"><h3 class="b2b-modal-title">تأیید حذف</h3><button type="button" class="b2b-modal-close">&times;</button></div><div class="b2b-modal-body"><p>آیا از حذف این واحد اطمینان دارید؟</p><input type="hidden" id="delete-unit-id" /></div><div class="b2b-modal-footer"><button type="button" class="b2b-btn b2b-btn-secondary b2b-modal-cancel">انصراف</button><button type="button" class="b2b-btn b2b-btn-danger" onclick="B2BUnits.confirmDel()">حذف</button></div></div></div>
        <?php
    }
}
