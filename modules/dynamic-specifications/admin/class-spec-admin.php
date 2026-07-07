<?php
namespace B2B\DynamicSpecs\Admin;

use B2B\DynamicSpecs\Database\Spec_DB;
use B2B\DynamicSpecs\FieldType\Registry;
use B2B\ProductDefinitions\Database\Definition_DB;

defined('ABSPATH') || exit;

class Spec_Admin {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function register_menu() {
        add_submenu_page(
            'b2b-procurement',
            'مدیریت مشخصات فنی',
            'مشخصات فنی',
            'manage_woocommerce',
            'b2b-spec-manager',
            array(__CLASS__, 'render_page')
        );
    }

    public static function enqueue_assets($hook) {
        if (strpos($hook, 'b2b-spec-manager') === false) return;

        $base = plugin_dir_url(dirname(__FILE__, 2) . '/bootstrap.php');
        wp_enqueue_style('b2b-spec-admin', $base . 'assets/css/admin.css', array(), '1.4.0');
        wp_enqueue_script('b2b-spec-admin', $base . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable'), '1.4.0', true);
        wp_localize_script('b2b-spec-admin', 'b2bSpec', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
            'types'   => Registry::get_all(),
        ));
    }

    public static function render_page() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $definitions = Definition_DB::get_all(array('per_page' => 200));
        $selected_def = intval($_GET['definition_id'] ?? 0);
        $specs = $selected_def ? Spec_DB::get_by_definition($selected_def) : array();
        $field_types = Registry::get_all();

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div>
                <h1 class="b2b-workspace-title">مدیریت مشخصات فنی (Dynamic Specifications)</h1>
                <p class="b2b-workspace-subtitle">تعریف فیلدهای داینامیک برای هر Product Definition</p>
            </div>
        </div>

