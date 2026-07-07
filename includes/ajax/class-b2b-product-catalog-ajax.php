<?php
defined('ABSPATH') || exit;

class B2B_Product_Catalog_Ajax {

    public static function init() {
        add_action('wp_ajax_b2b_catalog_get_products', array(__CLASS__, 'get_products'));
        add_action('wp_ajax_b2b_catalog_get_categories', array(__CLASS__, 'get_categories'));
        add_action('wp_ajax_b2b_product_save', array(__CLASS__, 'save_product'));
        add_action('wp_ajax_b2b_category_save', array(__CLASS__, 'save_category'));
        add_action('wp_ajax_b2b_catalog_delete_product', array(__CLASS__, 'delete_product'));
        add_action('wp_ajax_b2b_catalog_delete_category', array(__CLASS__, 'delete_category'));
        add_action('wp_ajax_b2b_catalog_move_category', array(__CLASS__, 'move_category'));
        add_action('wp_ajax_b2b_catalog_export_categories', array(__CLASS__, 'export_categories'));
        add_action('wp_ajax_b2b_catalog_import_categories', array(__CLASS__, 'import_categories'));
        add_action('wp_ajax_b2b_get_thumb', array(__CLASS__, 'get_thumb'));
        add_action('wp_ajax_b2b_catalog_bulk_products', array(__CLASS__, 'bulk_products'));
        add_action('wp_ajax_b2b_catalog_clone_product', array(__CLASS__, 'clone_product'));
        add_action('wp_ajax_b2b_catalog_export_products', array(__CLASS__, 'export_products'));
        add_action('wp_ajax_b2b_catalog_import_products', array(__CLASS__, 'import_products'));
    }

