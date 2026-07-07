<?php
defined('ABSPATH') || exit;

class B2B_WC_Product_Service {

    private $validator;

    public function __construct() {
        $this->validator = new B2B_Product_Validator();
    }

    public function get_products($args = array()) {
        $defaults = array(
            'search' => '',
            'status' => '',
            'category_id' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        $args = wp_parse_args($args, $defaults);

        $query_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $args['per_page'],
            'paged' => $args['page'],
        );

        if (!empty($args['search'])) {
            $query_args['s'] = $args['search'];
        }

        if (!empty($args['category_id'])) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => intval($args['category_id']),
                ),
            );
        }

        if ($args['orderby'] === 'date') {
            $query_args['orderby'] = 'date';
        } else {
            $query_args['orderby'] = 'title';
        }
        $query_args['order'] = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $products = get_posts($query_args);
        $total_query = new WP_Query($query_args);
        $total = $total_query->found_posts;

        $items = array();
        foreach ($products as $product) {
            $items[] = $this->normalize($product);
        }

        return array(
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
        );
    }

    public function get_product($id) {
        $id = intval($id);

        // Try WooCommerce first for full compatibility
        if (function_exists('wc_get_product')) {
            $wc_product = wc_get_product($id);
            if ($wc_product) {
                $post = get_post($id);
                if ($post && $post->post_type === 'product') {
                    return $this->normalize_from_wc($wc_product, $post);
                }
            }
        }

        // Fallback to direct post query
        $post = get_post($id);
        if (!$post || $post->post_type !== 'product') {
            return null;
        }
        return $this->normalize($post);
    }

    public function create($data) {
        $clean = $this->validator->sanitize($data);
        if (!$this->validator->validate($clean)) {
            return array('success' => false, 'errors' => $this->validator->get_errors());
        }

        $product_id = wp_insert_post(array(
            'post_title' => $clean['name_fa'],
            'post_content' => $clean['description'],
            'post_excerpt' => $clean['short_desc'],
            'post_status' => $clean['status'] == 1 ? 'publish' : 'draft',
            'post_type' => 'product',
            'post_name' => $clean['slug'],
        ));

        if (is_wp_error($product_id)) {
            return array('success' => false, 'errors' => array('general' => $product_id->get_error_message()));
        }

        // Set WooCommerce product type
        wp_set_object_terms($product_id, 'simple', 'product_type');

        // Set category
        if (!empty($clean['category_id'])) {
            wp_set_object_terms($product_id, intval($clean['category_id']), 'product_cat');
        }

        // Save custom meta
        $this->save_meta($product_id, $clean);

        return array('success' => true, 'id' => $product_id, 'message' => 'محصول با موفقیت ایجاد شد');
    }

    public function update($id, $data) {
        $product = wc_get_product($id);
        if (!$product) {
            return array('success' => false, 'errors' => array('general' => 'محصول یافت نشد'));
        }

        $clean = $this->validator->sanitize($data);
        if (!$this->validator->validate($clean, true, $id)) {
            return array('success' => false, 'errors' => $this->validator->get_errors());
        }

        wp_update_post(array(
            'ID' => $id,
            'post_title' => $clean['name_fa'],
            'post_content' => $clean['description'],
            'post_excerpt' => $clean['short_desc'],
            'post_status' => $clean['status'] == 1 ? 'publish' : 'draft',
            'post_name' => $clean['slug'],
        ));

        // Update category
        wp_set_object_terms($id, !empty($clean['category_id']) ? intval($clean['category_id']) : array(), 'product_cat');

        // Update custom meta
        $this->save_meta($id, $clean);

        return array('success' => true, 'message' => 'محصول با موفقیت بروزرسانی شد');
    }

    public function delete($id, $permanent = false) {
        $product = wc_get_product($id);
        if (!$product) {
            return array('success' => false, 'errors' => array('general' => 'محصول یافت نشد'));
        }

        if ($permanent) {
            wp_delete_post($id, true);
        } else {
            wp_update_post(array(
                'ID' => $id,
                'post_status' => 'trash',
            ));
        }

        $msg = $permanent ? 'محصول برای همیشه حذف شد' : 'محصول به زباله‌دان منتقل شد';
        return array('success' => true, 'message' => $msg);
    }

    public function restore($id) {
        wp_update_post(array(
            'ID' => $id,
            'post_status' => 'publish',
        ));
        return array('success' => true, 'message' => 'محصول بازیابی شد');
    }

    public function toggle_status($id) {
        $product = wc_get_product($id);
        if (!$product) {
            return array('success' => false, 'errors' => array('general' => 'محصول یافت نشد'));
        }

        $new_status = $product->get_status() === 'publish' ? 'draft' : 'publish';
        $product->set_status($new_status);
        $product->save();

        return array('success' => true, 'message' => 'وضعیت محصول تغییر کرد');
    }

    public function get_product_attributes($product_id) {
        $attrs = array();
        $raw_attrs = get_post_meta($product_id, '_b2b_attributes', true);
        if (is_array($raw_attrs)) {
            foreach ($raw_attrs as $attr) {
                $attr_def = get_post_meta($attr['attribute_id'], '', true);
                $attrs[] = array(
                    'id' => $attr['attribute_id'],
                    'name' => $attr_def['name'] ?? '',
                    'code' => $attr_def['code'] ?? '',
                    'value_text' => $attr['value_text'] ?? '',
                    'value_number' => $attr['value_number'] ?? null,
                );
            }
        }
        return $attrs;
    }

    public function set_product_attributes($product_id, $attributes) {
        $clean_attrs = array();
        foreach ($attributes as $attr) {
            $attr_id = intval($attr['attribute_id'] ?? 0);
            $value_text = sanitize_text_field($attr['value_text'] ?? '');
            $value_number = !empty($attr['value_number']) ? floatval($attr['value_number']) : null;

            if ($attr_id && ($value_text !== '' || $value_number !== null)) {
                $clean_attrs[] = array(
                    'attribute_id' => $attr_id,
                    'value_text' => $value_text,
                    'value_number' => $value_number,
                );
            }
        }
        update_post_meta($product_id, '_b2b_attributes', $clean_attrs);
    }

    public function get_stats() {
        $total = wp_count_posts('product');
        return array(
            'total' => $total->publish + $total->draft + $total->trash,
            'active' => $total->publish,
            'draft' => $total->draft,
            'trash' => $total->trash,
        );
    }

    private function save_meta($product_id, $data) {
        $meta_fields = array(
            'sku' => $data['sku'],
            'base_unit' => $data['base_unit'],
            'weight' => $data['weight'],
            'weight_unit' => $data['weight_unit'],
            'min_order_qty' => $data['min_order_qty'],
            'max_order_qty' => $data['max_order_qty'],
            'lead_time_days' => $data['lead_time_days'],
            'visibility' => $data['visibility'],
            'has_variants' => $data['has_variants'],
            'has_attributes' => $data['has_attributes'],
            'meta' => $data['meta'],
            'tags' => $data['tags'],
            'images' => $data['images'],
        );

        foreach ($meta_fields as $key => $value) {
            update_post_meta($product_id, '_b2b_' . $key, $value);
        }

        // Also save SKU in WooCommerce meta
        update_post_meta($product_id, '_sku', $data['sku']);
    }

    private function normalize($post, $wc_product = null) {
        $id = $post->ID;

        // Get product images - prefer WC methods
        $images = array();
        if ($wc_product && method_exists($wc_product, 'get_image_id')) {
            $thumb_id = $wc_product->get_image_id();
            if ($thumb_id) {
                $url = wp_get_attachment_url($thumb_id);
                if ($url) $images[] = $url;
            }
            $gallery_ids = $wc_product->get_gallery_image_ids();
            if (!empty($gallery_ids)) {
                foreach ($gallery_ids as $gid) {
                    $url = wp_get_attachment_url($gid);
                    if ($url) $images[] = $url;
                }
            }
        } else {
            $thumbnail_id = get_post_thumbnail_id($id);
            if ($thumbnail_id) {
                $img_url = wp_get_attachment_url($thumbnail_id);
                if ($img_url) $images[] = $img_url;
            }
            $gallery = get_post_meta($id, '_product_gallery', true);
            if ($gallery && is_array($gallery)) {
                foreach ($gallery as $att_id) {
                    $url = wp_get_attachment_url($att_id);
                    if ($url) $images[] = $url;
                }
            }
        }

        // Get prices - prefer WC methods
        $regular_price = '';
        $sale_price = '';
        if ($wc_product && method_exists($wc_product, 'get_regular_price')) {
            $regular_price = $wc_product->get_regular_price();
            $sale_price = $wc_product->get_sale_price();
        } else {
            $regular_price = get_post_meta($id, '_regular_price', true) ?: '';
            $sale_price = get_post_meta($id, '_sale_price', true) ?: '';
        }

        $product = array(
            'id' => $id,
            'sku' => get_post_meta($id, '_sku', true) ?: '',
            'name_fa' => $post->post_title,
            'name_en' => get_post_meta($id, '_b2b_name_en', true) ?: $post->post_title,
            'regular_price' => $regular_price,
            'sale_price' => $sale_price,
            'slug' => $post->post_name,
            'description' => $post->post_content,
            'short_desc' => $post->post_excerpt,
            'category_id' => '',
            'category_name' => '',
            'base_unit' => get_post_meta($id, '_b2b_base_unit', true) ?: 'pcs',
            'weight' => get_post_meta($id, '_weight', true) ?: null,
            'weight_unit' => get_post_meta($id, '_b2b_weight_unit', true) ?: 'kg',
            'min_order_qty' => get_post_meta($id, '_b2b_min_order_qty', true) ?: 1,
            'max_order_qty' => get_post_meta($id, '_b2b_max_order_qty', true) ?: null,
            'lead_time_days' => get_post_meta($id, '_b2b_lead_time_days', true) ?: 0,
            'status' => $post->post_status === 'publish' ? 1 : 0,
            'visibility' => get_post_meta($id, '_b2b_visibility', true) ?: 1,
            'has_variants' => get_post_meta($id, '_b2b_has_variants', true) ?: 0,
            'has_attributes' => get_post_meta($id, '_b2b_has_attributes', true) ?: 0,
            'meta' => get_post_meta($id, '_b2b_meta', true) ?: null,
            'tags' => get_post_meta($id, '_b2b_tags', true) ?: null,
            'images' => $images,
            'deleted_at' => $post->post_status === 'trash' ? $post->post_modified : null,
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified,
        );

        // Get category name
        $terms = get_the_terms($post->ID, 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            $product['category_id'] = $terms[0]->term_id;
            $product['category_name'] = $terms[0]->name;
        }

        return $product;
    }

    private function normalize_from_wc($wc_product, $post = null) {
        if (!$post) {
            $post = get_post($wc_product->get_id());
        }
        if (!$post) {
            return null;
        }
        return $this->normalize($post, $wc_product);
    }
}
