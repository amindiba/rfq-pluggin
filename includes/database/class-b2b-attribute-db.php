<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Attribute_DB {

    public static function get_attributes($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_product_attributes';

        $defaults = array(
            'search' => '',
            'type' => '',
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
            $where[] = "(name_fa LIKE %s OR name_en LIKE %s OR code LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s;
            $values[] = $s;
            $values[] = $s;
        }

        if (!empty($args['type'])) {
            $where[] = "type = %s";
            $values[] = $args['type'];
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

    public static function get_attribute($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_product_attributes WHERE id = %d", intval($id)));
    }

    public static function create_attribute($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_product_attributes';

        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE code = %s AND deleted_at IS NOT NULL", sanitize_text_field($data['code'])));

        $result = $wpdb->insert($table, array(
            'name_fa' => sanitize_text_field($data['name_fa']),
            'name_en' => sanitize_text_field($data['name_en']),
            'code' => sanitize_text_field($data['code']),
            'type' => sanitize_text_field($data['type'] ?? 'text'),
            'options' => !empty($data['options']) ? wp_json_encode($data['options']) : null,
            'is_required' => intval($data['is_required'] ?? 0),
            'is_filterable' => intval($data['is_filterable'] ?? 0),
            'is_searchable' => intval($data['is_searchable'] ?? 0),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        return $result === false ? new WP_Error('db_error', $wpdb->last_error) : $wpdb->insert_id;
    }

    public static function update_attribute($id, $data) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_product_attributes', array(
            'name_fa' => sanitize_text_field($data['name_fa']),
            'name_en' => sanitize_text_field($data['name_en']),
            'code' => sanitize_text_field($data['code']),
            'type' => sanitize_text_field($data['type'] ?? 'text'),
            'options' => !empty($data['options']) ? wp_json_encode($data['options']) : null,
            'is_required' => intval($data['is_required'] ?? 0),
            'is_filterable' => intval($data['is_filterable'] ?? 0),
            'is_searchable' => intval($data['is_searchable'] ?? 0),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'updated_at' => current_time('mysql'),
        ), array('id' => intval($id)));
    }

    public static function delete_attribute($id, $permanent = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_product_attributes';

        if ($permanent) {
            $wpdb->delete($wpdb->prefix . 'b2b_attribute_values', array('attribute_id' => intval($id)));
            return $wpdb->delete($table, array('id' => intval($id)));
        }

        return $wpdb->update($table, array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function restore_attribute($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_product_attributes', array('deleted_at' => null), array('id' => intval($id)));
    }

    // Product Attribute Values (EAV)
    public static function get_product_attributes($product_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT av.*, a.name_fa, a.name_en, a.code, a.type, a.options FROM {$wpdb->prefix}b2b_attribute_values av INNER JOIN {$wpdb->prefix}b2b_product_attributes a ON av.attribute_id = a.id WHERE av.product_id = %d ORDER BY av.sort_order ASC",
            intval($product_id)
        ));
    }

    public static function set_product_attribute($product_id, $attribute_id, $value) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_attribute_values';
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE product_id = %d AND attribute_id = %d", intval($product_id), intval($attribute_id)));

        $data = array(
            'product_id' => intval($product_id),
            'attribute_id' => intval($attribute_id),
            'value_text' => is_string($value) ? sanitize_text_field($value) : '',
            'value_number' => is_numeric($value) ? floatval($value) : null,
        );

        if ($existing) {
            return $wpdb->update($table, $data, array('id' => intval($existing)));
        }

        return $wpdb->insert($table, $data);
    }

    public static function delete_product_attribute($product_id, $attribute_id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'b2b_attribute_values', array(
            'product_id' => intval($product_id),
            'attribute_id' => intval($attribute_id),
        ));
    }

    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_product_attributes';
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
