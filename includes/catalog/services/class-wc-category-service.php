<?php
defined('ABSPATH') || exit;

class B2B_WC_Category_Service {

    public function get_categories($args = array()) {
        $defaults = array(
            'search' => '',
            'parent' => 0,
            'per_page' => 20,
            'page' => 1,
        );
        $args = wp_parse_args($args, $defaults);

        $query_args = array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'per_page' => $args['per_page'],
            'offset' => ($args['page'] - 1) * $args['per_page'],
        );

        if (!empty($args['search'])) {
            $query_args['search'] = $args['search'];
        }

        if ($args['parent'] > 0) {
            $query_args['parent'] = $args['parent'];
        }

        $terms = get_terms($query_args);
        $total = 0;
        $items = array();

        if (!is_wp_error($terms)) {
            $total = count($terms);
            foreach ($terms as $term) {
                $items[] = $this->normalize($term);
            }
        }

        // Get total count
        $count_args = array('taxonomy' => 'product_cat', 'hide_empty' => false);
        if (!empty($args['search'])) {
            $count_args['search'] = $args['search'];
        }
        if ($args['parent'] > 0) {
            $count_args['parent'] = $args['parent'];
        }
        $all_terms = get_terms($count_args);
        $total = is_wp_error($all_terms) ? 0 : count($all_terms);

        return array(
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
        );
    }

    public function get_category($id) {
        $term = get_term($id, 'product_cat');
        if (is_wp_error($term)) {
            return null;
        }
        return $this->normalize($term);
    }

    public function create($data) {
        $term_data = array(
            'name' => sanitize_text_field($data['name'] ?? ''),
            'slug' => sanitize_title($data['slug'] ?? $data['name'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'parent' => intval($data['parent'] ?? 0),
        );

        if (empty($term_data['name'])) {
            return array('success' => false, 'errors' => array('name' => 'نام دسته‌بندی الزامی است'));
        }

        // Check if term exists
        $existing = term_exists($term_data['name'], 'product_cat');
        if ($existing) {
            return array('success' => false, 'errors' => array('name' => 'دسته‌بندی با این نام وجود دارد'));
        }

        $result = wp_insert_term($term_data['name'], 'product_cat', $term_data);

        if (is_wp_error($result)) {
            return array('success' => false, 'errors' => array('general' => $result->get_error_message()));
        }

        // Save thumbnail image
        if (!empty($data['image_url'])) {
            $attachment_id = attachment_url_to_postid($data['image_url']);
            if ($attachment_id) {
                update_term_meta($result['term_id'], 'thumbnail_id', $attachment_id);
            }
        }

        return array('success' => true, 'id' => $result['term_id'], 'message' => 'دسته‌بندی با موفقیت ایجاد شد');
    }

    public function update($id, $data) {
        $term = get_term($id, 'product_cat');
        if (!$term) {
            return array('success' => false, 'errors' => array('general' => 'دسته‌بندی یافت نشد'));
        }

        $term_data = array(
            'name' => sanitize_text_field($data['name'] ?? $term->name),
            'slug' => sanitize_title($data['slug'] ?? $term->slug),
            'description' => sanitize_textarea_field($data['description'] ?? $term->description),
            'parent' => intval($data['parent'] ?? $term->parent),
        );

        $result = wp_update_term($id, 'product_cat', $term_data);

        if (!is_wp_error($result)) {
            // Save thumbnail image
            if (isset($data['image_url'])) {
                if (!empty($data['image_url'])) {
                    $attachment_id = attachment_url_to_postid($data['image_url']);
                    if ($attachment_id) {
                        update_term_meta($id, 'thumbnail_id', $attachment_id);
                    }
                } else {
                    delete_term_meta($id, 'thumbnail_id');
                }
            }
        }

        if (is_wp_error($result)) {
            return array('success' => false, 'errors' => array('general' => $result->get_error_message()));
        }

        return array('success' => true, 'message' => 'دسته‌بندی با موفقیت بروزرسانی شد');
    }

    public function delete($id) {
        $result = wp_delete_term($id, 'product_cat');
        if (is_wp_error($result)) {
            return array('success' => false, 'errors' => array('general' => $result->get_error_message()));
        }
        return array('success' => true, 'message' => 'دسته‌بندی با موفقیت حذف شد');
    }

    public function get_stats() {
        $terms = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
        $total = is_wp_error($terms) ? 0 : count($terms);

        return array(
            'total' => $total,
            'active' => $total,
            'inactive' => 0,
        );
    }

    private function normalize($term) {
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';

        return (object) array(
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'parent' => $term->parent,
            'count' => $term->count,
            'image_url' => $image_url,
        );
    }
}
