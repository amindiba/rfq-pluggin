<?php
defined('ABSPATH') || exit;

class B2B_Rfq_Admin {

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function enqueue_assets($hook) {
        $pages = array('b2b-rfqs', 'b2b-rfq-add', 'b2b-rfq-edit', 'b2b-rfq-detail');
        foreach ($pages as $p) {
            if (strpos($hook, $p) !== false) {
                wp_enqueue_media();
                wp_enqueue_script('b2b-admin-js', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), B2B_PROCUREMENT_VERSION, true);
                wp_localize_script('b2b-admin-js', 'b2bProcurement', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
                    'version' => B2B_PROCUREMENT_VERSION,
                ));
                wp_enqueue_script('b2b-rfq', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/rfq.js', array('b2b-admin-js'), B2B_PROCUREMENT_VERSION, true);
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
            <div><h1 class="b2b-workspace-title">درخواست‌های خرید</h1><p class="b2b-workspace-subtitle">مدیریت درخواست‌های استعلام قیمت</p></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-rfq-add'); ?>" class="b2b-btn b2b-btn-primary">&#10010; افزودن درخواست</a>
            </div>
        </div>
        <div class="b2b-toolbar">
            <div class="b2b-toolbar-left">
                <input type="text" id="rfq-search" class="b2b-search-input" placeholder="جستجو..." style="max-width:300px;" />
                <select id="rfq-status" class="b2b-select" style="max-width:150px;"><option value="">همه وضعیت‌ها</option><option value="draft">پیش‌نویس</option><option value="submitted">ارسال شده</option><option value="closed">بسته شده</option></select>
            </div>
            <div class="b2b-toolbar-right"><span id="rfq-count" class="b2b-text-muted"></span></div>
        </div>
        <div id="rfq-table-container"></div>
        <div id="rfq-pagination"></div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== CREATE/EDIT FORM ====================
    public static function render_form() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $is_edit = isset($_GET['id']);
        $rfq = null;
        if ($is_edit) {
            $rfq = B2B_Rfq_DB::get_rfq(intval($_GET['id']));
            if ($rfq) {
                $rfq->products = B2B_Rfq_DB::get_rfq_products($rfq->id);
                $rfq->suppliers = B2B_Rfq_DB::get_rfq_suppliers($rfq->id);
            }
        }
        $title = $is_edit && $rfq ? 'ویرایش درخواست خرید' : 'ایجاد درخواست خرید جدید';
        $can_edit = !$rfq || $rfq->status === 'draft';

        // Get active suppliers
        $suppliers = B2B_Supplier_DB::get_suppliers(array('per_page' => 999, 'status' => 1));

        // Get products (simple list)
        $products_query = new WP_Query(array('post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 999, 'fields' => 'ids'));

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title"><?php echo $title; ?></h1></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-rfqs'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>

        <?php if (!$can_edit) : ?>
            <div class="b2b-alert b2b-alert-warning">
                <p>این درخواست ارسال شده و قابل ویرایش نیست.</p>
            </div>
        <?php endif; ?>

        <form id="rfq-form">
            <input type="hidden" name="_b2b_nonce" value="<?php echo wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION); ?>" />
            <input type="hidden" name="action" value="b2b_rfq_save" />
            <?php if ($is_edit && $rfq) : ?>
                <input type="hidden" name="rfq_id" value="<?php echo $rfq->id; ?>" />
            <?php endif; ?>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات کلی</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">عنوان درخواست <span class="b2b-required">*</span></label>
                        <input type="text" name="title" class="b2b-input" required value="<?php echo $rfq ? esc_attr($rfq->title) : ''; ?>" <?php echo !$can_edit ? 'disabled' : ''; ?> />
                    </div>
                    <?php if ($is_edit && $rfq) : ?>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">شماره مرجع</label>
                        <input type="text" class="b2b-input" value="<?php echo esc_html($rfq->reference); ?>" disabled />
                    </div>
                    <?php endif; ?>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">توضیحات</label>
                        <textarea name="description" class="b2b-textarea" rows="3" <?php echo !$can_edit ? 'disabled' : ''; ?>><?php echo $rfq ? esc_textarea($rfq->description) : ''; ?></textarea>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">مهلت پیشنهاد قیمت <span class="b2b-required">*</span></label>
                        <?php B2B_PC_Persian_Datepicker::render('deadline', $rfq ? esc_attr($rfq->deadline) : '', array(
                            'required' => !$can_edit,
                            'disabled' => !$can_edit,
                            'placeholder' => 'انتخاب تاریخ مهلت',
                        )); ?>
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">محصولات</h2><div class="b2b-card-actions"><?php if ($can_edit) : ?><button type="button" class="b2b-btn b2b-btn-sm b2b-btn-secondary" onclick="B2B RFQ.addProduct()">&#10010; افزودن</button><?php endif; ?></div></div>
                <div class="b2b-card-body">
                    <table class="b2b-table" id="rfq-products-table">
                        <thead><tr><th>محصول</th><th>تعداد درخواستی</th><th>واحد</th><th>یادداشت</th><?php if ($can_edit) : ?><th>عملیات</th><?php endif; ?></tr></thead>
                        <tbody id="rfq-products-body">
                            <?php if ($is_edit && $rfq && !empty($rfq->products)) : ?>
                                <?php foreach ($rfq->products as $p) : ?>
                                    <tr data-id="<?php echo $p->id; ?>">
                                        <td><input type="hidden" name="products[<?php echo $p->id; ?>][product_id]" value="<?php echo $p->product_id; ?>" /><input type="text" class="b2b-input" value="<?php echo esc_attr($p->product_name); ?>" disabled /></td>
                                        <td><input type="number" name="products[<?php echo $p->id; ?>][requested_qty]" class="b2b-input" value="<?php echo $p->requested_qty; ?>" min="0.001" step="0.001" <?php echo !$can_edit ? 'disabled' : ''; ?> /></td>
                                        <td><input type="text" name="products[<?php echo $p->id; ?>][unit]" class="b2b-input" value="<?php echo esc_attr($p->unit); ?>" style="width:80px;" <?php echo !$can_edit ? 'disabled' : ''; ?> /></td>
                                        <td><input type="text" name="products[<?php echo $p->id; ?>][notes]" class="b2b-input" value="<?php echo esc_attr($p->notes); ?>" <?php echo !$can_edit ? 'disabled' : ''; ?> /></td>
                                        <?php if ($can_edit) : ?><td><button type="button" class="b2b-btn b2b-btn-sm b2b-btn-danger" onclick="B2B RFQ.removeProduct(this)">&#10005;</button></td><?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">تامین‌کنندگان</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">انتخاب تامین‌کنندگان <span class="b2b-required">*</span></label>
                        <div class="b2b-checkbox-group">
                            <?php if (!empty($suppliers['items'])) : foreach ($suppliers['items'] as $sup) :
                                $checked = false;
                                if ($is_edit && $rfq && !empty($rfq->suppliers)) {
                                    foreach ($rfq->suppliers as $rs) {
                                        if ($rs->supplier_id == $sup->id) { $checked = true; break; }
                                    }
                                }
                            ?>
                                <label class="b2b-checkbox-label">
                                    <input type="checkbox" name="suppliers[]" value="<?php echo $sup->id; ?>" data-name="<?php echo esc_attr($sup->name); ?>" <?php echo $checked ? 'checked' : ''; ?> <?php echo !$can_edit ? 'disabled' : ''; ?> />
                                    <?php echo esc_html($sup->name); ?> (<?php echo esc_html($sup->code); ?>)
                                </label>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($can_edit) : ?>
            <div class="b2b-form-actions">
                <button type="submit" class="b2b-btn b2b-btn-primary">ذخیره درخواست</button>
            </div>
            <?php endif; ?>
        </form>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== DETAIL ====================
    public static function render_detail() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $id = intval($_GET['id'] ?? 0);
        $rfq = B2B_Rfq_DB::get_rfq($id);

        if (!$rfq) { wp_die('درخواست یافت نشد'); }

        $rfq->products = B2B_Rfq_DB::get_rfq_products($id);
        $rfq->suppliers = B2B_Rfq_DB::get_rfq_suppliers($id);

        $status_map = array('draft' => array('پیش‌نویس', 'b2b-badge-default'), 'submitted' => array('ارسال شده', 'b2b-badge-primary'), 'closed' => array('بسته شده', 'b2b-badge-success'));
        $status_info = $status_map[$rfq->status] ?? array('نامشخص', 'b2b-badge-default');

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title"><?php echo esc_html($rfq->title); ?></h1><p class="b2b-workspace-subtitle"><?php echo esc_html($rfq->reference); ?></p></div>
            <div class="b2b-workspace-actions">
                <?php if ($rfq->status === 'draft') : ?>
                    <a href="<?php echo admin_url('admin.php?page=b2b-rfq-edit&id=' . $id); ?>" class="b2b-btn b2b-btn-primary">ویرایش</a>
                    <button type="button" class="b2b-btn b2b-btn-primary" onclick="B2B RFQ.submitRfq(<?php echo $id; ?>)">ارسال درخواست</button>
                <?php endif; ?>
                <?php if ($rfq->status !== 'closed') : ?>
                    <button type="button" class="b2b-btn b2b-btn-danger" onclick="B2B RFQ.closeRfq(<?php echo $id; ?>)">بستن درخواست</button>
                <?php endif; ?>
                <a href="<?php echo admin_url('admin.php?page=b2b-rfqs'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>

        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات درخواست</h2></div>
            <div class="b2b-card-body">
                <div class="b2b-card-grid b2b-card-grid-3">
                    <div><strong>شماره مرجع:</strong> <?php echo esc_html($rfq->reference); ?></div>
                    <div><strong>وضعیت:</strong> <span class="b2b-badge <?php echo $status_info[1]; ?>"><?php echo $status_info[0]; ?></span></div>
                    <div><strong>مهلت:</strong> <?php echo esc_html($rfq->deadline ? B2B_PC_Formatter::format_gregorian($rfq->deadline, 'long') : '-'); ?></div>
                    <div><strong>تاریخ ایجاد:</strong> <?php echo esc_html($rfq->created_at); ?></div>
                    <div><strong>تاریخ ارسال:</strong> <?php echo esc_html($rfq->submitted_at ?: '-'); ?></div>
                    <div><strong>تاریخ بستن:</strong> <?php echo esc_html($rfq->closed_at ?: '-'); ?></div>
                </div>
                <?php if (!empty($rfq->description)) : ?>
                    <div style="margin-top:16px;"><strong>توضیحات:</strong><br><?php echo nl2br(esc_html($rfq->description)); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">محصولات درخواستی</h2></div>
            <div class="b2b-card-body">
                <?php if (!empty($rfq->products)) : ?>
                <table class="b2b-table">
                    <thead><tr><th>محصول</th><th>کد</th><th>تعداد درخواستی</th><th>واحد</th><th>یادداشت</th></tr></thead>
                    <tbody>
                    <?php foreach ($rfq->products as $p) : ?>
                        <tr>
                            <td><?php echo esc_html($p->product_name); ?></td>
                            <td><span class="b2b-badge b2b-badge-primary"><?php echo esc_html($p->product_sku); ?></span></td>
                            <td><?php echo esc_html($p->requested_qty); ?></td>
                            <td><?php echo esc_html($p->unit); ?></td>
                            <td><?php echo esc_html($p->notes); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                    <p>محصولی انتخاب نشده.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">تامین‌کنندگان</h2></div>
            <div class="b2b-card-body">
                <?php if (!empty($rfq->suppliers)) : ?>
                <div class="b2b-d-flex b2b-gap-2" style="flex-wrap:wrap;">
                    <?php foreach ($rfq->suppliers as $s) : ?>
                        <span class="b2b-badge b2b-badge-primary"><?php echo esc_html($s->supplier_name); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                    <p>تامین‌کننده‌ای انتخاب نشده.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
