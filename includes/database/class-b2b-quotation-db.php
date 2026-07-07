<?php
defined('ABSPATH') || exit;

class B2B_Quotation_DB {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $quotations = $wpdb->prefix . 'b2b_quotations';
        $items = $wpdb->prefix . 'b2b_quotation_items';

        $sql_quotations = "CREATE TABLE IF NOT EXISTS {$quotations} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            rfq_id BIGINT UNSIGNED NOT NULL,
            supplier_id BIGINT UNSIGNED NOT NULL,
            supplier_name VARCHAR(200) DEFAULT '',
            status VARCHAR(20) DEFAULT 'draft',
            grand_total DECIMAL(15,3) DEFAULT 0,
            submitted_at DATETIME DEFAULT NULL,
            notes TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_rfq (rfq_id),
            KEY idx_supplier (supplier_id),
            KEY idx_status (status),
            UNIQUE KEY uk_rfq_supplier (rfq_id, supplier_id)
        ) {$charset};";

        $sql_items = "CREATE TABLE IF NOT EXISTS {$items} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            quotation_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            product_name VARCHAR(255) DEFAULT '',
            product_sku VARCHAR(100) DEFAULT '',
            unit_price DECIMAL(15,3) NOT NULL DEFAULT 0,
            quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
            line_total DECIMAL(15,3) DEFAULT 0,
            delivery_days SMALLINT UNSIGNED DEFAULT 0,
            supplier_note TEXT DEFAULT '',
            PRIMARY KEY (id),
            KEY idx_quotation (quotation_id),
            KEY idx_product (product_id),
            CONSTRAINT fk_qi_quotation FOREIGN KEY (quotation_id) REFERENCES {$quotations}(id) ON DELETE CASCADE
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_quotations);
        dbDelta($sql_items);

        update_option('b2b_quotation_db_version', '1.0.0');
    }

    // ==================== QUOTATIONS ====================

    public static function get_quotations($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_quotations';

        $defaults = array('search' => '', 'status' => '', 'rfq_id' => 0, 'per_page' => 20, 'page' => 1, 'orderby' => 'created_at', 'order' => 'DESC');
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(q.supplier_name LIKE %s OR r.reference LIKE %s OR r.title LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s; $values[] = $s; $values[] = $s;
        }
        if (!empty($args['status'])) { $where[] = "q.status = %s"; $values[] = $args['status']; }
        if (!empty($args['rfq_id'])) { $where[] = "q.rfq_id = %d"; $values[] = intval($args['rfq_id']); }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];
        $allowed = array('q.id', 'q.created_at', 'q.grand_total', 'q.status');
        $orderby = in_array($args['orderby'], $allowed) ? $args['orderby'] : 'q.created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $rfq_join = "LEFT JOIN {$wpdb->prefix}b2b_rfqs r ON q.rfq_id = r.id";

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} q {$rfq_join} WHERE {$where_clause}", $values));
            $params = array_merge($values, array($args['per_page'], $offset));
            $items = $wpdb->get_results($wpdb->prepare("SELECT q.*, r.reference AS rfq_reference, r.title AS rfq_title FROM {$table} q {$rfq_join} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $params));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} q {$rfq_join} WHERE {$where_clause}");
            $items = $wpdb->get_results($wpdb->prepare("SELECT q.*, r.reference AS rfq_reference, r.title AS rfq_title FROM {$table} q {$rfq_join} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        return array('items' => $items ? $items : array(), 'total' => $total, 'pages' => ceil($total / $args['per_page']), 'page' => $args['page'], 'per_page' => $args['per_page']);
    }

    public static function get_quotation($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT q.*, r.reference AS rfq_reference, r.title AS rfq_title, r.deadline AS rfq_deadline FROM {$wpdb->prefix}b2b_quotations q LEFT JOIN {$wpdb->prefix}b2b_rfqs r ON q.rfq_id = r.id WHERE q.id = %d", intval($id)));
    }

    public static function get_quotation_by_rfq_supplier($rfq_id, $supplier_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_quotations WHERE rfq_id = %d AND supplier_id = %d", intval($rfq_id), intval($supplier_id)));
    }

    public static function create_quotation($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_quotations';

        $result = $wpdb->insert($table, array(
            'rfq_id' => intval($data['rfq_id']),
            'supplier_id' => intval($data['supplier_id']),
            'supplier_name' => sanitize_text_field($data['supplier_name'] ?? ''),
            'status' => 'draft',
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        return $result === false ? new WP_Error('db_error', $wpdb->last_error) : $wpdb->insert_id;
    }

    public static function update_quotation($id, $data) {
        global $wpdb;
        $update = array('updated_at' => current_time('mysql'));
        if (isset($data['notes'])) $update['notes'] = sanitize_textarea_field($data['notes']);
        return $wpdb->update($wpdb->prefix . 'b2b_quotations', $update, array('id' => intval($id)));
    }

    public static function submit_quotation($id, $grand_total) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_quotations', array('status' => 'submitted', 'grand_total' => floatval($grand_total), 'submitted_at' => current_time('mysql'), 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function select_winner($id) {
        global $wpdb;
        $quotation = $wpdb->get_row($wpdb->prepare("SELECT rfq_id FROM {$wpdb->prefix}b2b_quotations WHERE id = %d", intval($id)));
        if (!$quotation) return false;

        $wpdb->update($wpdb->prefix . 'b2b_quotations', array('status' => 'selected', 'updated_at' => current_time('mysql')), array('id' => intval($id)));
        $wpdb->update($wpdb->prefix . 'b2b_quotations', array('status' => 'rejected', 'updated_at' => current_time('mysql')), array('rfq_id' => $quotation->rfq_id, 'id != %d', intval($id)));
        $wpdb->update($wpdb->prefix . 'b2b_rfqs', array('status' => 'quotation_completed', 'updated_at' => current_time('mysql')), array('id' => $quotation->rfq_id));

        return true;
    }

    public static function delete_quotation($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'b2b_quotations', array('id' => intval($id)));
    }

    // ==================== QUOTATION ITEMS ====================

    public static function get_items($quotation_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_quotation_items WHERE quotation_id = %d ORDER BY id ASC", intval($quotation_id)));
    }

    public static function save_items($quotation_id, $items) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_quotation_items';
        $wpdb->delete($table, array('quotation_id' => intval($quotation_id)));

        $grand_total = 0;
        foreach ($items as $item) {
            $unit_price = floatval($item['unit_price']);
            $quantity = floatval($item['quantity']);
            $line_total = $unit_price * $quantity;

            $wpdb->insert($table, array(
                'quotation_id' => intval($quotation_id),
                'product_id' => intval($item['product_id']),
                'product_name' => sanitize_text_field($item['product_name'] ?? ''),
                'product_sku' => sanitize_text_field($item['product_sku'] ?? ''),
                'unit_price' => $unit_price,
                'quantity' => $quantity,
                'line_total' => $line_total,
                'delivery_days' => intval($item['delivery_days'] ?? 0),
                'supplier_note' => sanitize_textarea_field($item['supplier_note'] ?? ''),
            ));

            $grand_total += $line_total;
        }

        return $grand_total;
    }

    public static function get_items_for_comparison($rfq_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT qi.*, q.supplier_name, q.supplier_id FROM {$wpdb->prefix}b2b_quotation_items qi INNER JOIN {$wpdb->prefix}b2b_quotations q ON qi.quotation_id = q.id WHERE q.rfq_id = %d AND q.status IN ('submitted', 'selected') ORDER BY qi.product_id, q.supplier_id",
            intval($rfq_id)
        ));
    }

    public static function get_quotations_for_rfq($rfq_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_quotations WHERE rfq_id = %d ORDER BY created_at ASC", intval($rfq_id)));
    }

    public static function get_stats() {
        global $wpdb;
        $t = $wpdb->prefix . 'b2b_quotations';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t));
        if ($exists !== $t) return array('total' => 0, 'draft' => 0, 'submitted' => 0, 'selected' => 0, 'rejected' => 0);
        return array(
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t}"),
            'draft' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'draft'"),
            'submitted' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'submitted'"),
            'selected' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'selected'"),
            'rejected' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'rejected'"),
        );
    }
}
