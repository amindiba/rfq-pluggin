<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Geography {

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function enqueue_assets($hook) {
        $pages = array('b2b-geography', 'b2b-provinces', 'b2b-cities', 'b2b-industrial-towns');
        $found = false;
        foreach ($pages as $p) { if (strpos($hook, $p) !== false) { $found = true; break; } }
        if (!$found) return;
        wp_enqueue_script('b2b-geography', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/geography.js', array('b2b-admin'), B2B_PROCUREMENT_VERSION, true);
    }

    // ==================== DASHBOARD ====================
    public static function render_dashboard() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $ps = B2B_Procurement_Geography_DB::get_province_stats();
        $cs = B2B_Procurement_Geography_DB::get_city_stats();

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header"><div><h1 class="b2b-workspace-title">جغرافیای ایران</h1><p class="b2b-workspace-subtitle">مدیریت استان‌ها و شهرهای ایران</p></div></div>
        <div class="b2b-card-grid b2b-card-grid-4 b2b-mb-6">
            <div class="b2b-stat-card"><div class="b2b-stat-icon b2b-stat-icon-primary">&#127961;</div><div class="b2b-stat-value"><?php echo $ps['total']; ?></div><div class="b2b-stat-label">استان</div></div>
            <div class="b2b-stat-card"><div class="b2b-stat-icon b2b-stat-icon-success">&#127961;</div><div class="b2b-stat-value"><?php echo $ps['active']; ?></div><div class="b2b-stat-label">استان فعال</div></div>
            <div class="b2b-stat-card"><div class="b2b-stat-icon b2b-stat-icon-info">&#127963;</div><div class="b2b-stat-value"><?php echo $cs['total']; ?></div><div class="b2b-stat-label">شهر</div></div>
            <div class="b2b-stat-card"><div class="b2b-stat-icon b2b-stat-icon-success">&#127963;</div><div class="b2b-stat-value"><?php echo $cs['active']; ?></div><div class="b2b-stat-label">شهر فعال</div></div>
        </div>
        <div class="b2b-card"><div class="b2b-card-header"><h2 class="b2b-card-title">دسترسی سریع</h2></div><div class="b2b-card-body"><div class="b2b-flex b2b-gap-4">
            <a href="<?php echo admin_url('admin.php?page=b2b-provinces'); ?>" class="b2b-btn b2b-btn-primary">مدیریت استان‌ها</a>
            <a href="<?php echo admin_url('admin.php?page=b2b-cities'); ?>" class="b2b-btn b2b-btn-secondary">مدیریت شهرها</a>
            <button type="button" class="b2b-btn b2b-btn-secondary" onclick="B2BGeo.openImportModal()">&#128229; ورود CSV</button>
        </div></div></div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== PROVINCES ====================
    public static function render_provinces() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header"><div><h1 class="b2b-workspace-title">استان‌ها</h1><p class="b2b-workspace-subtitle">مدیریت استان‌های ایران</p></div>
            <div class="b2b-workspace-actions"><button type="button" class="b2b-btn b2b-btn-primary" onclick="B2BGeo.openCreate()">&#10010; افزودن استان</button></div>
        </div>
        <div class="b2b-toolbar"><div class="b2b-toolbar-left">
            <input type="text" id="geo-search" class="b2b-search-input" placeholder="جستجو..." style="max-width:300px;" />
            <select id="geo-status" class="b2b-select" style="max-width:150px;"><option value="">همه وضعیت‌ها</option><option value="active">فعال</option><option value="inactive">غیرفعال</option></select>
        </div><div class="b2b-toolbar-right"><span id="geo-count" class="b2b-text-muted"></span>
            <button type="button" class="b2b-btn b2b-btn-secondary" onclick="B2BGeo.openImportModal()">&#128229; ورود CSV</button>
            <button type="button" class="b2b-btn b2b-btn-secondary" onclick="B2BGeo.exportCSV('provinces')">&#128230; خروجی</button>
        </div></div>
        <div id="geo-table-container"></div><div id="geo-pagination"></div>
        <?php self::render_modals('province'); B2B_Procurement_Admin::shell_end();
    }

    // ==================== CITIES ====================
    public static function render_cities() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $provinces = B2B_Procurement_Geography_DB::get_provinces(array('per_page' => 999, 'status' => 'active'));

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header"><div><h1 class="b2b-workspace-title">شهرها</h1><p class="b2b-workspace-subtitle">مدیریت شهرهای ایران</p></div>
            <div class="b2b-workspace-actions"><button type="button" class="b2b-btn b2b-btn-primary" onclick="B2BGeo.openCreate('city')">&#10010; افزودن شهر</button></div>
        </div>
        <div class="b2b-toolbar"><div class="b2b-toolbar-left">
            <input type="text" id="geo-search" class="b2b-search-input" placeholder="جستجو..." style="max-width:300px;" />
            <select id="geo-status" class="b2b-select" style="max-width:150px;"><option value="">همه وضعیت‌ها</option><option value="active">فعال</option><option value="inactive">غیرفعال</option></select>
            <select id="geo-province" class="b2b-select" style="max-width:200px;"><option value="">همه استان‌ها</option><?php foreach ($provinces['items'] as $p) : ?><option value="<?php echo $p->id; ?>"><?php echo esc_html($p->name_fa); ?></option><?php endforeach; ?></select>
        </div><div class="b2b-toolbar-right"><span id="geo-count" class="b2b-text-muted"></span>
            <button type="button" class="b2b-btn b2b-btn-secondary" onclick="B2BGeo.openImportModal()">&#128229; ورود CSV</button>
            <button type="button" class="b2b-btn b2b-btn-secondary" onclick="B2BGeo.exportCSV('cities')">&#128230; خروجی</button>
        </div></div>
        <div id="geo-table-container"></div><div id="geo-pagination"></div>
        <?php self::render_modals('city'); B2B_Procurement_Admin::shell_end();
    }

    // ==================== MODALS ====================
    private static function render_modals($type) {
        $is_province = ($type === 'province');
        $title = $is_province ? 'استان' : 'شهر';
        $provinces_data = B2B_Procurement_Geography_DB::get_provinces(array('per_page' => 999, 'status' => 'active'));
        $provinces_items = $provinces_data['items'];
        ?>
        <div id="geo-create-modal" class="b2b-modal b2b-modal-md"><div class="b2b-modal-overlay"></div><div class="b2b-modal-dialog"><div class="b2b-modal-header"><h3 class="b2b-modal-title">افزودن <?php echo $title; ?> جدید</h3><button type="button" class="b2b-modal-close">&times;</button></div><div class="b2b-modal-body"><form id="geo-create-form" class="b2b-form">
        <?php if (!$is_province) : ?><div class="b2b-form-field"><label class="b2b-form-label">استان <span class="b2b-required">*</span></label><select name="province_id" class="b2b-select" required><option value="">انتخاب استان</option><?php foreach ($provinces_items as $p) : ?><option value="<?php echo $p->id; ?>"><?php echo esc_html($p->name_fa); ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <div class="b2b-form-field"><label class="b2b-form-label">نام فارسی <span class="b2b-required">*</span></label><input type="text" name="name_fa" class="b2b-input" required /></div>
        <div class="b2b-form-field"><label class="b2b-form-label">نام انگلیسی <span class="b2b-required">*</span></label><input type="text" name="name_en" class="b2b-input" required /><p class="b2b-form-desc">فقط حروف انگلیسی</p></div>
        <div class="b2b-form-row"><div class="b2b-form-field"><label class="b2b-form-label">کد <span class="b2b-required">*</span></label><input type="text" name="code" class="b2b-input" required /></div><div class="b2b-form-field"><label class="b2b-form-label">ترتیب</label><input type="number" name="sort_order" class="b2b-input" value="0" /></div></div>
        <div class="b2b-form-field"><label class="b2b-form-label">وضعیت</label><select name="status" class="b2b-select"><option value="active">فعال</option><option value="inactive">غیرفعال</option></select></div>
        </form></div><div class="b2b-modal-footer"><button type="button" class="b2b-btn b2b-btn-secondary b2b-modal-cancel">انصراف</button><button type="button" class="b2b-btn b2b-btn-primary" onclick="B2BGeo.saveCreate()">ذخیره</button></div></div></div>

        <div id="geo-edit-modal" class="b2b-modal b2b-modal-md"><div class="b2b-modal-overlay"></div><div class="b2b-modal-dialog"><div class="b2b-modal-header"><h3 class="b2b-modal-title">ویرایش <?php echo $title; ?></h3><button type="button" class="b2b-modal-close">&times;</button></div><div class="b2b-modal-body"><form id="geo-edit-form" class="b2b-form"><input type="hidden" name="item_id" />
        <?php if (!$is_province) : ?><div class="b2b-form-field"><label class="b2b-form-label">استان <span class="b2b-required">*</span></label><select name="province_id" class="b2b-select" required><option value="">انتخاب استان</option><?php foreach ($provinces_items as $p) : ?><option value="<?php echo $p->id; ?>"><?php echo esc_html($p->name_fa); ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <div class="b2b-form-field"><label class="b2b-form-label">نام فارسی <span class="b2b-required">*</span></label><input type="text" name="name_fa" class="b2b-input" required /></div>
        <div class="b2b-form-field"><label class="b2b-form-label">نام انگلیسی <span class="b2b-required">*</span></label><input type="text" name="name_en" class="b2b-input" required /></div>
        <div class="b2b-form-row"><div class="b2b-form-field"><label class="b2b-form-label">کد <span class="b2b-required">*</span></label><input type="text" name="code" class="b2b-input" required /></div><div class="b2b-form-field"><label class="b2b-form-label">ترتیب</label><input type="number" name="sort_order" class="b2b-input" value="0" /></div></div>
        <div class="b2b-form-field"><label class="b2b-form-label">وضعیت</label><select name="status" class="b2b-select"><option value="active">فعال</option><option value="inactive">غیرفعال</option></select></div>
        </form></div><div class="b2b-modal-footer"><button type="button" class="b2b-btn b2b-btn-secondary b2b-modal-cancel">انصراف</button><button type="button" class="b2b-btn b2b-btn-primary" onclick="B2BGeo.saveEdit()">ذخیره</button></div></div></div>

        <div id="geo-delete-modal" class="b2b-modal b2b-modal-sm"><div class="b2b-modal-overlay"></div><div class="b2b-modal-dialog"><div class="b2b-modal-header"><h3 class="b2b-modal-title">تأیید حذف</h3><button type="button" class="b2b-modal-close">&times;</button></div><div class="b2b-modal-body"><p>آیا از حذف این <?php echo $title; ?> اطمینان دارید؟</p><input type="hidden" id="delete-geo-id" /></div><div class="b2b-modal-footer"><button type="button" class="b2b-btn b2b-btn-secondary b2b-modal-cancel">انصراف</button><button type="button" class="b2b-btn b2b-btn-danger" onclick="B2BGeo.confirmDel()">حذف</button></div></div></div>

        <div id="geo-import-modal" class="b2b-modal b2b-modal-md"><div class="b2b-modal-overlay"></div><div class="b2b-modal-dialog"><div class="b2b-modal-header"><h3 class="b2b-modal-title">ورود اطلاعات CSV</h3><button type="button" class="b2b-modal-close">&times;</button></div><div class="b2b-modal-body"><form id="geo-import-form" class="b2b-form">
        <div class="b2b-form-field"><label class="b2b-form-label">نوع <span class="b2b-required">*</span></label><select name="import_type" class="b2b-select" id="import-type"><option value="provinces">استان‌ها</option><option value="cities">شهرها</option></select></div>
        <div class="b2b-form-field"><label class="b2b-form-label">فایل CSV <span class="b2b-required">*</span></label><input type="file" name="csv_file" class="b2b-input" accept=".csv" required /><p class="b2b-form-desc" id="import-format"></p></div>
        </form></div><div class="b2b-modal-footer"><button type="button" class="b2b-btn b2b-btn-secondary b2b-modal-cancel">انصراف</button><button type="button" class="b2b-btn b2b-btn-primary" onclick="B2BGeo.doImport()">&#128229; ورود</button></div></div></div>
        <?php
    }

    public static function render_coming_soon() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header"><div><h1 class="b2b-workspace-title">شهرک‌های صنعتی</h1><p class="b2b-workspace-subtitle">این بخش در نسخه‌های بعدی اضافه خواهد شد</p></div></div>
        <div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#127981;</div><p class="b2b-empty-state-text">شهرک‌های صنعتی هنوز توسعه نیافته است.</p></div></div></div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
