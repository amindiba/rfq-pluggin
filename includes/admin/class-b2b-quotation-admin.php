<?php
defined('ABSPATH') || exit;

class B2B_Quotation_Admin {

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function enqueue_assets($hook) {
        $pages = array('b2b-quotations', 'b2b-quotation-add', 'b2b-quotation-detail', 'b2b-quotation-compare');
        foreach ($pages as $p) {
            if (strpos($hook, $p) !== false) {
                wp_enqueue_media();
                wp_enqueue_script('b2b-admin-js', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), B2B_PROCUREMENT_VERSION, true);
                wp_localize_script('b2b-admin-js', 'b2bProcurement', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
                    'version' => B2B_PROCUREMENT_VERSION,
                ));
                wp_enqueue_script('b2b-quotation', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/quotation.js', array('b2b-admin-js'), B2B_PROCUREMENT_VERSION, true);
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
            <div><h1 class="b2b-workspace-title">پیشنهادات قیمت</h1><p class="b2b-workspace-subtitle">مدیریت پیشنهادات قیمت تامین‌کنندگان</p></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-quotation-add'); ?>" class="b2b-btn b2b-btn-primary">&#10010; افزودن پیشنهاد</a>
            </div>
        </div>
        <div class="b2b-toolbar">
            <div class="b2b-toolbar-left">
                <input type="text" id="quotation-search" class="b2b-search-input" placeholder="جستجو..." style="max-width:300px;" />
                <select id="quotation-status" class="b2b-select" style="max-width:150px;">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="draft">پیش‌نویس</option>
                    <option value="submitted">ارسال شده</option>
                    <option value="selected">انتخاب شده</option>
                    <option value="rejected">رد شده</option>
                </select>
            </div>
            <div class="b2b-toolbar-right"><span id="quotation-count" class="b2b-text-muted"></span></div>
        </div>
        <div id="quotation-table-container"></div>
        <div id="quotation-pagination"></div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== CREATE FORM ====================
    public static function render_form() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $is_edit = isset($_GET['id']);
        $quotation = null;
        if ($is_edit) {
            $quotation = B2B_Quotation_DB::get_quotation(intval($_GET['id']));
            if ($quotation) $quotation->items = B2B_Quotation_DB::get_items($quotation->id);
        }
        $title = $is_edit && $quotation ? 'ویرایش پیشنهاد قیمت' : 'افزودن پیشنهاد قیمت جدید';

        // Get submitted RFQs
        $rfqs = B2B_Rfq_DB::get_rfqs(array('status' => 'submitted', 'per_page' => 999));

        // Get active suppliers
        $suppliers = B2B_Supplier_DB::get_suppliers(array('per_page' => 999, 'status' => 1));

        // Get products
        $products_query = new WP_Query(array('post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 999, 'fields' => 'all'));

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title"><?php echo $title; ?></h1></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-quotations'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>

        <form id="quotation-form">
            <input type="hidden" name="_b2b_nonce" value="<?php echo wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION); ?>" />
            <input type="hidden" name="action" value="b2b_quotation_save" />
            <?php if ($is_edit && $quotation) : ?>
                <input type="hidden" name="quotation_id" value="<?php echo $quotation->id; ?>" />
            <?php endif; ?>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات پایه</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">درخواست خرید <span class="b2b-required">*</span></label>
                            <select name="rfq_id" class="b2b-select" id="rfq-select" required>
                                <option value="">انتخاب درخواست</option>
                                <?php if (!empty($rfqs['items'])) : foreach ($rfqs['items'] as $rfq) : ?>
                                    <option value="<?php echo $rfq->id; ?>" <?php echo ($quotation && $quotation->rfq_id == $rfq->id) ? 'selected' : ''; ?>><?php echo esc_html($rfq->reference . ' - ' . $rfq->title); ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">تامین‌کننده <span class="b2b-required">*</span></label>
                            <select name="supplier_id" class="b2b-select" required>
                                <option value="">انتخاب تامین‌کننده</option>
                                <?php if (!empty($suppliers['items'])) : foreach ($suppliers['items'] as $sup) : ?>
                                    <option value="<?php echo $sup->id; ?>" data-name="<?php echo esc_attr($sup->name); ?>" <?php echo ($quotation && $quotation->supplier_id == $sup->id) ? 'selected' : ''; ?>><?php echo esc_html($sup->name . ' (' . $sup->code . ')'); ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">یادداشت</label>
                        <textarea name="notes" class="b2b-textarea" rows="2"><?php echo $quotation ? esc_textarea($quotation->notes) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">ردیف‌های محصول</h2><div class="b2b-card-actions"><button type="button" class="b2b-btn b2b-btn-sm b2b-btn-secondary" onclick="B2BQuotation.addItem()">&#10010; افزودن ردیف</button></div></div>
                <div class="b2b-card-body">
                    <table class="b2b-table" id="quotation-items-table">
                        <thead><tr><th>محصول</th><th>قیمت واحد (تومان)</th><th>تعداد</th><th>زمان تحویل (روز)</th><th>یادداشت</th><th>جمع</th><th>عملیات</th></tr></thead>
                        <tbody id="quotation-items-body">
                            <?php if ($is_edit && $quotation && !empty($quotation->items)) : ?>
                                <?php foreach ($quotation->items as $item) : ?>
                                    <tr data-id="<?php echo $item->id; ?>">
                                        <td><select name="items[<?php echo $item->id; ?>][product_id]" class="b2b-select" data-name="items[<?php echo $item->id; ?>][product_name]" data-sku="items[<?php echo $item->id; ?>][product_sku]"><?php if ($products_query->have_posts()) : while ($products_query->have_posts()) : $products_query->the_post(); ?><option value="<?php the_ID(); ?>" data-name="<?php echo esc_attr(get_the_title()); ?>" data-sku="<?php echo esc_attr(get_post_meta(get_the_ID(), '_sku', true)); ?>" <?php echo ($item->product_id == get_the_ID()) ? 'selected' : ''; ?>><?php the_title(); ?></option><?php endwhile; endif; ?></select></td>
                                        <td><input type="number" name="items[<?php echo $item->id; ?>][unit_price]" class="b2b-input" value="<?php echo $item->unit_price; ?>" min="0" step="0.01" /></td>
                                        <td><input type="number" name="items[<?php echo $item->id; ?>][quantity]" class="b2b-input" value="<?php echo $item->quantity; ?>" min="0" step="0.001" /></td>
                                        <td><input type="number" name="items[<?php echo $item->id; ?>][delivery_days]" class="b2b-input" value="<?php echo $item->delivery_days; ?>" min="0" /></td>
                                        <td><input type="text" name="items[<?php echo $item->id; ?>][supplier_note]" class="b2b-input" value="<?php echo esc_attr($item->supplier_note); ?>" /></td>
                                        <td class="b2b-text-muted"><?php echo number_format($item->line_total); ?> تومان</td>
                                        <td><button type="button" class="b2b-btn b2b-btn-sm b2b-btn-danger" onclick="B2BQuotation.removeItem(this)">&#10005;</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div style="margin-top:16px;text-align:left;">
                        <strong>جمع کل: <span id="quotation-grand-total">۰</span> تومان</strong>
                    </div>
                </div>
            </div>

            <div class="b2b-form-actions">
                <button type="submit" class="b2b-btn b2b-btn-primary">ذخیره پیشنهاد</button>
            </div>
        </form>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== DETAIL ====================
    public static function render_detail() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $id = intval($_GET['id'] ?? 0);
        $q = B2B_Quotation_DB::get_quotation($id);
        if (!$q) wp_die('پیشنهاد یافت نشد');

        $q->items = B2B_Quotation_DB::get_items($id);
        $status_map = array('draft' => array('پیش‌نویس', 'b2b-badge-default'), 'submitted' => array('ارسال شده', 'b2b-badge-primary'), 'selected' => array('انتخاب شده', 'b2b-badge-success'), 'rejected' => array('رد شده', 'b2b-badge-danger'));
        $status_info = $status_map[$q->status] ?? array('نامشخص', 'b2b-badge-default');

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title">پیشنهاد قیمت #<?php echo $q->id; ?></h1><p class="b2b-workspace-subtitle"><?php echo esc_html($q->supplier_name); ?></p></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-quotations'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>

        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات پیشنهاد</h2></div>
            <div class="b2b-card-body">
                <div class="b2b-card-grid b2b-card-grid-3">
                    <div><strong>شماره درخواست:</strong> <?php echo esc_html($q->rfq_reference); ?></div>
                    <div><strong>تامین‌کننده:</strong> <?php echo esc_html($q->supplier_name); ?></div>
                    <div><strong>وضعیت:</strong> <span class="b2b-badge <?php echo $status_info[1]; ?>"><?php echo $status_info[0]; ?></span></div>
                    <div><strong>تاریخ ارسال:</strong> <?php echo esc_html($q->submitted_at ?: '-'); ?></div>
                    <div><strong>جمع کل:</strong> <?php echo number_format($q->grand_total); ?> تومان</div>
                    <div><strong>یادداشت:</strong> <?php echo esc_html($q->notes ?: '-'); ?></div>
                </div>
            </div>
        </div>

        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">ردیف‌های محصول</h2></div>
            <div class="b2b-card-body">
                <?php if (!empty($q->items)) : ?>
                <table class="b2b-table">
                    <thead><tr><th>محصول</th><th>کد</th><th>قیمت واحد</th><th>تعداد</th><th>جمع</th><th>زمان تحویل</th><th>یادداشت</th></tr></thead>
                    <tbody>
                    <?php foreach ($q->items as $item) : ?>
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
                    <p>ردیفی ثبت نشده.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== COMPARISON ====================
    public static function render_comparison() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $rfq_id = intval($_GET['rfq_id'] ?? 0);
        $rfq = B2B_Rfq_DB::get_rfq($rfq_id);
        if (!$rfq) wp_die('درخواست یافت نشد');

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title">مقایسه پیشنهادات</h1><p class="b2b-workspace-subtitle"><?php echo esc_html($rfq->reference . ' - ' . $rfq->title); ?></p></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-rfq-detail&id=' . $rfq_id); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>

        <div id="comparison-container">
            <div style="text-align:center;padding:40px;"><div class="b2b-spinner-lg" style="margin:0 auto;"></div></div>
        </div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
