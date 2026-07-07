<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Product_Catalog_DB {

    public static function get_products($args = array()) {
        global $wpdb;
        $ptable = $wpdb->prefix . 'b2b_products';
        $ctable = $wpdb->prefix . 'b2b_categories';

        $defaults = array(
            'search' => '',
            'status' => '',
            'category_id' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'p.created_at',
            'order' => 'DESC',
            'include_deleted' => false,
        );
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(p.name_fa LIKE %s OR p.name_en LIKE %s OR p.sku LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s;
            $values[] = $s;
            $values[] = $s;
        }

        if (!empty($args['status'])) {
            $where[] = "p.status = %s";
            $values[] = $args['status'];
        }

        if (!empty($args['category_id'])) {
            $where[] = "p.category_id = %d";
            $values[] = intval($args['category_id']);
        }

        if (!$args['include_deleted']) {
            $where[] = "p.deleted_at IS NULL";
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $join = "LEFT JOIN {$ctable} c ON p.category_id = c.id";

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ptable} p {$join} WHERE {$where_clause}", $values));
            $params = array_merge($values, array($args['per_page'], $offset));
            $items = $wpdb->get_results($wpdb->prepare("SELECT p.*, c.name_fa AS category_name FROM {$ptable} p {$join} WHERE {$where_clause} ORDER BY p.{$args['orderby']} {$args['order']} LIMIT %d OFFSET %d", $params));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$ptable} p {$join} WHERE {$where_clause}");
            $items = $wpdb->get_results($wpdb->prepare("SELECT p.*, c.name_fa AS category_name FROM {$ptable} p {$join} WHERE {$where_clause} ORDER BY p.{$args['orderby']} {$args['order']} LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        return array('items' => $items ?: array(), 'total' => $total, 'pages' => ceil($total / $args['per_page']), 'page' => $args['page'], 'per_page' => $args['per_page']);
    }

    public static function get_product($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT p.*, c.name_fa AS category_name FROM {$wpdb->prefix}b2b_products p LEFT JOIN {$wpdb->prefix}b2b_categories c ON p.category_id = c.id WHERE p.id = %d", intval($id)));
    }

    public static function get_product_by_sku($sku) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_products WHERE sku = %s AND deleted_at IS NULL", sanitize_text_field($sku)));
    }

    public static function create_product($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_products';

        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE sku = %s AND deleted_at IS NOT NULL", sanitize_text_field($data['sku'])));

        $result = $wpdb->insert($table, array(
            'sku' => sanitize_text_field($data['sku']),
            'name_fa' => sanitize_text_field($data['name_fa']),
            'name_en' => sanitize_text_field($data['name_en']),
            'slug' => sanitize_title($data['name_en']),
            'description' => wp_kses_post($data['description'] ?? ''),
            'short_desc' => sanitize_text_field($data['short_desc'] ?? ''),
            'category_id' => intval($data['category_id'] ?: null),
            'base_unit' => sanitize_text_field($data['base_unit'] ?? 'pcs'),
            'weight' => !empty($data['weight']) ? floatval($data['weight']) : null,
            'weight_unit' => sanitize_text_field($data['weight_unit'] ?? 'kg'),
            'min_order_qty' => floatval($data['min_order_qty'] ?? 1),
            'max_order_qty' => !empty($data['max_order_qty']) ? floatval($data['max_order_qty']) : null,
            'lead_time_days' => intval($data['lead_time_days'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'draft'),
            'visibility' => sanitize_text_field($data['visibility'] ?? 'visible'),
            'has_variants' => intval($data['has_variants'] ?? 0),
            'has_attributes' => intval($data['has_attributes'] ?? 0),
            'meta' => !empty($data['meta']) ? wp_json_encode($data['meta']) : null,
            'tags' => !empty($data['tags']) ? wp_json_encode($data['tags']) : null,
            'images' => !empty($data['images']) ? wp_json_encode($data['images']) : null,
            'created_by' => get_current_user_id(),
            'updated_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        if (!is_wp_error($result) && !empty($data['category_id'])) {
            self::update_category_count($data['category_id']);
        }

        return $result === false ? new WP_Error('db_error', $wpdb->last_error) : $wpdb->insert_id;
    }

    public static function update_product($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_products';

        $old = $wpdb->get_var($wpdb->prepare("SELECT category_id FROM {$table} WHERE id = %d", intval($id)));

        $update = array(
            'sku' => sanitize_text_field($data['sku']),
            'name_fa' => sanitize_text_field($data['name_fa']),
            'name_en' => sanitize_text_field($data['name_en']),
            'slug' => sanitize_title($data['name_en']),
            'description' => wp_kses_post($data['description'] ?? ''),
            'short_desc' => sanitize_text_field($data['short_desc'] ?? ''),
            'category_id' => intval($data['category_id'] ?: null),
            'base_unit' => sanitize_text_field($data['base_unit'] ?? 'pcs'),
            'weight' => !empty($data['weight']) ? floatval($data['weight']) : null,
            'weight_unit' => sanitize_text_field($data['weight_unit'] ?? 'kg'),
            'min_order_qty' => floatval($data['min_order_qty'] ?? 1),
            'max_order_qty' => !empty($data['max_order_qty']) ? floatval($data['max_order_qty']) : null,
            'lead_time_days' => intval($data['lead_time_days'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'draft'),
            'visibility' => sanitize_text_field($data['visibility'] ?? 'visible'),
            'has_variants' => intval($data['has_variants'] ?? 0),
            'has_attributes' => intval($data['has_attributes'] ?? 0),
            'meta' => !empty($data['meta']) ? wp_json_encode($data['meta']) : null,
            'tags' => !empty($data['tags']) ? wp_json_encode($data['tags']) : null,
            'images' => !empty($data['images']) ? wp_json_encode($data['images']) : null,
            'updated_by' => get_current_user_id(),
            'updated_at' => current_time('mysql'),
        );

        $result = $wpdb->update($table, $update, array('id' => intval($id)));

        if (!is_wp_error($result)) {
            if (!empty($old)) self::update_category_count($old);
            if (!empty($data['category_id']) && $data['category_id'] != $old) {
                self::update_category_count($data['category_id']);
            }
        }

        return $result;
    }

    public static function delete_product($id, $permanent = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_products';

        $product = $wpdb->get_row($wpdb->prepare("SELECT category_id FROM {$table} WHERE id = %d", intval($id)));

        if ($permanent) {
            $wpdb->delete($wpdb->prefix . 'b2b_attribute_values', array('product_id' => intval($id)));
            $result = $wpdb->delete($table, array('id' => intval($id)));
        } else {
            $result = $wpdb->update($table, array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
        }

        if (!is_wp_error($result) && $product && !empty($product->category_id)) {
            B2B_Procurement_Category_DB::update_product_count($product->category_id);
        }

        return $result;
    }

    public static function restore_product($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_products';
        $result = $wpdb->update($table, array('deleted_at' => null), array('id' => intval($id)));

        if (!is_wp_error($result)) {
            $cat_id = $wpdb->get_var($wpdb->prepare("SELECT category_id FROM {$table} WHERE id = %d", intval($id)));
            if (!empty($cat_id)) {
                self::update_category_count($cat_id);
            }
        }

        return $result;
    }

    public static function toggle_status($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_products';
        $current = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$table} WHERE id = %d", intval($id)));
        $new = ($current === 'active') ? 'inactive' : 'active';
        $result = $wpdb->update($table, array('status' => $new, 'updated_at' => current_time('mysql')), array('id' => intval($id)));

        if (!is_wp_error($result)) {
            $cat_id = $wpdb->get_var($wpdb->prepare("SELECT category_id FROM {$table} WHERE id = %d", intval($id)));
            if (!empty($cat_id)) self::update_category_count($cat_id);
        }

        return $result;
    }

    public static function update_category_count($category_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}b2b_products WHERE category_id = %d AND deleted_at IS NULL AND status != 'draft'", intval($category_id)));
        $wpdb->update($wpdb->prefix . 'b2b_categories', array('product_count' => intval($count)), array('id' => intval($category_id)));
    }

    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_products';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) return array('total' => 0, 'active' => 0, 'inactive' => 0, 'draft' => 0, 'deleted' => 0);
        return array(
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL"),
            'active' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active' AND deleted_at IS NULL"),
            'inactive' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'inactive' AND deleted_at IS NULL"),
            'draft' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'draft' AND deleted_at IS NULL"),
            'deleted' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NOT NULL"),
        );
    }

    public static function bulk_delete($ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_products';
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        return $wpdb->query($wpdb->prepare("UPDATE {$table} SET deleted_at = %s WHERE id IN ({$placeholders})", array_merge(array(current_time('mysql')), $ids)));
    }

    public static function bulk_restore($ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_products';
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        return $wpdb->query($wpdb->prepare("UPDATE {$table} SET deleted_at = NULL WHERE id IN ({$placeholders})", $ids));
    }
}