    private static function check() {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
        }
        if (!isset($_POST['_b2b_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_b2b_nonce'])), 'b2b_procurement_nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی'));
        }
        return true;
    }

    public static function get_products() {
        self::check();
        $svc = new B2B_WC_Product_Service();
        $args = array(
            'search' => sanitize_text_field(wp_unslash($_POST['search'] ?? '')),
            'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? '')),
            'category_id' => intval($_POST['category_id'] ?? 0),
            'per_page' => max(1, intval($_POST['per_page'] ?? 20)),
            'page' => max(1, intval($_POST['page'] ?? 1)),
        );

        try {
            $result = $svc->get_products($args);
        } catch (Exception $e) {
            wp_send_json_success(array('items' => array(), 'total' => 0, 'pages' => 0, 'page' => 1));
            return;
        }

        if (!is_array($result) || !isset($result['items'])) {
            wp_send_json_success(array('items' => array(), 'total' => 0, 'pages' => 0, 'page' => 1));
            return;
        }

        // Map unit short_name to title
        $unit_map = self::get_unit_map();
        foreach ($result['items'] as &$item) {
            if (is_object($item)) {
                $item->base_unit_name = isset($unit_map[$item->base_unit]) ? $unit_map[$item->base_unit] : $item->base_unit;
            }
        }
        unset($item);

        wp_send_json_success($result);
    }

    private static function get_unit_map() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';
        $rows = $wpdb->get_results("SELECT title, short_name FROM {$table} WHERE deleted_at IS NULL");
        $map = array();
        foreach ($rows as $row) {
            $map[$row->short_name] = $row->title;
        }
        return $map;
    }

    public static function get_categories() {
        self::check();
        $svc = new B2B_WC_Category_Service();
        $args = array(
            'search' => sanitize_text_field(wp_unslash($_POST['search'] ?? '')),
            'per_page' => max(1, intval($_POST['per_page'] ?? 20)),
            'page' => max(1, intval($_POST['page'] ?? 1)),
        );
        $result = $svc->get_categories($args);
        $data = array();
        foreach ($result['items'] as $item) {
            $data[] = (array) $item;
        }
        wp_send_json_success(array('items' => $data, 'total' => $result['total'], 'pages' => $result['pages'], 'page' => $result['page']));
    }

    public static function save_product() {
        if (!isset($_POST['_b2b_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_b2b_nonce'])), 'b2b_procurement_nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی'));
        }
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
        }

        $svc = new B2B_WC_Product_Service();
        $id = intval($_POST['product_id'] ?? 0);
        $data = array(
            'sku' => sanitize_text_field(wp_unslash($_POST['sku'] ?? '')),
            'name_fa' => sanitize_text_field(wp_unslash($_POST['name_fa'] ?? '')),
            'name_en' => sanitize_text_field(wp_unslash($_POST['name_en'] ?? '')),
            'slug' => sanitize_title(wp_unslash($_POST['name_en'] ?? '')),
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'short_desc' => sanitize_text_field(wp_unslash($_POST['short_desc'] ?? '')),
            'category_id' => intval($_POST['category_id'] ?? 0),
            'base_unit' => sanitize_text_field(wp_unslash($_POST['base_unit'] ?? 'pcs')),
            'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
            'weight_unit' => sanitize_text_field(wp_unslash($_POST['weight_unit'] ?? 'kg')),
            'min_order_qty' => floatval($_POST['min_order_qty'] ?? 1),
            'max_order_qty' => !empty($_POST['max_order_qty']) ? floatval($_POST['max_order_qty']) : null,
            'lead_time_days' => intval($_POST['lead_time_days'] ?? 0),
            'status' => intval($_POST['status'] ?? 0),
            'visibility' => intval($_POST['visibility'] ?? 1),
            'has_variants' => intval($_POST['has_variants'] ?? 0),
            'has_attributes' => intval($_POST['has_attributes'] ?? 0),
            'regular_price' => !empty($_POST['regular_price']) ? floatval($_POST['regular_price']) : '',
            'sale_price' => !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : '',
        );

        if ($id > 0) {
            $result = $svc->update($id, $data);
        } else {
            $result = $svc->create($data);
        }

        // Save price to WooCommerce
        if ($result['success'] && !empty($data['regular_price'])) {
            $wc_product = wc_get_product($result['id'] ?? $id);
            if ($wc_product) {
                $wc_product->set_regular_price($data['regular_price']);
                if (!empty($data['sale_price'])) {
                    $wc_product->set_sale_price($data['sale_price']);
                }
                $wc_product->save();
            }
        }

        if ($result['success']) {
            $product_id = $result['id'] ?? $id;

            // Save thumbnail
            $thumbnail_id = intval($_POST['thumbnail_id'] ?? 0);
            if ($thumbnail_id > 0) {
                set_post_thumbnail($product_id, $thumbnail_id);
            } else {
                delete_post_thumbnail($product_id);
            }

            // Save gallery
            $gallery_raw = sanitize_text_field(wp_unslash($_POST['gallery_ids'] ?? ''));
            if (!empty($gallery_raw)) {
                $gallery_ids = array_filter(array_map('intval', explode(',', $gallery_raw)));
                update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
            } else {
                update_post_meta($product_id, '_product_image_gallery', '');
            }

            // Save additional WC meta
            update_post_meta($product_id, '_tax_status', sanitize_text_field(wp_unslash($_POST['tax_status'] ?? 'none')));
            update_post_meta($product_id, '_tax_class', sanitize_text_field(wp_unslash($_POST['tax_class'] ?? '')));
            update_post_meta($product_id, '_manage_stock', intval($_POST['manage_stock'] ?? 0) ? 'yes' : 'no');
            if (isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '') update_post_meta($product_id, '_stock', intval($_POST['stock_quantity']));
            update_post_meta($product_id, '_stock_status', sanitize_text_field(wp_unslash($_POST['stock_status'] ?? 'instock')));
            update_post_meta($product_id, '_backorders', sanitize_text_field(wp_unslash($_POST['backorders'] ?? 'no')));
            update_post_meta($product_id, '_sold_individually', intval($_POST['sold_individually'] ?? 0) ? 'yes' : '');
            update_post_meta($product_id, '_length', !empty($_POST['length']) ? floatval($_POST['length']) : '');
            update_post_meta($product_id, '_width', !empty($_POST['width']) ? floatval($_POST['width']) : '');
            update_post_meta($product_id, '_height', !empty($_POST['height']) ? floatval($_POST['height']) : '');
            update_post_meta($product_id, '_featured', intval($_POST['featured'] ?? 0));
            update_post_meta($product_id, 'menu_order', intval($_POST['menu_order'] ?? 0));
            update_post_meta($product_id, '_purchase_note', sanitize_textarea_field(wp_unslash($_POST['purchase_note'] ?? '')));

            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => reset($result['errors']), 'errors' => $result['errors']));
        }
    }

    public static function save_category() {
        if (!isset($_POST['_b2b_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_b2b_nonce'])), 'b2b_procurement_nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی'));
        }
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
        }

        $svc = new B2B_WC_Category_Service();
        $id = intval($_POST['category_id'] ?? 0);
        $data = array(
            'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'slug' => sanitize_text_field(wp_unslash($_POST['slug'] ?? '')),
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'parent' => intval($_POST['parent'] ?? 0),
            'image_url' => esc_url_raw(wp_unslash($_POST['image_url'] ?? '')),
        );

        if ($id > 0) {
            $result = $svc->update($id, $data);
        } else {
            $result = $svc->create($data);
        }

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => reset($result['errors']), 'errors' => $result['errors']));
        }
    }

    public static function delete_product() {
        self::check();
        $svc = new B2B_WC_Product_Service();
        $id = intval($_POST['product_id'] ?? 0);
        $result = $svc->delete($id);
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => reset($result['errors'])));
        }
    }

    public static function delete_category() {
        self::check();
        $svc = new B2B_WC_Category_Service();
        $id = intval($_POST['category_id'] ?? 0);
        $result = $svc->delete($id);
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => reset($result['errors'])));
        }
    }

    public static function move_category() {
        self::check();
        $cat_id = intval($_POST['category_id'] ?? 0);
        $target_id = intval($_POST['target_id'] ?? 0);

        if ($cat_id <= 0) {
            wp_send_json_error(array('message' => 'شناسه دسته‌بندی نامعتبر'));
        }

        if ($cat_id === $target_id) {
            wp_send_json_error(array('message' => 'دسته‌بندی نمی‌تواند والد خودش باشد'));
        }

        $result = wp_update_term($cat_id, 'product_cat', array('parent' => $target_id));
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'دسته‌بندی با موفقیت جابجا شد'));
    }

    public static function export_categories() {
        self::check();
        $terms = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false, 'number' => 0));

        if (is_wp_error($terms)) {
            wp_send_json_error(array('message' => 'خطا در دریافت دسته‌بندی‌ها'));
        }

        $csv = "نام,نامک,تعداد محصول,والد\n";
        foreach ($terms as $term) {
            $parent_name = '';
            if ($term->parent > 0) {
                $parent = get_term($term->parent, 'product_cat');
                $parent_name = $parent ? $parent->name : '';
            }
            $csv .= '"' . str_replace('"', '""', $term->name) . '",';
            $csv .= '"' . $term->slug . '",';
            $csv .= $term->count . ',';
            $csv .= '"' . str_replace('"', '""', $parent_name) . '"\n';
        }

        wp_send_json_success(array('csv' => $csv));
    }

    public static function import_categories() {
        self::check();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'فایل آپلود نشد'));
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            wp_send_json_error(array('message' => 'خطا در خواندن فایل'));
        }

        $imported = 0;
        $skipped = 0;
        $line = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if ($line === 1) continue; // Skip header

            if (count($row) < 1 || empty($row[0])) {
                $skipped++;
                continue;
            }

            $name = sanitize_text_field($row[0]);
            $parent = 0;

            // Find parent by name
            if (!empty($row[3])) {
                $parent_term = term_exists(trim($row[3]), 'product_cat');
                if ($parent_term) {
                    $parent = is_array($parent_term) ? intval($parent_term['term_id']) : intval($parent_term);
                }
            }

            $existing = term_exists($name, 'product_cat');
            if ($existing) {
                $skipped++;
                continue;
            }

            $result = wp_insert_term($name, 'product_cat', array('parent' => $parent));
            if (!is_wp_error($result)) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        fclose($handle);

        wp_send_json_success(array(
            'message' => " {$imported} دسته‌بندی با موفقیت وارد شد. {$skipped} مورد رد شد.",
            'imported' => $imported,
            'skipped' => $skipped,
        ));
    }

    public static function bulk_products() {
        self::check();
        $action = sanitize_text_field(wp_unslash($_POST['bulk_action'] ?? ''));
        $ids = array_map('intval', $_POST['ids'] ?? array());

        if (empty($ids)) {
            wp_send_json_error(array('message' => 'موردی انتخاب نشده'));
        }

        $svc = new B2B_WC_Product_Service();
        $count = 0;

        foreach ($ids as $id) {
            switch ($action) {
                case 'status_active':
                    $product = wc_get_product($id);
                    if ($product) { $product->set_status('publish'); $product->save(); $count++; }
                    break;
                case 'status_inactive':
                    $product = wc_get_product($id);
                    if ($product) { $product->set_status('draft'); $product->save(); $count++; }
                    break;
                case 'delete':
                    wp_update_post(array('ID' => $id, 'post_status' => 'trash'));
                    $count++;
                    break;
            }
        }

        wp_send_json_success(array('message' => count($ids) . ' محصول پردازش شد'));
    }

    public static function clone_product() {
        self::check();
        $id = intval($_POST['product_id'] ?? 0);

        $post = get_post($id);
        if (!$post || $post->post_type !== 'product') {
            wp_send_json_error(array('message' => 'محصول یافت نشد'));
        }

        $new_name = $post->post_title . ' - کپی ' . date('Y-m-d H:i');
        $new_slug = sanitize_title($new_name);

        // Create brand new post - do NOT pass ID
        $new_id = wp_insert_post(array(
            'post_author' => get_current_user_id(),
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_title' => $new_name,
            'post_status' => 'draft',
            'post_type' => 'product',
            'post_name' => $new_slug,
            'comment_status' => $post->comment_status,
            'ping_status' => $post->ping_status,
            'menu_order' => $post->menu_order,
            'post_parent' => $post->post_parent,
        ));

        if (!$new_id || is_wp_error($new_id)) {
            wp_send_json_error(array('message' => 'خطا در ایجاد محصول جدید: ' . ($new_id->get_error_message() ?? '')));
        }

        // Copy all post meta except system fields
        $meta = get_post_meta($id);
        foreach ($meta as $key => $value) {
            if (in_array($key, array('_edit_lock', '_edit_last', '_thumbnail_id'))) continue;
            update_post_meta($new_id, $key, maybe_unserialize($value));
        }

        // Set new unique SKU
        $old_sku = get_post_meta($id, '_sku', true);
        $new_sku = $old_sku ? $old_sku . '-' . $new_id : 'PRD-' . $new_id;
        update_post_meta($new_id, '_sku', $new_sku);

        // Copy product type
        $terms = wp_get_post_terms($id, 'product_type');
        if ($terms && !is_wp_error($terms)) {
            wp_set_object_terms($new_id, wp_list_pluck($terms, 'term_id'), 'product_type');
        }

        // Copy categories
        $cat_terms = wp_get_post_terms($id, 'product_cat');
        if ($cat_terms && !is_wp_error($cat_terms)) {
            wp_set_object_terms($new_id, wp_list_pluck($cat_terms, 'term_id'), 'product_cat');
        }

        wp_send_json_success(array('message' => 'محصول با موفقیت کپی شد', 'id' => $new_id));
    }

    public static function export_products() {
        self::check();
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        if (!empty($_POST['category_id'])) {
            $args['tax_query'] = array(array('taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => intval($_POST['category_id'])));
        }

        $products = get_posts($args);
        $csv = "SKU,نام فارسی,نام انگلیسی,توضیحات,واحد,حداقل تعداد,زمان تحویل,وضعیت\n";

        foreach ($products as $post) {
            $sku = get_post_meta($post->ID, '_sku', true);
            $name_en = get_post_meta($post->ID, '_b2b_name_en', true);
            $unit = get_post_meta($post->ID, '_b2b_base_unit', true);
            $min_qty = get_post_meta($post->ID, '_b2b_min_order_qty', true);
            $lead_time = get_post_meta($post->ID, '_b2b_lead_time_days', true);

            $csv .= '"' . str_replace('"', '""', $sku) . '",';
            $csv .= '"' . str_replace('"', '""', $post->post_title) . '",';
            $csv .= '"' . str_replace('"', '""', $name_en) . '",';
            $csv .= '"' . str_replace('"', '""', $post->post_content) . '",';
            $csv .= '"' . $unit . '",';
            $csv .= $min_qty . ',';
            $csv .= $lead_time . ',';
            $csv .= '"' . $post->post_status . '"\n';
        }

        wp_send_json_success(array('csv' => $csv));
    }

    public static function import_products() {
        self::check();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'فایل آپلود نشد'));
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            wp_send_json_error(array('message' => 'خطا در خواندن فایل'));
        }

        $imported = 0;
        $skipped = 0;
        $line = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if ($line === 1) continue;

            if (count($row) < 2 || empty($row[0]) || empty($row[1])) {
                $skipped++;
                continue;
            }

            $sku = sanitize_text_field($row[0]);
            $name = sanitize_text_field($row[1]);

            // Check if SKU exists
            $existing = get_posts(array('post_type' => 'product', 'meta_key' => '_sku', 'meta_value' => $sku, 'posts_per_page' => 1));
            if (!empty($existing)) {
                $skipped++;
                continue;
            }

            $product_id = wp_insert_post(array(
                'post_title' => $name,
                'post_content' => isset($row[3]) ? sanitize_textarea_field($row[3]) : '',
                'post_excerpt' => '',
                'post_status' => 'draft',
                'post_type' => 'product',
                'post_name' => sanitize_title($name),
            ));

            if ($product_id && !is_wp_error($product_id)) {
                wp_set_object_terms($product_id, 'simple', 'product_type');
                update_post_meta($product_id, '_sku', $sku);
                if (!empty($row[4])) update_post_meta($product_id, '_b2b_base_unit', sanitize_text_field($row[4]));
                if (!empty($row[5])) update_post_meta($product_id, '_b2b_min_order_qty', floatval($row[5]));
                if (!empty($row[6])) update_post_meta($product_id, '_b2b_lead_time_days', intval($row[6]));
                $imported++;
            } else {
                $skipped++;
            }
        }

        fclose($handle);

        wp_send_json_success(array(
            'message' => "{$imported} محصول با موفقیت وارد شد. {$skipped} مورد رد شد.",
            'imported' => $imported,
            'skipped' => $skipped,
        ));
    }

    public static function get_thumb() {
        $id = intval($_GET['id'] ?? 0);
        if ($id) {
            $src = wp_get_attachment_image_src($id, 'thumbnail');
            if ($src) {
                header('Content-Type: image/jpeg');
                header('Cache-Control: public, max-age=86400');
                // Redirect to actual image URL
                wp_redirect($src[0]);
                exit;
            }
        }
        // Return 1x1 transparent pixel if not found
        header('Content-Type: image/gif');
        header('Cache-Control: public, max-age=3600');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }
}
