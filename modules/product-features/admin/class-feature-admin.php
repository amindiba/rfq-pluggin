<?php
namespace B2B\ProductFeatures\Admin;

use B2B\ProductFeatures\Database\Feature_DB;

defined('ABSPATH') || exit;

class Feature_Admin {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function register_menu() {
        add_submenu_page(
            'b2b-procurement',
            'ویژگی‌های محصولات',
            'ویژگی‌های محصولات',
            'manage_woocommerce',
            'b2b-product-features',
            array(__CLASS__, 'render_list')
        );
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-product-feature-add', array(__CLASS__, 'render_form'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-product-feature-edit', array(__CLASS__, 'render_form'));
    }

    public static function enqueue_assets($hook) {
        $pages = array('b2b-product-features', 'b2b-product-feature-add', 'b2b-product-feature-edit');
        foreach ($pages as $p) {
            if (strpos($hook, $p) !== false) {
                wp_enqueue_script('b2b-admin-js', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), B2B_PROCUREMENT_VERSION, true);
                wp_localize_script('b2b-admin-js', 'b2bProcurement', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
                ));
                $base = plugin_dir_url(dirname(__FILE__, 2) . '/bootstrap.php');
                wp_enqueue_style('b2b-pf-admin', $base . 'assets/css/admin.css', array(), '1.4.0');
                wp_enqueue_script('b2b-pf-admin', $base . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable'), '1.4.0', true);
                wp_localize_script('b2b-pf-admin', 'b2bPF', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
                ));
                return;
            }
        }
    }

    public static function render_list() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $search   = sanitize_text_field(wp_unslash($_GET['search'] ?? ''));
        $group    = sanitize_text_field(wp_unslash($_GET['group'] ?? ''));
        $page     = max(1, intval($_GET['paged'] ?? 1));
        $result   = Feature_DB::get_all(array('search' => $search, 'group_name' => $group, 'per_page' => 20, 'page' => $page));
        $stats    = Feature_DB::get_stats();
        $groups   = Feature_DB::get_groups();

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div>
                <h1 class="b2b-workspace-title">ویژگی‌های محصولات</h1>
                <p class="b2b-workspace-subtitle">مدیریت ویژگی‌ها و مشخصات فنی قابل استفاده در محصولات</p>
            </div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-product-feature-add'); ?>" class="b2b-btn b2b-btn-primary">+ افزودن ویژگی جدید</a>
            </div>
        </div>

        <div class="b2b-card b2b-mb-4">
            <div class="b2b-card-body" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <form method="get" style="display:flex;gap:8px;flex:1;flex-wrap:wrap;">
                    <input type="hidden" name="page" value="b2b-product-features" />
                    <input type="text" name="search" class="b2b-input" placeholder="جستجو..." value="<?php echo esc_attr($search); ?>" style="min-width:200px;" />
                    <select name="group" class="b2b-select" style="min-width:150px;">
                        <option value="">همه گروه‌ها</option>
                        <?php foreach ($groups as $g) : ?>
                            <option value="<?php echo esc_attr($g); ?>" <?php selected($group, $g); ?>><?php echo esc_html($g); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="b2b-btn b2b-btn-primary">فیلتر</button>
                    <?php if ($search || $group) : ?>
                        <a href="<?php echo admin_url('admin.php?page=b2b-product-features'); ?>" class="b2b-btn b2b-btn-secondary">پاک کردن</a>
                    <?php endif; ?>
                </form>
                <div class="b2b-text-muted" style="font-size:13px;">
                    کل: <?php echo number_format($stats['total']); ?> | فعال: <?php echo number_format($stats['active']); ?> | گروه: <?php echo number_format($stats['groups']); ?>
                </div>
            </div>
        </div>

        <?php if (empty($result['items'])) : ?>
            <div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128295;</div><p>ویژگی‌ای یافت نشد</p></div></div></div>
        <?php else : ?>
            <div class="b2b-card"><div class="b2b-card-body" style="padding:0;overflow-x:auto;">
                <table class="b2b-table">
                    <thead><tr><th>#</th><th>نام</th><th>Slug</th><th>گروه</th><th>نوع</th><th>واحد</th><th>ترتیب</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                    <tbody>
                    <?php foreach ($result['items'] as $i => $item) :
                        $idx = ($result['page'] - 1) * 20 + $i + 1;
                        $type_labels = array('text'=>'متن','number'=>'عدد','select'=>'انتخابی','checkbox'=>'چک‌باکس','textarea'=>'توضیح','date'=>'تاریخ','url'=>'لینک');
                    ?>
                    <tr>
                        <td><?php echo number_format($idx); ?></td>
                        <td><strong><?php echo esc_html($item->name); ?></strong></td>
                        <td><code style="font-size:12px;"><?php echo esc_html($item->slug); ?></code></td>
                        <td><span class="b2b-badge b2b-badge-primary"><?php echo esc_html($item->group_name ?: '-'); ?></span></td>
                        <td><?php echo $type_labels[$item->feature_type] ?? $item->feature_type; ?></td>
                        <td><?php echo esc_html($item->unit ?: '-'); ?></td>
                        <td><?php echo number_format($item->sort_order); ?></td>
                        <td><span class="b2b-badge <?php echo $item->is_active ? 'b2b-badge-success' : 'b2b-badge-default'; ?>"><?php echo $item->is_active ? 'فعال' : 'غیرفعال'; ?></span></td>
                        <td style="white-space:nowrap;">
                            <a href="<?php echo admin_url('admin.php?page=b2b-product-feature-edit&id=' . $item->id); ?>" class="b2b-btn b2b-btn-sm b2b-btn-ghost">&#9998; ویرایش</a>
                            <button type="button" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-pf-delete" data-id="<?php echo $item->id; ?>" style="color:#EF4444;">&#128465;</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>

            <?php if ($result['pages'] > 1) : ?>
            <div class="b2b-table-pagination">
                <div class="b2b-pagination-info">صفحه <?php echo number_format($result['page']); ?> از <?php echo number_format($result['pages']); ?></div>
                <div class="b2b-pagination-links">
                    <?php for ($p = 1; $p <= $result['pages']; $p++) :
                        $url = add_query_arg(array('page' => 'b2b-product-features', 'paged' => $p, 'search' => $search, 'group' => $group));
                    ?>
                        <a href="<?php echo esc_url($url); ?>" class="<?php echo ($p === $result['page']) ? 'b2b-page-link b2b-page-active' : 'b2b-page-link'; ?>"><?php echo number_format($p); ?></a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    public static function render_form() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $id      = intval($_GET['id'] ?? 0);
        $item    = $id ? Feature_DB::get($id) : null;
        $is_edit = $item !== null;
        $title   = $is_edit ? 'ویرایش ویژگی: ' . esc_html($item->name) : 'افزودن ویژگی جدید';
        $groups  = Feature_DB::get_groups();

        $feature_types = array(
            'text' => 'متن', 'textarea' => 'توضیحات', 'number' => 'عدد', 'decimal' => 'عدد اعشاری',
            'select' => 'انتخابی', 'checkbox' => 'چک‌باکس', 'radio' => 'دکمه رادیویی',
            'date' => 'تاریخ', 'url' => 'لینک', 'email' => 'ایمیل', 'phone' => 'تلفن',
            'color' => 'رنگ', 'switch' => 'کلید',
        );

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title"><?php echo $title; ?></h1></div>
            <div class="b2b-workspace-actions"><a href="<?php echo admin_url('admin.php?page=b2b-product-features'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a></div>
        </div>

        <form id="b2b-pf-form" class="b2b-form" style="max-width:700px;" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" name="_b2b_nonce" value="<?php echo wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION); ?>" />
            <input type="hidden" name="action" value="b2b_pf_save" />
            <?php if ($is_edit) : ?><input type="hidden" name="feature_id" value="<?php echo $item->id; ?>" /><?php endif; ?>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات ویژگی</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-row">
                        <div class="b2b-form-field" style="flex:2;">
                            <label class="b2b-form-label">نام <span style="color:#EF4444;">*</span></label>
                            <input type="text" name="name" class="b2b-input" required value="<?php echo $item ? esc_attr($item->name) : ''; ?>" placeholder="مثال: وزن" />
                        </div>
                        <div class="b2b-form-field" style="flex:1;">
                            <label class="b2b-form-label">Slug</label>
                            <input type="text" name="slug" class="b2b-input" value="<?php echo $item ? esc_attr($item->slug) : ''; ?>" placeholder="خودکار از نام" />
                        </div>
                    </div>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">گروه</label>
                            <input type="text" name="group_name" class="b2b-input" value="<?php echo $item ? esc_attr($item->group_name) : ''; ?>" list="pf-groups-list" placeholder="مثال: ابعاد، متریال، استاندارد" />
                            <datalist id="pf-groups-list">
                                <?php foreach ($groups as $g) : ?>
                                    <option value="<?php echo esc_attr($g); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">نوع فیلد</label>
                            <select name="feature_type" class="b2b-select">
                                <?php foreach ($feature_types as $k => $v) : ?>
                                    <option value="<?php echo $k; ?>" <?php echo ($item && $item->feature_type === $k) ? 'selected' : ''; ?>><?php echo $v; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">واحد</label>
                            <input type="text" name="unit" class="b2b-input" value="<?php echo $item ? esc_attr($item->unit) : ''; ?>" placeholder="مثال: کیلوگرم، سانتی‌متر" />
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">ترتیب</label>
                            <input type="number" name="sort_order" class="b2b-input" value="<?php echo $item ? intval($item->sort_order) : '0'; ?>" min="0" />
                        </div>
                    </div>
                    <div class="b2b-pr-row" style="gap:20px;margin-top:8px;">
                        <label><input type="checkbox" name="is_required" value="1" <?php echo ($item && $item->is_required) ? 'checked' : ''; ?> /> اجباری</label>
                        <label><input type="checkbox" name="is_searchable" value="1" <?php echo ($item && $item->is_searchable) ? 'checked' : ''; ?> /> قابل جستجو</label>
                        <label><input type="checkbox" name="is_filterable" value="1" <?php echo ($item && $item->is_filterable) ? 'checked' : ''; ?> /> قابل فیلتر</label>
                        <label><input type="checkbox" name="is_active" value="1" <?php echo (!$item || $item->is_active) ? 'checked' : ''; ?> /> فعال</label>
                    </div>

                    <div id="pf-options-section" style="margin-top:16px;<?php echo ($item && in_array($item->feature_type, array('select','checkbox','radio'))) ? '' : 'display:none;'; ?>">
                        <label class="b2b-form-label">گزینه‌ها (برای select/checkbox/radio)</label>
                        <div id="pf-options-list" class="b2b-spec-options-list">
                            <?php if ($item && !empty($item->options)) :
                                foreach ($item->options as $oi => $ov) : ?>
                                    <div class="b2b-spec-option-item"><input type="text" name="options[]" value="<?php echo esc_attr($ov); ?>" style="width:300px;" placeholder="مقدار گزینه" /><button type="button" class="b2b-spec-option-remove">&#10005;</button></div>
                                <?php endforeach;
                            endif; ?>
                        </div>
                        <button type="button" class="button" id="pf-add-option" style="margin-top:8px;">+ افزودن گزینه</button>
                    </div>
                </div>
            </div>

            <div class="b2b-form-actions">
                <button type="submit" class="b2b-btn b2b-btn-primary"><?php echo $is_edit ? 'بروزرسانی' : 'ذخیره ویژگی'; ?></button>
                <a href="<?php echo admin_url('admin.php?page=b2b-product-features'); ?>" class="b2b-btn b2b-btn-secondary">انصراف</a>
            </div>
        </form>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
