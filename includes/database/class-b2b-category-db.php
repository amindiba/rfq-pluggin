<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Category_DB {

    public static function get_categories($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_categories';

        $defaults = array(
            'search' => '',
            'status' => '',
            'parent_id' => null,
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'sort_order',
            'order' => 'ASC',
            'include_deleted' => false,
        );
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(name_fa LIKE %s OR name_en LIKE %s OR slug LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s;
            $values[] = $s;
            $values[] = $s;
        }

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $values[] = $args['status'];
        }

        if ($args['parent_id'] !== null) {
            $where[] = "parent_id = %d";
            $values[] = intval($args['parent_id']);
        }

        if (!$args['include_deleted']) {
            $where[] = "deleted_at IS NULL";
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", $values));
            $params = array_merge($values, array($args['per_page'], $offset));
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d", $params));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}");
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        return array('items' => $items ?: array(), 'total' => $total, 'pages' => ceil($total / $args['per_page']), 'page' => $args['page'], 'per_page' => $args['per_page']);
    }

    public static function get_category($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_categories WHERE id = %d", intval($id)));
    }

    public static function get_children($parent_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_categories WHERE parent_id = %d AND deleted_at IS NULL ORDER BY sort_order ASC", intval($parent_id)));
    }

    public static function get_tree() {
        $all = self::get_categories(array('per_page' => 9999, 'include_deleted' => false));
        $tree = array();
        $lookup = array();

        foreach ($all['items'] as $cat) {
            $cat->children = array();
            $lookup[$cat->id] = $cat;
        }

        foreach ($lookup as $cat) {
            if ($cat->parent_id && isset($lookup[$cat->parent_id])) {
                $lookup[$cat->parent_id]->children[] = $cat;
            } else {
                $tree[] = $cat;
            }
        }

        return $tree;
    }

    public static function create_category($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_categories';

        $parent_id = intval($data['parent_id'] ?? 0);
        $depth = 0;
        $path = '/';

        if ($parent_id > 0) {
            $parent = self::get_category($parent_id);
            if ($parent) {
                $depth = $parent->depth + 1;
                $path = $parent->path . $parent->id . '/';
            }
        }

        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE slug = %s AND deleted_at IS NOT NULL", sanitize_title($data['name_en'])));

        $result = $wpdb->insert($table, array(
            'parent_id' => $parent_id ?: null,
            'name_fa' => sanitize_text_field($data['name_fa']),
            'name_en' => sanitize_text_field($data['name_en']),
            'slug' => sanitize_title($data['name_en']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'icon' => sanitize_text_field($data['icon'] ?? ''),
            'image_url' => esc_url_raw($data['image_url'] ?? ''),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'depth' => $depth,
            'path' => $path,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        return $result === false ? new WP_Error('db_error', $wpdb->last_error) : $wpdb->insert_id;
    }

    public static function update_category($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_categories';

        $parent_id = intval($data['parent_id'] ?? 0);
        $depth = 0;
        $path = '/';

        if ($parent_id > 0 && $parent_id != $id) {
            $parent = self::get_category($parent_id);
            if ($parent) {
                $depth = $parent->depth + 1;
                $path = $parent->path . $parent->id . '/';
            }
        }

        return $wpdb->update($table, array(
            'parent_id' => $parent_id ?: null,
            'name_fa' => sanitize_text_field($data['name_fa']),
            'name_en' => sanitize_text_field($data['name_en']),
            'slug' => sanitize_title($data['name_en']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'icon' => sanitize_text_field($data['icon'] ?? ''),
            'image_url' => esc_url_raw($data['image_url'] ?? ''),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'depth' => $depth,
            'path' => $path,
            'updated_at' => current_time('mysql'),
        ), array('id' => intval($id)));
    }

    public static function delete_category($id, $permanent = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_categories';

        $children = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE parent_id = %d AND deleted_at IS NULL", intval($id)));
        if ($children > 0) {
            return new WP_Error('has_children', 'این دسته‌بندی دارای زیرمجموعه است و قابل حذف نیست.');
        }

        $products = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}b2b_products WHERE category_id = %d AND deleted_at IS NULL", intval($id)));
        if ($products > 0) {
            return new WP_Error('has_products', 'این دسته‌بندی دارای محصول است و قابل حذف نیست.');
        }

        if ($permanent) {
            return $wpdb->delete($table, array('id' => intval($id)));
        }

        return $wpdb->update($table, array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function restore_category($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_categories', array('deleted_at' => null), array('id' => intval($id)));
    }

    public static function toggle_status($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_categories';
        $current = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$table} WHERE id = %d", intval($id)));
        $new = ($current === 'active') ? 'inactive' : 'active';
        return $wpdb->update($table, array('status' => $new, 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function update_product_count($category_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}b2b_products WHERE category_id = %d AND deleted_at IS NULL AND status != 'draft'", intval($category_id)));
        return $wpdb->update($wpdb->prefix . 'b2b_categories', array('product_count' => intval($count)), array('id' => intval($category_id)));
    }

    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_categories';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) return array('total' => 0, 'active' => 0, 'inactive' => 0, 'deleted' => 0);
        return array(
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL"),
            'active' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active' AND deleted_at IS NULL"),
            'inactive' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'inactive' AND deleted_at IS NULL"),
            'deleted' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NOT NULL"),
        );
    }
}