        <div class="b2b-card b2b-mb-4">
            <div class="b2b-card-body">
                <form method="get" class="b2b-flex b2b-gap-4" style="align-items:flex-end;">
                    <input type="hidden" name="page" value="b2b-spec-manager" />
                    <div class="b2b-form-field" style="margin:0;">
                        <label class="b2b-form-label">انتخاب Product Definition</label>
                        <select name="definition_id" class="b2b-select" id="spec-def-select">
                            <option value="">— انتخاب کنید —</option>
                            <?php foreach ($definitions['items'] as $def) : ?>
                                <option value="<?php echo $def->id; ?>" <?php selected($selected_def, $def->id); ?>><?php echo esc_html($def->name); ?> (<?php echo $def->is_active ? 'فعال' : 'غیرفعال'; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="b2b-btn b2b-btn-primary">بارگذاری</button>
                </form>
            </div>
        </div>

        <?php if ($selected_def) : ?>
            <div class="b2b-card b2b-mb-4">
                <div class="b2b-card-header">
                    <h2 class="b2b-card-title">مشخصات تعریف: <?php echo esc_html(Definition_DB::get($selected_def)->name ?? ''); ?></h2>
                    <button type="button" class="b2b-btn b2b-btn-primary b2b-spec-add" id="b2b-spec-add-btn">+ افزودن فیلد</button>
                </div>
            </div>

            <div class="b2b-spec-list" id="b2b-spec-list" data-definition-id="<?php echo $selected_def; ?>">
                <?php foreach ($specs as $idx => $spec) : ?>
                    <?php self::render_spec_card($idx, $spec, $field_types); ?>
                <?php endforeach; ?>
            </div>

            <?php if (empty($specs)) : ?>
            <div class="b2b-card">
                <div class="b2b-card-body">
                    <div class="b2b-empty-state">
                        <div class="b2b-empty-state-icon">&#128295;</div>
                        <p>هیچ مشخصات فنی تعریف نشده</p>
                        <p>روی دکمه «افزودن فیلد» کلیک کنید تا اولین مشخصات را اضافه کنید</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="b2b-card">
                <div class="b2b-card-body">
                    <div class="b2b-empty-state">
                        <div class="b2b-empty-state-icon">&#128204;</div>
                        <p>ابتدا یک Product Definition انتخاب کنید</p>
                        <p>مشخصات فنی برای هر Product Definition به صورت مجزا تعریف می‌شوند</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    public static function render_spec_card($idx, $spec, $field_types) {
        $has_options = Registry::has_options($spec->field_type);
        ?>
        <div class="b2b-spec-card" data-spec-id="<?php echo $spec->id; ?>" data-index="<?php echo $idx; ?>">
            <div class="b2b-spec-card-header">
                <span class="b2b-spec-drag-handle">&#9776;</span>
                <span class="b2b-spec-card-title"><?php echo esc_html($spec->label); ?></span>
                <span class="b2b-spec-card-type"><?php echo Registry::get_label($spec->field_type); ?></span>
                <?php if ($spec->is_required) : ?><span class="b2b-badge b2b-badge-danger">اجباری</span><?php endif; ?>
                <?php if (!$spec->is_active) : ?><span class="b2b-badge b2b-badge-default">غیرفعال</span><?php endif; ?>
                <span class="b2b-spec-card-toggle">&#9660;</span>
                <button type="button" class="b2b-spec-delete" title="حذف">&#128465;</button>
            </div>
            <div class="b2b-spec-card-body">
                <div class="b2b-pr-row">
                    <div class="b2b-pr-field" style="flex:2;">
                        <label>عنوان فیلد <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="specs[<?php echo $idx; ?>][label]" value="<?php echo esc_attr($spec->label); ?>" class="regular-text" required />
                    </div>
                    <div class="b2b-pr-field" style="flex:1;">
                        <label>کلید فیلد <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="specs[<?php echo $idx; ?>][field_key]" value="<?php echo esc_attr($spec->field_key); ?>" class="regular-text" required pattern="[a-z0-9_-]+" title="فقط حروف کوچک انگلیسی، اعداد، خط تیره و زیرخط" />
                    </div>
                    <div class="b2b-pr-field" style="flex:1;">
                        <label>نوع فیلد</label>
                        <select name="specs[<?php echo $idx; ?>][field_type]" class="spec-type-select">
                            <?php foreach ($field_types as $key => $ft) : ?>
                                <option value="<?php echo $key; ?>" <?php selected($spec->field_type, $key); ?>><?php echo $ft['label']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="b2b-pr-row">
                    <div class="b2b-pr-field" style="flex:3;">
                        <label>توضیحات</label>
                        <input type="text" name="specs[<?php echo $idx; ?>][description]" value="<?php echo esc_attr($spec->description); ?>" class="regular-text" placeholder="راهنمای فیلد برای کاربر" />
                    </div>
                    <div class="b2b-pr-field" style="flex:2;">
                        <label>Placeholder</label>
                        <input type="text" name="specs[<?php echo $idx; ?>][placeholder]" value="<?php echo esc_attr($spec->placeholder); ?>" class="regular-text" />
                    </div>
                </div>
                <div class="b2b-pr-row">
                    <div class="b2b-pr-field" style="flex:1;">
                        <label>مقدار پیش‌فرض</label>
                        <input type="text" name="specs[<?php echo $idx; ?>][default_value]" value="<?php echo esc_attr($spec->default_value); ?>" class="regular-text" />
                    </div>
                    <div class="b2b-pr-field" style="flex:1;">
                        <label>ترتیب</label>
                        <input type="number" name="specs[<?php echo $idx; ?>][sort_order]" value="<?php echo $spec->sort_order; ?>" min="0" style="width:80px;" />
                    </div>
                </div>
                <div class="b2b-pr-row">
                    <div class="b2b-pr-field">
                        <label><input type="checkbox" name="specs[<?php echo $idx; ?>][is_required]" value="1" <?php checked($spec->is_required); ?> /> اجباری</label>
                    </div>
                    <div class="b2b-pr-field">
                        <label><input type="checkbox" name="specs[<?php echo $idx; ?>][is_searchable]" value="1" <?php checked($spec->is_searchable); ?> /> قابل جستجو</label>
                    </div>
                    <div class="b2b-pr-field">
                        <label><input type="checkbox" name="specs[<?php echo $idx; ?>][is_filterable]" value="1" <?php checked($spec->is_filterable); ?> /> قابل فیلتر</label>
                    </div>
                    <div class="b2b-pr-field">
                        <label><input type="checkbox" name="specs[<?php echo $idx; ?>][is_active]" value="1" <?php checked($spec->is_active); ?> /> فعال</label>
                    </div>
                </div>
                <?php if ($has_options) : ?>
                <div class="b2b-spec-options-section">
                    <label style="font-weight:600;font-size:13px;color:#6B7280;display:block;margin-bottom:8px;">گزینه‌ها</label>
                    <div class="b2b-spec-options-list" data-spec-idx="<?php echo $idx; ?>">
                        <?php if (!empty($spec->options)) : foreach ($spec->options as $oidx => $opt) : ?>
                            <div class="b2b-spec-option-item" data-oidx="<?php echo $oidx; ?>">
                                <span class="b2b-spec-drag-handle" style="cursor:grab;">&#9776;</span>
                                <input type="text" name="specs[<?php echo $idx; ?>][options][<?php echo $oidx; ?>]" value="<?php echo esc_attr($opt); ?>" placeholder="مقدار گزینه" style="width:250px;" />
                                <button type="button" class="b2b-spec-option-remove">&#10005;</button>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    <button type="button" class="button b2b-spec-option-add" data-idx="<?php echo $idx; ?>" style="margin-top:8px;">+ افزودن گزینه</button>
                </div>
                <?php endif; ?>
                <input type="hidden" name="specs[<?php echo $idx; ?>][spec_id]" value="<?php echo $spec->id; ?>" />
            </div>
        </div>
        <?php
    }
}
