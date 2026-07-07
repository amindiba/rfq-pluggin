<?php
defined('ABSPATH') || exit;

class B2B_Rfq_DB {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $rfqs = $wpdb->prefix . 'b2b_rfqs';
        $rfq_products = $wpdb->prefix . 'b2b_rfq_products';
        $rfq_suppliers = $wpdb->prefix . 'b2b_rfq_suppliers';

        $sql_rfqs = "CREATE TABLE IF NOT EXISTS {$rfqs} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            reference VARCHAR(50) NOT NULL,
            description TEXT DEFAULT '',
            deadline DATE DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'draft',
            submitted_at DATETIME DEFAULT NULL,
            closed_at DATETIME DEFAULT NULL,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_reference (reference),
            KEY idx_status (status),
            KEY idx_deleted (deleted_at),
            KEY idx_deadline (deadline)
        ) {$charset};";

        $sql_rfq_products = "CREATE TABLE IF NOT EXISTS {$rfq_products} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            rfq_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            product_name VARCHAR(255) DEFAULT '',
            product_sku VARCHAR(100) DEFAULT '',
            requested_qty DECIMAL(12,3) NOT NULL DEFAULT 1,
            unit VARCHAR(20) DEFAULT 'pcs',
            notes TEXT DEFAULT '',
            PRIMARY KEY (id),
            KEY idx_rfq (rfq_id),
            KEY idx_product (product_id),
            CONSTRAINT fk_rfp_rfq FOREIGN KEY (rfq_id) REFERENCES {$rfqs}(id) ON DELETE CASCADE
        ) {$charset};";

        $sql_rfq_suppliers = "CREATE TABLE IF NOT EXISTS {$rfq_suppliers} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            rfq_id BIGINT UNSIGNED NOT NULL,
            supplier_id BIGINT UNSIGNED NOT NULL,
            supplier_name VARCHAR(200) DEFAULT '',
            status VARCHAR(20) DEFAULT 'invited',
            PRIMARY KEY (id),
            KEY idx_rfq (rfq_id),
            KEY idx_supplier (supplier_id),
            CONSTRAINT fk_rfs_rfq FOREIGN KEY (rfq_id) REFERENCES {$rfqs}(id) ON DELETE CASCADE
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_rfqs);
        dbDelta($sql_rfq_products);
        dbDelta($sql_rfq_suppliers);

        update_option('b2b_rfq_db_version', '1.0.0');
    }

    // ==================== RFQ CRUD ====================

    public static function get_rfqs($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_rfqs';

        $defaults = array('search' => '', 'status' => '', 'per_page' => 20, 'page' => 1, 'orderby' => 'created_at', 'order' => 'DESC', 'include_deleted' => false);
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(title LIKE %s OR reference LIKE %s OR description LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s; $values[] = $s; $values[] = $s;
        }
        if (!empty($args['status'])) { $where[] = "status = %s"; $values[] = $args['status']; }
        if (!$args['include_deleted']) { $where[] = "deleted_at IS NULL"; }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];
        $allowed = array('id', 'title', 'reference', 'deadline', 'status', 'created_at');
        $orderby = in_array($args['orderby'], $allowed) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", $values));
            $params = array_merge($values, array($args['per_page'], $offset));
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $params));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}");
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        return array('items' => $items ? $items : array(), 'total' => $total, 'pages' => ceil($total / $args['per_page']), 'page' => $args['page'], 'per_page' => $args['per_page']);
    }

    public static function get_rfq($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_rfqs WHERE id = %d", intval($id)));
    }

    public static function create_rfq($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_rfqs';

        // Generate reference
        $ref = 'RFQ-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $result = $wpdb->insert($table, array(
            'title' => sanitize_text_field($data['title']),
            'reference' => $ref,
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'deadline' => sanitize_text_field($data['deadline']),
            'status' => 'draft',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        return $result === false ? new WP_Error('db_error', $wpdb->last_error) : array('id' => $wpdb->insert_id, 'reference' => $ref);
    }

    public static function update_rfq($id, $data) {
        global $wpdb;
        $update = array('updated_at' => current_time('mysql'));
        if (isset($data['title'])) $update['title'] = sanitize_text_field($data['title']);
        if (isset($data['description'])) $update['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['deadline'])) $update['deadline'] = sanitize_text_field($data['deadline']);
        return $wpdb->update($wpdb->prefix . 'b2b_rfqs', $update, array('id' => intval($id)));
    }

    public static function submit_rfq($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_rfqs', array('status' => 'submitted', 'submitted_at' => current_time('mysql'), 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function close_rfq($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_rfqs', array('status' => 'closed', 'closed_at' => current_time('mysql'), 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function delete_rfq($id, $permanent = false) {
        global $wpdb;
        if ($permanent) return $wpdb->delete($wpdb->prefix . 'b2b_rfqs', array('id' => intval($id)));
        return $wpdb->update($wpdb->prefix . 'b2b_rfqs', array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    // ==================== RFQ PRODUCTS ====================

    public static function get_rfq_products($rfq_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_rfq_products WHERE rfq_id = %d ORDER BY id ASC", intval($rfq_id)));
    }

    public static function save_rfq_products($rfq_id, $products) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_rfq_products';
        $wpdb->delete($table, array('rfq_id' => intval($rfq_id)));

        foreach ($products as $p) {
            $wpdb->insert($table, array(
                'rfq_id' => intval($rfq_id),
                'product_id' => intval($p['product_id']),
                'product_name' => sanitize_text_field($p['product_name'] ?? ''),
                'product_sku' => sanitize_text_field($p['product_sku'] ?? ''),
                'requested_qty' => floatval($p['requested_qty']),
                'unit' => sanitize_text_field($p['unit'] ?? 'pcs'),
                'notes' => sanitize_textarea_field($p['notes'] ?? ''),
            ));
        }
    }

    // ==================== RFQ SUPPLIERS ====================

    public static function get_rfq_suppliers($rfq_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_rfq_suppliers WHERE rfq_id = %d", intval($rfq_id)));
    }

    public static function save_rfq_suppliers($rfq_id, $suppliers) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_rfq_suppliers';
        $wpdb->delete($table, array('rfq_id' => intval($rfq_id)));

        foreach ($suppliers as $s) {
            $wpdb->insert($table, array(
                'rfq_id' => intval($rfq_id),
                'supplier_id' => intval($s['supplier_id']),
                'supplier_name' => sanitize_text_field($s['supplier_name'] ?? ''),
                'status' => 'invited',
            ));
        }
    }

    public static function get_stats() {
        global $wpdb;
        $t = $wpdb->prefix . 'b2b_rfqs';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t));
        if ($exists !== $t) return array('total' => 0, 'draft' => 0, 'submitted' => 0, 'closed' => 0);
        return array(
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE deleted_at IS NULL"),
            'draft' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'draft' AND deleted_at IS NULL"),
            'submitted' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'submitted' AND deleted_at IS NULL"),
            'closed' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'closed' AND deleted_at IS NULL"),
        );
    }
}
