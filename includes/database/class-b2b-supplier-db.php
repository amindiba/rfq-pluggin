<?php
defined('ABSPATH') || exit;

class B2B_Supplier_DB {

    public static function create_tables() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_suppliers';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(200) NOT NULL,
            name_en VARCHAR(200) DEFAULT '',
            company_name VARCHAR(200) DEFAULT '',
            contact_person VARCHAR(150) DEFAULT '',
            email VARCHAR(100) DEFAULT '',
            phone VARCHAR(30) DEFAULT '',
            mobile VARCHAR(30) DEFAULT '',
            address TEXT DEFAULT '',
            city VARCHAR(100) DEFAULT '',
            province VARCHAR(100) DEFAULT '',
            postal_code VARCHAR(20) DEFAULT '',
            tax_id VARCHAR(30) DEFAULT '',
            national_id VARCHAR(20) DEFAULT '',
            status TINYINT(1) DEFAULT 1,
            description TEXT DEFAULT '',
            meta LONGTEXT DEFAULT NULL,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_code (code),
            KEY idx_status (status),
            KEY idx_deleted (deleted_at),
            KEY idx_name (name)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('b2b_supplier_db_version', '1.0.0');
    }

    public static function get_suppliers($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_suppliers';

        $defaults = array(
            'search' => '',
            'status' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'name',
            'order' => 'ASC',
            'include_deleted' => false,
        );
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(name LIKE %s OR name_en LIKE %s OR code LIKE %s OR company_name LIKE %s OR contact_person LIKE %s OR phone LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s; $values[] = $s; $values[] = $s; $values[] = $s; $values[] = $s; $values[] = $s;
        }

        if ($args['status'] !== '') {
            $where[] = "status = %d";
            $values[] = intval($args['status']);
        }

        if (!$args['include_deleted']) {
            $where[] = "deleted_at IS NULL";
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];
        $allowed = array('id', 'code', 'name', 'name_en', 'company_name', 'sort_order', 'created_at');
        $orderby = in_array($args['orderby'], $allowed) ? $args['orderby'] : 'name';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", $values));
            $values[] = $args['per_page'];
            $values[] = $offset;
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $values));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}");
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        return array(
            'items' => $items ? $items : array(),
            'total' => $total,
            'pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
        );
    }

    public static function get_supplier($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_suppliers WHERE id = %d", intval($id)));
    }

    public static function get_supplier_by_code($code) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_suppliers WHERE code = %s AND deleted_at IS NULL", sanitize_text_field($code)));
    }

    public static function create_supplier($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_suppliers';

        $result = $wpdb->insert($table, array(
            'code' => sanitize_text_field($data['code']),
            'name' => sanitize_text_field($data['name']),
            'name_en' => sanitize_text_field($data['name_en'] ?? ''),
            'company_name' => sanitize_text_field($data['company_name'] ?? ''),
            'contact_person' => sanitize_text_field($data['contact_person'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'mobile' => sanitize_text_field($data['mobile'] ?? ''),
            'address' => sanitize_textarea_field($data['address'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'province' => sanitize_text_field($data['province'] ?? ''),
            'postal_code' => sanitize_text_field($data['postal_code'] ?? ''),
            'tax_id' => sanitize_text_field($data['tax_id'] ?? ''),
            'national_id' => sanitize_text_field($data['national_id'] ?? ''),
            'status' => intval($data['status'] ?? 1),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        return $result === false ? new WP_Error('db_error', $wpdb->last_error) : $wpdb->insert_id;
    }

    public static function update_supplier($id, $data) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_suppliers', array(
            'code' => sanitize_text_field($data['code']),
            'name' => sanitize_text_field($data['name']),
            'name_en' => sanitize_text_field($data['name_en'] ?? ''),
            'company_name' => sanitize_text_field($data['company_name'] ?? ''),
            'contact_person' => sanitize_text_field($data['contact_person'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'mobile' => sanitize_text_field($data['mobile'] ?? ''),
            'address' => sanitize_textarea_field($data['address'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'province' => sanitize_text_field($data['province'] ?? ''),
            'postal_code' => sanitize_text_field($data['postal_code'] ?? ''),
            'tax_id' => sanitize_text_field($data['tax_id'] ?? ''),
            'national_id' => sanitize_text_field($data['national_id'] ?? ''),
            'status' => intval($data['status'] ?? 1),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'updated_at' => current_time('mysql'),
        ), array('id' => intval($id)));
    }

    public static function delete_supplier($id, $permanent = false) {
        global $wpdb;
        if ($permanent) {
            return $wpdb->delete($wpdb->prefix . 'b2b_suppliers', array('id' => intval($id)));
        }
        return $wpdb->update($wpdb->prefix . 'b2b_suppliers', array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function restore_supplier($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_suppliers', array('deleted_at' => null), array('id' => intval($id)));
    }

    public static function toggle_status($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_suppliers';
        $current = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$table} WHERE id = %d", intval($id)));
        $new = ($current == 1) ? 0 : 1;
        return $wpdb->update($table, array('status' => $new, 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_suppliers';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) return array('total' => 0, 'active' => 0, 'inactive' => 0, 'deleted' => 0);
        return array(
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL"),
            'active' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 1 AND deleted_at IS NULL"),
            'inactive' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 0 AND deleted_at IS NULL"),
            'deleted' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NOT NULL"),
        );
    }

    public static function bulk_delete($ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_suppliers';
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        return $wpdb->query($wpdb->prepare("UPDATE {$table} SET deleted_at = %s WHERE id IN ({$placeholders})", array_merge(array(current_time('mysql')), $ids)));
    }

    public static function bulk_restore($ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_suppliers';
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        return $wpdb->query($wpdb->prepare("UPDATE {$table} SET deleted_at = NULL WHERE id IN ({$placeholders})", $ids));
    }
}
