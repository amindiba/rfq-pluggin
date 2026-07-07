<?php
defined('ABSPATH') || exit;

class B2B_Supplier_Admin {

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function enqueue_assets($hook) {
        $pages = array('b2b-suppliers', 'b2b-supplier-add', 'b2b-supplier-edit');
        foreach ($pages as $p) {
            if (strpos($hook, $p) !== false) {
                wp_enqueue_media();
                wp_enqueue_script('b2b-admin-js', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), B2B_PROCUREMENT_VERSION, true);
                wp_localize_script('b2b-admin-js', 'b2bProcurement', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
                    'version' => B2B_PROCUREMENT_VERSION,
                ));
                wp_enqueue_script('b2b-supplier', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/supplier.js', array('b2b-admin-js'), B2B_PROCUREMENT_VERSION, true);
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
            <div><h1 class="b2b-workspace-title">تامین‌کنندگان</h1><p class="b2b-workspace-subtitle">مدیریت تامین‌کنندگان سامانه</p></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-supplier-add'); ?>" class="b2b-btn b2b-btn-primary">&#10010; افزودن تامین‌کننده</a>
            </div>
        </div>
        <div class="b2b-toolbar">
            <div class="b2b-toolbar-left">
                <input type="text" id="supplier-search" class="b2b-search-input" placeholder="جستجو..." style="max-width:300px;" />
                <select id="supplier-status" class="b2b-select" style="max-width:150px;"><option value="">همه وضعیت‌ها</option><option value="1">فعال</option><option value="0">غیرفعال</option></select>
            </div>
            <div class="b2b-toolbar-right">
                <span id="supplier-count" class="b2b-text-muted"></span>
            </div>
        </div>
        <div id="supplier-table-container"></div>
        <div id="supplier-pagination"></div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== CREATE/EDIT FORM ====================
    public static function render_form() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $is_edit = isset($_GET['id']);
        $supplier = null;
        if ($is_edit) {
            $supplier = B2B_Supplier_DB::get_supplier(intval($_GET['id']));
        }
        $title = $is_edit && $supplier ? 'ویرایش تامین‌کننده' : 'افزودن تامین‌کننده جدید';

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title"><?php echo $title; ?></h1></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-suppliers'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>
        <form id="supplier-form" class="b2b-form" style="max-width:900px;">
            <input type="hidden" name="_b2b_nonce" value="<?php echo wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION); ?>" />
            <input type="hidden" name="action" value="b2b_supplier_save" />
            <?php if ($is_edit && $supplier) : ?><input type="hidden" name="item_id" value="<?php echo $supplier->id; ?>" /><?php endif; ?>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات اصلی</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">کد تامین‌کننده <span class="b2b-required">*</span></label>
                            <input type="text" name="code" class="b2b-input" required value="<?php echo $supplier ? esc_attr($supplier->code) : ''; ?>" placeholder="مثال: SUP-001" />
                            <p class="b2b-form-desc">فقط حروف و اعداد انگلیسی</p>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">نام <span class="b2b-required">*</span></label>
                            <input type="text" name="name" class="b2b-input" required value="<?php echo $supplier ? esc_attr($supplier->name) : ''; ?>" />
                        </div>
                    </div>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">نام انگلیسی</label>
                            <input type="text" name="name_en" class="b2b-input" value="<?php echo $supplier ? esc_attr($supplier->name_en) : ''; ?>" />
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">نام شرکت</label>
                            <input type="text" name="company_name" class="b2b-input" value="<?php echo $supplier ? esc_attr($supplier->company_name) : ''; ?>" />
                        </div>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">توضیحات</label>
                        <textarea name="description" class="b2b-textarea" rows="3"><?php echo $supplier ? esc_textarea($supplier->description) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات تماس</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">شخص تماس</label>
                            <input type="text" name="contact_person" class="b2b-input" value="<?php echo $supplier ? esc_attr($supplier->contact_person) : ''; ?>" />
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">ایمیل</label>
                            <input type="email" name="email" class="b2b-input" value="<?php echo $supplier ? esc_attr($supplier->email) : ''; ?>" />
                        </div>
                    </div>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">تلفن</label>
                            <input type="text" name="phone" class="b2b-input" value="<?php echo $supplier ? esc_attr($supplier->phone) : ''; ?>" />
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">موبایل</label>
                            <input type="text" name="mobile" class="b2b-input" value="<?php echo $supplier ? esc_attr($supplier->mobile) : ''; ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات مکانی</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">آدرس</label>
                        <textarea name="address" class="b2b-textarea" rows="2"><?php echo $supplier ? esc_textarea($supplier->address) : ''; ?></textarea>
                    </div>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">استان</label>
                            <input type="text" name="province" class="b2b-input" value="<?php echo $supplier ? esc_attr($supplier->province) : ''; ?>" />
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">شهر</label>
                            <input type="text" name="city" class="b2b-input" value="<?php echo $supplier ? esc_attr($supplier->city) : ''; ?>" />
                        </div>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">کد پستی</label>
                        <input type="text" name="postal_code" class="b2b-input" value="<?php echo $supplier ? esc_attr($supplier->postal_code) : ''; ?>" />
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات مالی</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">شناسه ملی</label>
                            <input type="text" name="national_id" class="b2b-input" value="<?php echo $supplier ? esc_attr($supplier->national_id) : ''; ?>" />
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">شماره اقتصادی</label>
                            <input type="text" name="tax_id" class="b2b-input" value="<?php echo $supplier ? esc_attr($supplier->tax_id) : ''; ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">وضعیت</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">وضعیت</label>
                        <select name="status" class="b2b-select">
                            <option value="1" <?php echo ($supplier && $supplier->status == 1) ? 'selected' : ''; ?>>فعال</option>
                            <option value="0" <?php echo ($supplier && $supplier->status == 0) ? 'selected' : ''; ?>>غیرفعال</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="b2b-form-actions">
                <button type="submit" class="b2b-btn b2b-btn-primary">ذخیره تامین‌کننده</button>
            </div>
        </form>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== DETAIL VIEW ====================
    public static function render_detail() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $id = intval($_GET['id'] ?? 0);
        $supplier = B2B_Supplier_DB::get_supplier($id);

        if (!$supplier) {
            wp_die('تامین‌کننده یافت نشد');
        }

        $status_class = $supplier->status == 1 ? 'b2b-status-active' : 'b2b-status-inactive';
        $status_text = $supplier->status == 1 ? 'فعال' : 'غیرفعال';

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title"><?php echo esc_html($supplier->name); ?></h1><p class="b2b-workspace-subtitle">جزئیات تامین‌کننده</p></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-supplier-edit&id=' . $id); ?>" class="b2b-btn b2b-btn-primary">ویرایش</a>
                <a href="<?php echo admin_url('admin.php?page=b2b-suppliers'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>

        <div class="b2b-card-grid b2b-card-grid-2">
            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات اصلی</h2></div>
                <div class="b2b-card-body">
                    <table class="b2b-status-table">
                        <tr><td class="b2b-status-label">کد</td><td><span class="b2b-badge b2b-badge-primary"><?php echo esc_html($supplier->code); ?></span></td></tr>
                        <tr><td class="b2b-status-label">نام</td><td><?php echo esc_html($supplier->name); ?></td></tr>
                        <tr><td class="b2b-status-label">نام انگلیسی</td><td><?php echo esc_html($supplier->name_en); ?></td></tr>
                        <tr><td class="b2b-status-label">شرکت</td><td><?php echo esc_html($supplier->company_name); ?></td></tr>
                        <tr><td class="b2b-status-label">وضعیت</td><td><span class="b2b-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td></tr>
                        <tr><td class="b2b-status-label">توضیحات</td><td><?php echo esc_html($supplier->description); ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات تماس</h2></div>
                <div class="b2b-card-body">
                    <table class="b2b-status-table">
                        <tr><td class="b2b-status-label">شخص تماس</td><td><?php echo esc_html($supplier->contact_person); ?></td></tr>
                        <tr><td class="b2b-status-label">ایمیل</td><td><?php echo esc_html($supplier->email); ?></td></tr>
                        <tr><td class="b2b-status-label">تلفن</td><td><?php echo esc_html($supplier->phone); ?></td></tr>
                        <tr><td class="b2b-status-label">موبایل</td><td><?php echo esc_html($supplier->mobile); ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات مکانی</h2></div>
                <div class="b2b-card-body">
                    <table class="b2b-status-table">
                        <tr><td class="b2b-status-label">آدرس</td><td><?php echo esc_html($supplier->address); ?></td></tr>
                        <tr><td class="b2b-status-label">استان</td><td><?php echo esc_html($supplier->province); ?></td></tr>
                        <tr><td class="b2b-status-label">شهر</td><td><?php echo esc_html($supplier->city); ?></td></tr>
                        <tr><td class="b2b-status-label">کد پستی</td><td><?php echo esc_html($supplier->postal_code); ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات مالی</h2></div>
                <div class="b2b-card-body">
                    <table class="b2b-status-table">
                        <tr><td class="b2b-status-label">شناسه ملی</td><td><?php echo esc_html($supplier->national_id); ?></td></tr>
                        <tr><td class="b2b-status-label">شماره اقتصادی</td><td><?php echo esc_html($supplier->tax_id); ?></td></tr>
                        <tr><td class="b2b-status-label">تاریخ ایجاد</td><td><?php echo esc_html($supplier->created_at); ?></td></tr>
                        <tr><td class="b2b-status-label">آخرین بروزرسانی</td><td><?php echo esc_html($supplier->updated_at); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
