<?php
defined('ABSPATH') || exit;

class B2B_Product_Catalog_Admin {

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function enqueue_assets($hook) {
        $pages = array('b2b-products', 'b2b-product-add', 'b2b-product-edit', 'b2b-categories', 'b2b-category-add', 'b2b-category-edit');
        $found = false;
        foreach ($pages as $p) { if (strpos($hook, $p) !== false) { $found = true; break; } }
        if (!$found) return;
        wp_enqueue_media();
        // Ensure admin.js is loaded
        if (!wp_script_is('b2b-admin', 'enqueued')) {
            wp_enqueue_script('b2b-admin', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), B2B_PROCUREMENT_VERSION, true);
            wp_localize_script('b2b-admin', 'b2bProcurement', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
                'version' => B2B_PROCUREMENT_VERSION,
            ));
        }
        wp_enqueue_script('b2b-product-catalog', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/product-catalog.js', array('b2b-admin'), B2B_PROCUREMENT_VERSION, true);
    }

    // ==================== PRODUCTS LIST ====================
    public static function render_products() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        wp_enqueue_media();
        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title">محصولات</h1><p class="b2b-workspace-subtitle">مدیریت محصولات کاتالوگ</p></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-product-add'); ?>" class="b2b-btn b2b-btn-primary">&#10010; افزودن محصول</a>
            </div>
        </div>
        <div class="b2b-toolbar">
            <div class="b2b-toolbar-left">
                <input type="text" id="product-search" class="b2b-search-input" placeholder="جستجو..." style="max-width:300px;" />
                <select id="product-status" class="b2b-select" style="max-width:150px;"><option value="">همه وضعیت‌ها</option><option value="1">فعال</option><option value="0">غیرفعال</option></select>
                <select id="product-category" class="b2b-select" style="max-width:200px;"><option value="">همه دسته‌بندی‌ها</option></select>
            </div>
            <div class="b2b-toolbar-right">
                <span id="product-count" class="b2b-text-muted"></span>
                <div class="b2b-d-flex b2b-gap-2">
                    <button type="button" class="b2b-btn b2b-btn-secondary b2b-btn-sm" data-view="list" title="نمایش لیستی">&#9776;</button>
                    <button type="button" class="b2b-btn b2b-btn-secondary b2b-btn-sm" data-view="grid" title="نمایش کارتی">&#9638;</button>
                </div>
            </div>
        </div>
        <div class="b2b-toolbar">
            <div class="b2b-toolbar-left">
                <select id="bulk-action-select" class="b2b-select" style="max-width:200px;">
                    <option value="">عملیات گروهی</option>
                    <option value="status_active">فعال‌سازی</option>
                    <option value="status_inactive">غیرفعال‌سازی</option>
                    <option value="delete">حذف</option>
                </select>
                <button type="button" class="b2b-btn b2b-btn-secondary b2b-bulk-submit">اعمال</button>
            </div>
        </div>
        <div id="product-table-container"></div>
        <div id="product-pagination"></div>

        <!-- Quick Edit Modal -->
        <div id="quick-edit-modal" class="b2b-modal b2b-modal-md">
            <div class="b2b-modal-overlay"></div>
            <div class="b2b-modal-dialog">
                <div class="b2b-modal-header">
                    <h3 class="b2b-modal-title">ویرایش سریع</h3>
                    <button type="button" class="b2b-modal-close">&times;</button>
                </div>
                <div class="b2b-modal-body">
                    <form id="quick-edit-form" class="b2b-form">
                        <input type="hidden" name="product_id" />
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">نام محصول</label>
                            <input type="text" name="name_fa" class="b2b-input" required value="<?php echo $product ? esc_attr($product->name_fa) : ''; ?>" />
                        </div>
                        <div class="b2b-form-row">
                            <div class="b2b-form-field">
                                <label class="b2b-form-label">کد (SKU)</label>
                                <input type="text" name="sku" class="b2b-input" required />
                            </div>
                            <div class="b2b-form-field">
                                <label class="b2b-form-label">واحد</label>
                                <input type="text" name="base_unit" class="b2b-input" />
                            </div>
                        </div>
                        <div class="b2b-form-row">
                            <div class="b2b-form-field">
                                <label class="b2b-form-label">حداقل تعداد</label>
                                <input type="number" name="min_order_qty" class="b2b-input" />
                            </div>
                            <div class="b2b-form-field">
                                <label class="b2b-form-label">زمان تحویل (روز)</label>
                                <input type="number" name="lead_time_days" class="b2b-input" />
                            </div>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">وضعیت</label>
                            <select name="status" class="b2b-select">
                                <option value="0">پیش‌نویس</option>
                                <option value="1">فعال</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="b2b-modal-footer">
                    <button type="button" class="b2b-btn b2b-btn-secondary b2b-modal-cancel" onclick="B2BCatalog.closeModal('quick-edit-modal')">انصراف</button>
                    <button type="button" class="b2b-btn b2b-btn-primary" onclick="B2BCatalog.saveQuickEdit()">ذخیره</button>
                </div>
            </div>
        </div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== PRODUCT CREATE/EDIT ====================
    public static function render_product_form() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        wp_enqueue_media();
        $is_edit = isset($_GET['id']);
        $product = null;
        if ($is_edit) {
            $post = get_post(intval($_GET['id']));
            if ($post && $post->post_type === 'product') {
                $product = (object) array(
                    'id' => $post->ID,
                    'sku' => get_post_meta($post->ID, '_sku', true),
                    'name_fa' => $post->post_title,
                    'name_en' => get_post_meta($post->ID, '_b2b_name_en', true) ?: $post->post_title,
                    'description' => $post->post_content,
                    'short_desc' => $post->post_excerpt,
                    'category_id' => '',
                    'category_name' => '',
                    'base_unit' => get_post_meta($post->ID, '_b2b_base_unit', true) ?: 'pcs',
                    'min_order_qty' => get_post_meta($post->ID, '_b2b_min_order_qty', true) ?: 1,
                    'lead_time_days' => get_post_meta($post->ID, '_b2b_lead_time_days', true) ?: 0,
                    'status' => $post->post_status === 'publish' ? 1 : 0,
                    'visibility' => get_post_meta($post->ID, '_b2b_visibility', true) ?: 1,
                    'regular_price' => get_post_meta($post->ID, '_regular_price', true),
                    'sale_price' => get_post_meta($post->ID, '_sale_price', true),
                    'weight' => get_post_meta($post->ID, '_weight', true),
                );
                $terms = get_the_terms($post->ID, 'product_cat');
                if ($terms && !is_wp_error($terms)) {
                    $product->category_id = $terms[0]->term_id;
                    $product->category_name = $terms[0]->name;
                }
            }
        }
        $title = $is_edit && $product ? 'ویرایش محصول' : 'افزودن محصول جدید';
        $cat_svc = new B2B_WC_Category_Service();
        $categories = $cat_svc->get_categories(array('per_page' => 999));
        $units = B2B_Procurement_Master_Data_DB::get_units(array('per_page' => 999, 'status' => 'active'));

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title"><?php echo $title; ?></h1></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-products'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>
        <form id="product-form" class="b2b-form" style="max-width:800px;" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" name="_b2b_nonce" value="<?php echo wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION); ?>" />
            <input type="hidden" name="action" value="b2b_product_save" />
            <?php if ($is_edit && $product) : ?><input type="hidden" name="product_id" value="<?php echo $product->id; ?>" /><?php endif; ?>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات پایه</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">کد محصول (SKU) <span class="b2b-required">*</span></label>
                            <input type="text" name="sku" class="b2b-input" required value="<?php echo $product ? esc_attr($product->sku) : ''; ?>" />
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">دسته‌بندی</label>
                            <select name="category_id" class="b2b-select">
                                <option value="">انتخاب دسته‌بندی</option>
                                <?php if (!empty($categories['items'])) : foreach ($categories['items'] as $cat) : ?>
                                    <option value="<?php echo $cat->id; ?>" <?php echo ($product && $product->category_id == $cat->id) ? 'selected' : ''; ?>><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">نام فارسی <span class="b2b-required">*</span></label>
                        <input type="text" name="name_fa" class="b2b-input" required value="<?php echo $product ? esc_attr($product->name_fa) : ''; ?>" />
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">نام انگلیسی <span class="b2b-required">*</span></label>
                        <input type="text" name="name_en" class="b2b-input" required value="<?php echo $product ? esc_attr($product->name_en) : ''; ?>" />
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">توضیحات</label>
                        <textarea name="description" class="b2b-textarea" rows="4"><?php echo $product ? esc_textarea($product->description) : ''; ?></textarea>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">توضیح کوتاه</label>
                        <input type="text" name="short_desc" class="b2b-input" value="<?php echo $product ? esc_attr($product->short_desc) : ''; ?>" />
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">قیمت و موجودی</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">قیمت اصلی (تومان)</label>
                            <input type="number" name="regular_price" class="b2b-input" step="1" value="<?php echo $product ? esc_attr(get_post_meta($product->id, '_regular_price', true)) : ''; ?>" />
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">قیمت تخفیف (تومان)</label>
                            <input type="number" name="sale_price" class="b2b-input" step="1" value="<?php echo $product ? esc_attr(get_post_meta($product->id, '_sale_price', true)) : ''; ?>" />
                        </div>
                    </div>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">وضعیت مالیات</label>
                            <select name="tax_status" class="b2b-select">
                                <option value="taxable" <?php echo ($product && get_post_meta($product->id, '_tax_status', true) === 'taxable') ? 'selected' : ''; ?>>مشمول مالیات</option>
                                <option value="shipping" <?php echo ($product && get_post_meta($product->id, '_tax_status', true) === 'shipping') ? 'selected' : ''; ?>>فقط حمل</option>
                                <option value="none" <?php echo (!$product || get_post_meta($product->id, '_tax_status', true) === 'none') ? 'selected' : ''; ?>>بدون مالیات</option>
                            </select>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">کلاس مالیات</label>
                            <input type="text" name="tax_class" class="b2b-input" value="<?php echo $product ? esc_attr(get_post_meta($product->id, '_tax_class', true)) : ''; ?>" placeholder="standard" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">موجودی</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">مدیریت موجودی</label>
                            <select name="manage_stock" class="b2b-select">
                                <option value="0" <?php echo (!$product || get_post_meta($product->id, '_manage_stock', true) !== 'yes') ? 'selected' : ''; ?>>خیر</option>
                                <option value="1" <?php echo ($product && get_post_meta($product->id, '_manage_stock', true) === 'yes') ? 'selected' : ''; ?>>بله</option>
                            </select>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">موجودی</label>
                            <input type="number" name="stock_quantity" class="b2b-input" value="<?php echo $product ? esc_attr(get_post_meta($product->id, '_stock', true)) : ''; ?>" />
                        </div>
                    </div>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">وضعیت موجودی</label>
                            <select name="stock_status" class="b2b-select">
                                <option value="instock" <?php echo ($product && get_post_meta($product->id, '_stock_status', true) === 'instock') ? 'selected' : ''; ?>>موجود</option>
                                <option value="outofstock" <?php echo ($product && get_post_meta($product->id, '_stock_status', true) === 'outofstock') ? 'selected' : ''; ?>>ناموجود</option>
                                <option value="onbackorder" <?php echo ($product && get_post_meta($product->id, '_stock_status', true) === 'onbackorder') ? 'selected' : ''; ?>>پیش‌سفارش</option>
                            </select>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">پیش‌سفارش مجاز</label>
                            <select name="backorders" class="b2b-select">
                                <option value="no" <?php echo (!$product || get_post_meta($product->id, '_backorders', true) !== 'yes') ? 'selected' : ''; ?>>غیرمجاز</option>
                                <option value="yes" <?php echo ($product && get_post_meta($product->id, '_backorders', true) === 'yes') ? 'selected' : ''; ?>>مجاز</option>
                            </select>
                        </div>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">موجودی جداگانه هر تنوع</label>
                        <select name="sold_individually" class="b2b-select">
                            <option value="0" <?php echo (!$product || get_post_meta($product->id, '_sold_individually', true) !== 'yes') ? 'selected' : ''; ?>>خیر</option>
                            <option value="1" <?php echo ($product && get_post_meta($product->id, '_sold_individually', true) === 'yes') ? 'selected' : ''; ?>>بله - فقط یک عدد در هر سفارش</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">ابعاد و وزن</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">وزن (کیلوگرم)</label>
                            <input type="number" name="weight" class="b2b-input" step="0.001" value="<?php echo $product ? esc_attr(get_post_meta($product->id, '_weight', true)) : ''; ?>" />
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">طول (سانتی‌متر)</label>
                            <input type="number" name="length" class="b2b-input" step="0.01" value="<?php echo $product ? esc_attr(get_post_meta($product->id, '_length', true)) : ''; ?>" />
                        </div>
                    </div>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">عرض (سانتی‌متر)</label>
                            <input type="number" name="width" class="b2b-input" step="0.01" value="<?php echo $product ? esc_attr(get_post_meta($product->id, '_width', true)) : ''; ?>" />
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">ارتفاع (سانتی‌متر)</label>
                            <input type="number" name="height" class="b2b-input" step="0.01" value="<?php echo $product ? esc_attr(get_post_meta($product->id, '_height', true)) : ''; ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">تصاویر</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">تصویر اصلی</label>
                        <div class="b2b-media-upload" id="main-image-wrap">
                            <input type="hidden" name="thumbnail_id" id="thumbnail_id" value="<?php echo $product ? get_post_meta($product->id, '_thumbnail_id', true) : ''; ?>" />
                            <div class="b2b-media-preview" id="main-image-preview">
                                <?php if ($product) { $thumb = wp_get_attachment_image_src(get_post_meta($product->id, '_thumbnail_id', true)); if ($thumb) echo '<img src="' . esc_url($thumb[0]) . '" style="max-width:200px;border-radius:8px;" />'; } ?>
                            </div>
                            <button type="button" class="b2b-btn b2b-btn-secondary b2b-media-upload-btn" data-target="thumbnail_id" data-preview="main-image-preview">انتخاب تصویر</button>
                        </div>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">گالری تصاویر</label>
                        <div class="b2b-media-upload" id="gallery-wrap">
                            <input type="hidden" name="gallery_ids" id="gallery_ids" value="<?php echo $product ? get_post_meta($product->id, '_product_image_gallery', true) : ''; ?>" />
                            <div class="b2b-media-preview" id="gallery-preview" style="display:flex;gap:8px;flex-wrap:wrap;">
                                <?php if ($product) { $gallery = get_post_meta($product->id, '_product_image_gallery', true); if ($gallery) { foreach (explode(',', $gallery) as $gid) { $src = wp_get_attachment_image_src($gid); if ($src) echo '<img src="' . esc_url($src[0]) . '" style="width:80px;height:80px;object-fit:cover;border-radius:6px;" />'; } } } ?>
                            </div>
                            <button type="button" class="b2b-btn b2b-btn-secondary b2b-media-upload-btn" data-target="gallery_ids" data-preview="gallery-preview" data-multiple="true">افزودن تصاویر</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">مشخصات فنی</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">واحد شمارش</label>
                            <select name="base_unit" class="b2b-select">
                                <?php
                                $current_unit = $product ? $product->base_unit : 'pcs';
                                $unit_items = isset($units['items']) && is_array($units['items']) ? $units['items'] : array();
                                if (!empty($unit_items)) :
                                    foreach ($unit_items as $u) : ?>
                                        <option value="<?php echo esc_attr($u->short_name); ?>" <?php echo ($current_unit === $u->short_name) ? 'selected' : ''; ?>><?php echo esc_html($u->title); ?></option>
                                    <?php endforeach;
                                else : ?>
                                    <option value="pcs">عدد</option>
                                    <option value="kg">کیلوگرم</option>
                                    <option value="m">متر</option>
                                    <option value="L">لیتر</option>
                                    <option value="ton">تن</option>
                                    <option value="box">جعبه</option>
                                    <option value="pack">بسته</option>
                                    <option value="roll">رول</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">حداقل تعداد سفارش</label>
                            <input type="number" name="min_order_qty" class="b2b-input" step="0.001" value="<?php echo $product ? esc_attr($product->min_order_qty) : '1'; ?>" />
                        </div>
                    </div>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">حداکثر تعداد سفارش</label>
                            <input type="number" name="max_order_qty" class="b2b-input" step="0.001" value="<?php echo $product && $product->max_order_qty ? esc_attr($product->max_order_qty) : ''; ?>" />
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">زمان تحویل (روز)</label>
                            <input type="number" name="lead_time_days" class="b2b-input" value="<?php echo $product ? esc_attr($product->lead_time_days) : '0'; ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">وضعیت و نمایش</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">وضعیت</label>
                            <select name="status" class="b2b-select">
                                <option value="0" <?php echo ($product && $product->status == 0) ? 'selected' : ''; ?>>پیش‌نویس</option>
                                <option value="1" <?php echo ($product && $product->status == 1) ? 'selected' : ''; ?>>فعال</option>
                            </select>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">قابلیت نمایش</label>
                            <select name="visibility" class="b2b-select">
                                <option value="1" <?php echo ($product && $product->visibility == 1) ? 'selected' : ''; ?>>نمایش</option>
                                <option value="0" <?php echo ($product && $product->visibility == 0) ? 'selected' : ''; ?>>مخفی</option>
                            </select>
                        </div>
                    </div>
                    <div class="b2b-form-row">
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">محصول ویژه</label>
                            <select name="featured" class="b2b-select">
                                <option value="0" <?php echo (!$product || !get_post_meta($product->id, '_featured', true)) ? 'selected' : ''; ?>>خیر</option>
                                <option value="1" <?php echo ($product && get_post_meta($product->id, '_featured', true)) ? 'selected' : ''; ?>>بله</option>
                            </select>
                        </div>
                        <div class="b2b-form-field">
                            <label class="b2b-form-label">تعداد نمایش (ترتیب منو)</label>
                            <input type="number" name="menu_order" class="b2b-input" value="<?php echo $product ? get_post_field('menu_order', $product->id) : '0'; ?>" />
                        </div>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">یادداشت خرید</label>
                        <textarea name="purchase_note" class="b2b-textarea" rows="2" placeholder="متنی که بعد از خرید به مشتری نمایش داده می‌شود"><?php echo $product ? esc_textarea(get_post_meta($product->id, '_purchase_note', true)) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="b2b-form-actions">
                <button type="submit" class="b2b-btn b2b-btn-primary">ذخیره محصول</button>
            </div>
        </form>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== CATEGORIES LIST ====================
    public static function render_categories() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        wp_enqueue_media();
        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title">دسته‌بندی‌ها</h1><p class="b2b-workspace-subtitle">مدیریت دسته‌بندی محصولات</p></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-category-add'); ?>" class="b2b-btn b2b-btn-primary">&#10010; افزودن دسته‌بندی</a>
            </div>
        </div>
        <div class="b2b-toolbar">
            <div class="b2b-toolbar-left">
                <input type="text" id="category-search" class="b2b-search-input" placeholder="جستجو..." style="max-width:300px;" />
            </div>
            <div class="b2b-toolbar-right">
                <span id="category-count" class="b2b-text-muted"></span>
                <button type="button" class="b2b-btn b2b-btn-secondary" onclick="B2BCatalog.exportCategoriesCSV()">&#128230; خروجی CSV</button>
                <button type="button" class="b2b-btn b2b-btn-secondary" onclick="document.getElementById('csv-file-input').click()">&#128229; ورود CSV</button>
                <input type="file" id="csv-file-input" accept=".csv" style="display:none;" onchange="B2BCatalog.importCategoriesCSV()" />
            </div>
        </div>
        <div id="category-table-container"></div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }

    // ==================== CATEGORY CREATE/EDIT ====================
    public static function render_category_form() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        $is_edit = isset($_GET['id']);
        $category = null;
        if ($is_edit) {
            $cat_svc = new B2B_WC_Category_Service();
            $category = $cat_svc->get_category(intval($_GET['id']));
        }
        $title = $is_edit ? 'ویرایش دسته‌بندی' : 'افزودن دسته‌بندی جدید';

        // Get WooCommerce parent categories
        $parent_terms = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => 0,
            'exclude' => $is_edit ? array($category->id) : array(),
        ));

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div><h1 class="b2b-workspace-title"><?php echo $title; ?></h1></div>
            <div class="b2b-workspace-actions">
                <a href="<?php echo admin_url('admin.php?page=b2b-categories'); ?>" class="b2b-btn b2b-btn-secondary">بازگشت</a>
            </div>
        </div>
        <form id="category-form" class="b2b-form" style="max-width:700px;" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" name="_b2b_nonce" value="<?php echo wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION); ?>" />
            <input type="hidden" name="action" value="b2b_category_save" />
            <?php if ($is_edit) : ?><input type="hidden" name="category_id" value="<?php echo $category->id; ?>" /><?php endif; ?>

            <div class="b2b-card">
                <div class="b2b-card-header"><h2 class="b2b-card-title">اطلاعات دسته‌بندی</h2></div>
                <div class="b2b-card-body">
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">دسته‌بندی والد</label>
                        <select name="parent" class="b2b-select">
                            <option value="0">بدون والد (ریشه)</option>
                            <?php if (!is_wp_error($parent_terms)) : foreach ($parent_terms as $p) : ?>
                                <option value="<?php echo $p->term_id; ?>" <?php echo ($category && $category->parent == $p->term_id) ? 'selected' : ''; ?>><?php echo esc_html($p->name); ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">نام دسته‌بندی <span class="b2b-required">*</span></label>
                        <input type="text" name="name" class="b2b-input" required value="<?php echo $category ? esc_attr($category->name) : ''; ?>" placeholder="مثال: لوازم اداری" />
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">نامک (اختیاری)</label>
                        <input type="text" name="slug" class="b2b-input" value="<?php echo $category ? esc_attr($category->slug) : ''; ?>" placeholder="مثال: office-supplies" />
                        <p class="b2b-form-desc">در صورت خالی گذاشتن خودکار از نام ساخته می‌شود</p>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">توضیحات</label>
                        <textarea name="description" class="b2b-textarea" rows="3" placeholder="توضیحات اختیاری دسته‌بندی..."><?php echo $category ? esc_textarea($category->description) : ''; ?></textarea>
                    </div>
                    <div class="b2b-form-field">
                        <label class="b2b-form-label">تصویر دسته‌بندی</label>
                        <div class="b2b-media-wrap">
                            <div class="b2b-media-preview">
                                <?php if ($category && !empty($category->image_url)) : ?>
                                    <img src="<?php echo esc_url($category->image_url); ?>" />
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="image_url" value="<?php echo $category ? esc_attr($category->image_url ?? '') : ''; ?>" />
                            <div class="b2b-media-actions">
                                <button type="button" class="b2b-btn b2b-btn-secondary b2b-media-upload-btn">انتخاب تصویر</button>
                                <button type="button" class="b2b-btn b2b-btn-ghost b2b-media-remove-btn">حذف</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="b2b-form-actions">
                <button type="submit" class="b2b-btn b2b-btn-primary">ذخیره دسته‌بندی</button>
            </div>
        </form>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
