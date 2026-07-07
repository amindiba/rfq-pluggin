<?php
defined('ABSPATH') || exit;

class B2B_Contract_Admin {

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function enqueue_assets($hook) {
        $pages = array('b2b-contracts', 'b2b-contract-add', 'b2b-contract-edit', 'b2b-contract-detail');
        foreach ($pages as $p) {
            if (strpos($hook, $p) !== false) {
                wp_enqueue_media();
                wp_enqueue_script('b2b-admin-js', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), B2B_PROCUREMENT_VERSION, true);
                wp_localize_script('b2b-admin-js', 'b2bProcurement', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
                    'version' => B2B_PROCUREMENT_VERSION,
                ));
                wp_enqueue_script('b2b-contract', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/contract.js', array('b2b-admin-js'), B2B_PROCUREMENT_VERSION, true);
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
            <div><h1 class="b2b-workspace-title">قراردادها</h1><p class="b2b-workspace-subtitle">مدیریت قراردادهای خرید</p></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-contract-add'); ?>" class="b2b-btn b2b-btn-primary">&#10010; افزودن قرارداد</a>
            </div>
        </div>
        <div class="b2b-toolbar">
            <div class="b2b-toolbar-left">
                <input type="text" id="contract-search" class="b2b-search-input" placeholder="جستجو..." style="max-width:300px;" />
                <select id="contract-status" class="b2b-select" style="max-width:150px;">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="draft">پیش‌نویس</option>
                    <option value="active">فعال</option>
                    <option value="closed">بسته شده</option>
                </select>
            </div>
            <div class="b2b-toolbar-right"><span id="contract-count" class="b2b-text-muted"></span></div>
        </div>
        <div id="contract-table-container"></div>
        <div id="contract-pagination"></div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== CREATE FORM ====================
    public static function render_form() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $is_edit = isset($_GET['id']);
        $contract = null;
        if ($is_edit) {
            $contract = B2B_Contract_DB::get_contract(intval($_GET['id']));
        }
        $title = $is_edit && $contract ? 'ویرایش قرارداد' : 'ایجاد قرارداد جدید';
        $can_edit = !$contract || $contract->status === 'draft';

        // Get confirmed POs
        $pos = B2B_PO_DB::get_pos(array('status' => 'confirmed', 'per_page' => 999));

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title"><?php echo $title; ?></h1></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-contracts'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>

        <?php if (!$can_edit) : ?>
            <div class="b2b-info-box b2b-info-warning"><p>این قرارداد فعال یا بسته شده و قابل ویرایش نیست.</p></div>
        <?php endif; ?>

        <form id="contract-form">
            <input type="hidden" name="_b2b_nonce" value="<?php echo wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION); ?>" />
            <input type="hidden" name="action" value="b2b_contract_save" />
            <?php if ($is_edit && $contract) : ?>
                <input type="hidden" name="contract_id" value="<?php echo $contract->id; ?>" />
            <?php endif; ?>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات قرارداد</h2></div>
                <div class="b2b-card-body">
                    <?php if ($is_edit && $contract) : ?>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">شماره قرارداد</label>
                        <input type="text" class="b2b-input" value="<?php echo esc_html($contract->contract_number); ?>" disabled />
                    </div>
                    <?php endif; ?>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">عنوان قرارداد <span class="b2b-required">*</span></label>
                        <input type="text" name="title" class="b2b-input" required value="<?php echo $contract ? esc_attr($contract->title) : ''; ?>" <?php echo !$can_edit ? 'disabled' : ''; ?> />
                    </div>
                    <?php if (!$is_edit) : ?>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">سفارش خرید <span class="b2b-required">*</span></label>
                        <select name="po_id" class="b2b-select" required>
                            <option value="">انتخاب سفارش خرید</option>
                            <?php if (!empty($pos['items'])) : foreach ($pos['items'] as $po) : ?>
                                <option value="<?php echo $po->id; ?>"><?php echo esc_html($po->po_number . ' - ' . $po->supplier_name); ?> (<?php echo number_format($po->grand_total); ?> تومان)</option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">تاریخ شروع <span class="b2b-required">*</span></label>
                            <?php B2B_PC_Persian_Datepicker::render('start_date', $contract ? esc_attr($contract->start_date) : '', array(
                                'required' => !$can_edit,
                                'disabled' => !$can_edit,
                                'placeholder' => 'تاریخ شروع قرارداد',
                            )); ?>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">تاریخ پایان <span class="b2b-required">*</span></label>
                            <?php B2B_PC_Persian_Datepicker::render('end_date', $contract ? esc_attr($contract->end_date) : '', array(
                                'required' => !$can_edit,
                                'disabled' => !$can_edit,
                                'placeholder' => 'تاریخ پایان قرارداد',
                            )); ?>
                        </div>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">یادداشت</label>
                        <textarea name="notes" class="b2b-textarea" rows="3" <?php echo !$can_edit ? 'disabled' : ''; ?>><?php echo $contract ? esc_textarea($contract->notes) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <?php if ($is_edit && $contract) : ?>
            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات خودکار</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-card-grid b2b-card-grid-3">
                        <div><strong>تامین‌کننده:</strong> <?php echo esc_html($contract->supplier_name); ?></div>
                        <div><strong>شماره سفارش:</strong> <?php echo esc_html($contract->po_number); ?></div>
                        <div><strong>شماره درخواست:</strong> <?php echo esc_html($contract->rfq_reference); ?></div>
                        <div><strong>شماره پیشنهاد:</strong> <?php echo esc_html($contract->quotation_reference); ?></div>
                        <div><strong>ارزش قرارداد:</strong> <?php echo number_format($contract->contract_value); ?> تومان</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($can_edit) : ?>
            <div class="b2b-form-actions">
                <button type="submit" class="b2b-btn b2b-btn-primary">ذخیره قرارداد</button>
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
        $contract = B2B_Contract_DB::get_contract($id);
        if (!$contract) wp_die('قرارداد یافت نشد');

        $status_map = array('draft' => array('پیش‌نویس', 'b2b-badge-default'), 'active' => array('فعال', 'b2b-badge-success'), 'closed' => array('بسته شده', 'b2b-badge-danger'));
        $status_info = $status_map[$contract->status] ?? array('نامشخص', 'b2b-badge-default');

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title"><?php echo esc_html($contract->title); ?></h1><p class="b2b-workspace-subtitle"><?php echo esc_html($contract->contract_number); ?></p></div>
            <div class="b2b-workspace-actions">
                <?php if ($contract->status === 'draft') : ?>
                    <a href="<?php echo admin_url('admin.php?page=b2b-contract-edit&id=' . $id); ?>" class="b2b-btn b2b-btn-primary">ویرایش</a>
                    <button type="button" class="b2b-btn b2b-btn-primary" onclick="B2BContract.activate(<?php echo $id; ?>)">فعال‌سازی قرارداد</button>
                <?php endif; ?>
                <?php if ($contract->status === 'active') : ?>
                    <button type="button" class="b2b-btn b2b-btn-danger" onclick="B2BContract.close(<?php echo $id; ?>)">بستن قرارداد</button>
                <?php endif; ?>
                <a href="<?php echo admin_url('admin.php?page=b2b-contracts'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>

        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات قرارداد</h2></div>
            <div class="b2b-card-body">
                <div class="b2b-card-grid b2b-card-grid-3">
                    <div><strong>شماره قرارداد:</strong> <?php echo esc_html($contract->contract_number); ?></div>
                    <div><strong>عنوان:</strong> <?php echo esc_html($contract->title); ?></div>
                    <div><strong>وضعیت:</strong> <span class="b2b-badge <?php echo $status_info[1]; ?>"><?php echo $status_info[0]; ?></span></div>
                    <div><strong>تامین‌کننده:</strong> <?php echo esc_html($contract->supplier_name); ?></div>
                    <div><strong>شماره سفارش:</strong> <?php echo esc_html($contract->po_number); ?></div>
                    <div><strong>شماره درخواست:</strong> <?php echo esc_html($contract->rfq_reference); ?></div>
                    <div><strong>تاریخ شروع:</strong> <?php echo esc_html($contract->start_date ? B2B_PC_Formatter::format_gregorian($contract->start_date, 'long') : '-'); ?></div>
                    <div><strong>تاریخ پایان:</strong> <?php echo esc_html($contract->end_date ? B2B_PC_Formatter::format_gregorian($contract->end_date, 'long') : '-'); ?></div>
                    <div><strong>ارزش قرارداد:</strong> <?php echo number_format($contract->contract_value); ?> تومان</div>
                </div>
                <?php if (!empty($contract->notes)) : ?>
                    <div style="margin-top:16px;"><strong>یادداشت:</strong><br><?php echo nl2br(esc_html($contract->notes)); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
