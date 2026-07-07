<?php
defined('ABSPATH') || exit;

class B2B_PO_DB {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $pos = $wpdb->prefix . 'b2b_purchase_orders';
        $items = $wpdb->prefix . 'b2b_po_items';

        $sql_pos = "CREATE TABLE IF NOT EXISTS {$pos} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            po_number VARCHAR(50) NOT NULL,
            rfq_id BIGINT UNSIGNED DEFAULT NULL,
            quotation_id BIGINT UNSIGNED DEFAULT NULL,
            supplier_id BIGINT UNSIGNED DEFAULT NULL,
            supplier_name VARCHAR(200) DEFAULT '',
            rfq_reference VARCHAR(50) DEFAULT '',
            quotation_reference VARCHAR(50) DEFAULT '',
            status VARCHAR(20) DEFAULT 'draft',
            grand_total DECIMAL(15,3) DEFAULT 0,
            notes TEXT DEFAULT '',
            confirmed_at DATETIME DEFAULT NULL,
            cancelled_at DATETIME DEFAULT NULL,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_po_number (po_number),
            KEY idx_rfq (rfq_id),
            KEY idx_quotation (quotation_id),
            KEY idx_supplier (supplier_id),
            KEY idx_status (status)
        ) {$charset};";

        $sql_items = "CREATE TABLE IF NOT EXISTS {$items} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            po_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            product_name VARCHAR(255) DEFAULT '',
            product_sku VARCHAR(100) DEFAULT '',
            unit_price DECIMAL(15,3) NOT NULL DEFAULT 0,
            quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
            line_total DECIMAL(15,3) DEFAULT 0,
            delivery_days SMALLINT UNSIGNED DEFAULT 0,
            supplier_note TEXT DEFAULT '',
            PRIMARY KEY (id),
            KEY idx_po (po_id),
            KEY idx_product (product_id),
            CONSTRAINT fk_poi_po FOREIGN KEY (po_id) REFERENCES {$pos}(id) ON DELETE CASCADE
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_pos);
        dbDelta($sql_items);

        update_option('b2b_po_db_version', '1.0.0');
    }

    // ==================== PO CRUD ====================

    public static function get_pos($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_purchase_orders';

        $defaults = array('search' => '', 'status' => '', 'per_page' => 20, 'page' => 1, 'orderby' => 'created_at', 'order' => 'DESC');
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(po.po_number LIKE %s OR po.supplier_name LIKE %s OR po.rfq_reference LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s; $values[] = $s; $values[] = $s;
        }
        if (!empty($args['status'])) { $where[] = "po.status = %s"; $values[] = $args['status']; }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];
        $allowed = array('po.id', 'po.po_number', 'po.created_at', 'po.grand_total', 'po.status');
        $orderby = in_array($args['orderby'], $allowed) ? $args['orderby'] : 'po.created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} po WHERE {$where_clause}", $values));
            $params = array_merge($values, array($args['per_page'], $offset));
            $items = $wpdb->get_results($wpdb->prepare("SELECT po.* FROM {$table} po WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $params));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} po WHERE {$where_clause}");
            $items = $wpdb->get_results($wpdb->prepare("SELECT po.* FROM {$table} po WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        return array('items' => $items ? $items : array(), 'total' => $total, 'pages' => ceil($total / $args['per_page']), 'page' => $args['page'], 'per_page' => $args['per_page']);
    }

    public static function get_po($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_purchase_orders WHERE id = %d", intval($id)));
    }

    public static function get_po_by_quotation($quotation_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_purchase_orders WHERE quotation_id = %d AND deleted_at IS NULL", intval($quotation_id)));
    }

    public static function create_po($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_purchase_orders';

        $po_num = 'PO-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $result = $wpdb->insert($table, array(
            'po_number' => $po_num,
            'rfq_id' => intval($data['rfq_id']),
            'quotation_id' => intval($data['quotation_id']),
            'supplier_id' => intval($data['supplier_id']),
            'supplier_name' => sanitize_text_field($data['supplier_name'] ?? ''),
            'rfq_reference' => sanitize_text_field($data['rfq_reference'] ?? ''),
            'quotation_reference' => sanitize_text_field($data['quotation_reference'] ?? ''),
            'status' => 'draft',
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        return $result === false ? new WP_Error('db_error', $wpdb->last_error) : array('id' => $wpdb->insert_id, 'po_number' => $po_num);
    }

    public static function update_po($id, $data) {
        global $wpdb;
        $update = array('updated_at' => current_time('mysql'));
        if (isset($data['notes'])) $update['notes'] = sanitize_textarea_field($data['notes']);
        return $wpdb->update($wpdb->prefix . 'b2b_purchase_orders', $update, array('id' => intval($id)));
    }

    public static function confirm_po($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_purchase_orders', array('status' => 'confirmed', 'confirmed_at' => current_time('mysql'), 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function cancel_po($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_purchase_orders', array('status' => 'cancelled', 'cancelled_at' => current_time('mysql'), 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function delete_po($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_purchase_orders', array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    // ==================== PO ITEMS ====================

    public static function get_items($po_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_po_items WHERE po_id = %d ORDER BY id ASC", intval($po_id)));
    }

    public static function save_items($po_id, $items, $grand_total) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_po_items';
        $wpdb->delete($table, array('po_id' => intval($po_id)));

        foreach ($items as $item) {
            $wpdb->insert($table, array(
                'po_id' => intval($po_id),
                'product_id' => intval($item['product_id']),
                'product_name' => sanitize_text_field($item['product_name'] ?? ''),
                'product_sku' => sanitize_text_field($item['product_sku'] ?? ''),
                'unit_price' => floatval($item['unit_price']),
                'quantity' => floatval($item['quantity']),
                'line_total' => floatval($item['line_total'] ?? 0),
                'delivery_days' => intval($item['delivery_days'] ?? 0),
                'supplier_note' => sanitize_textarea_field($item['supplier_note'] ?? ''),
            ));
        }

        global $wpdb;
        $wpdb->update($wpdb->prefix . 'b2b_purchase_orders', array('grand_total' => floatval($grand_total), 'updated_at' => current_time('mysql')), array('id' => intval($po_id)));
    }

    public static function get_stats() {
        global $wpdb;
        $t = $wpdb->prefix . 'b2b_purchase_orders';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t));
        if ($exists !== $t) return array('total' => 0, 'draft' => 0, 'confirmed' => 0, 'cancelled' => 0);
        return array(
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE deleted_at IS NULL"),
            'draft' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'draft' AND deleted_at IS NULL"),
            'confirmed' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'confirmed' AND deleted_at IS NULL"),
            'cancelled' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'cancelled' AND deleted_at IS NULL"),
        );
    }
}
