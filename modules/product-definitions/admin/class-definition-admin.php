<?php
namespace B2B\ProductDefinitions\Admin;

use B2B\ProductDefinitions\Database\Definition_DB;

defined('ABSPATH') || exit;

class Definition_Admin {

    public function init() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function register_menu() {
        add_submenu_page(
            'b2b-procurement',
            'تعریف محصولات',
            'تعریف محصولات',
            'manage_woocommerce',
            'b2b-product-definitions',
            array($this, 'render_list')
        );
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-product-definition-add', array($this, 'render_form'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-product-definition-edit', array($this, 'render_form'));
    }

    public function enqueue_assets($hook) {
        $pages = array('b2b-product-definitions', 'b2b-product-definition-add', 'b2b-product-definition-edit');
        foreach ($pages as $p) {
            if (strpos($hook, $p) !== false) {
                wp_enqueue_script('b2b-admin-js', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), B2B_PROCUREMENT_VERSION, true);
                wp_localize_script('b2b-admin-js', 'b2bProcurement', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
                ));
                $base = plugin_dir_url(dirname(__FILE__, 2) . '/bootstrap.php');
                wp_enqueue_style('b2b-pd-admin', $base . 'assets/css/admin.css', array(), '1.4.0');
                wp_enqueue_script('b2b-pd-admin', $base . 'assets/js/admin.js', array('jquery'), '1.4.0', true);
                return;
            }
        }
    }

    // ==================== LIST ====================
    public function render_list() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $search = sanitize_text_field(wp_unslash($_GET['search'] ?? ''));
        $page = max(1, intval($_GET['paged'] ?? 1));
        $result = Definition_DB::get_all(array('search' => $search, 'per_page' => 20, 'page' => $page));
        $stats = Definition_DB::get_stats();

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div>
                <h1 class="b2b-workspace-title">تعریف محصولات (Product Definitions)</h1>
                <p class="b2b-workspace-subtitle">مدیریت الگوهای محصولات — تعریف مشخصات ثابتی که محصولات می‌توانند از آن‌ها استفاده کنند</p>
            </div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-product-definition-add'); ?>" class="b2b-btn b2b-btn-primary">+ افزودن تعریف جدید</a>
            </div>
        </div>

        <div class="b2b-card b2b-mb-4">
            <div class="b2b-card-body" style="display:flex;gap:12px;align-items:center;">
                <form method="get" style="display:flex;gap:8px;flex:1;">
                    <input type="hidden" name="page" value="b2b-product-definitions" />
                    <input type="text" name="search" class="b2b-input" placeholder="جستجو در نام یا slug..." value="<?php echo esc_attr($search); ?>" style="flex:1;" />
                    <button type="submit" class="b2b-btn b2b-btn-primary">جستجو</button>
                    <?php if ($search) : ?>
                        <a href="<?php echo admin_url('admin.php?page=b2b-product-definitions'); ?>" class="b2b-btn b2b-btn-secondary">پاک کردن</a>
                    <?php endif; ?>
                </form>
                <div class="b2b-text-muted" style="font-size:13px;">
                    کل: <?php echo number_format($stats['total']); ?> | فعال: <?php echo number_format($stats['active']); ?>
                </div>
            </div>
        </div>

        <?php if (empty($result['items'])) : ?>
            <div class="b2b-card">
                <div class="b2b-card-body">
                    <div class="b2b-empty-state">
                        <div class="b2b-empty-state-icon">&#128203;</div>
                        <p>تعریف محصولی یافت نشد</p>
                        <a href="<?php echo admin_url('admin.php?page=b2b-product-definition-add'); ?>" class="b2b-btn b2b-btn-primary">+ افزودن اولین تعریف</a>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="b2b-card">
                <div class="b2b-card-body" style="padding:0;overflow-x:auto;">
                    <table class="b2b-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>نام</th>
                                <th>Slug</th>
                                <th>توضیح</th>
                                <th>ترتیب</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['items'] as $i => $item) :
                                $idx = ($result['page'] - 1) * 20 + $i + 1;
                            ?>
                            <tr>
                                <td><?php echo number_format($idx); ?></td>
                                <td><strong><?php echo esc_html($item->name); ?></strong></td>
                                <td><code><?php echo esc_html($item->slug); ?></code></td>
                                <td><?php echo esc_html(mb_substr($item->description ?: '-', 0, 50)); ?></td>
                                <td><?php echo number_format($item->sort_order); ?></td>
                                <td>
                                    <span class="b2b-badge <?php echo $item->is_active ? 'b2b-badge-success' : 'b2b-badge-default'; ?>">
                                        <?php echo $item->is_active ? 'فعال' : 'غیرفعال'; ?>
                                    </span>
                                </td>
                                <td style="white-space:nowrap;">
                                    <a href="<?php echo admin_url('admin.php?page=b2b-product-definition-edit&id=' . $item->id); ?>" class="b2b-btn b2b-btn-sm b2b-btn-ghost">&#9998; ویرایش</a>
                                    <button type="button" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-pd-delete" data-id="<?php echo $item->id; ?>" style="color:#EF4444;">&#128465; حذف</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($result['pages'] > 1) : ?>
            <div class="b2b-table-pagination">
                <div class="b2b-pagination-info">صفحه <?php echo number_format($result['page']); ?> از <?php echo number_format($result['pages']); ?></div>
                <div class="b2b-pagination-links">
                    <?php for ($p = 1; $p <= $result['pages']; $p++) :
                        $url = add_query_arg(array('page' => 'b2b-product-definitions', 'paged' => $p, 'search' => $search));
                        $cls = ($p === $result['page']) ? 'b2b-page-link b2b-page-active' : 'b2b-page-link';
                    ?>
                        <a href="<?php echo esc_url($url); ?>" class="<?php echo $cls; ?>"><?php echo number_format($p); ?></a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== FORM (CREATE / EDIT) ====================
    public function render_form() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');

        $id = intval($_GET['id'] ?? 0);
        $item = $id ? Definition_DB::get($id) : null;
        $is_edit = $item !== null;
        $title = $is_edit ? 'ویرایش تعریف: ' . esc_html($item->name) : 'افزودن تعریف جدید';

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title"><?php echo $title; ?></h1></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-product-definitions'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>

        <form id="b2b-pd-form" class="b2b-form" style="max-width:700px;" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" name="_b2b_nonce" value="<?php echo wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION); ?>" />
            <input type="hidden" name="action" value="b2b_pd_save" />
            <?php if ($is_edit) : ?>
                <input type="hidden" name="definition_id" value="<?php echo $item->id; ?>" />
            <?php endif; ?>

            <div class="b2b-card">
                <div class="b2b-card-header">
                    <h2 class="b2b-card-title">اطلاعات تعریف</h2>
                </div>
                <div class="b2b-card-body">
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">نام <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="name" class="b2b-input" required value="<?php echo $item ? esc_attr($item->name) : ''; ?>" placeholder="مثال: فولاد ضد زنگ" />
                        <p class="description">نام نمایشی الگوی محصول</p>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">نامک (Slug)</label>
                        <input type="text" name="slug" class="b2b-input" value="<?php echo $item ? esc_attr($item->slug) : ''; ?>" placeholder="خودکار از نام تولید می‌شود" />
                        <p class="description">خالی بگذارید تا خودکار از نام ساخته شود</p>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">توضیحات</label>
                        <textarea name="description" class="b2b-textarea" rows="3" placeholder="توضیحات اختیاری این الگو"><?php echo $item ? esc_textarea($item->description) : ''; ?></textarea>
                    </div>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">وضعیت</label>
                            <select name="is_active" class="b2b-select">
                                <option value="1" <?php echo (!$item || $item->is_active) ? 'selected' : ''; ?>>فعال</option>
                                <option value="0" <?php echo ($item && !$item->is_active) ? 'selected' : ''; ?>>غیرفعال</option>
                            </select>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">ترتیب نمایش</label>
                            <input type="number" name="sort_order" class="b2b-input" value="<?php echo $item ? intval($item->sort_order) : '0'; ?>" min="0" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="b2b-form-actions">
                <button type="submit" class="b2b-btn b2b-btn-primary"><?php echo $is_edit ? 'بروزرسانی' : 'ذخیره تعریف'; ?></button>
                <a href="<?php echo admin_url('admin.php?page=b2b-product-definitions'); ?>" class="b2b-btn b2b-btn-secondary">انصراف</a>
            </div>
        </form>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
