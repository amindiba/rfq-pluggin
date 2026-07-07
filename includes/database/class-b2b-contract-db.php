<?php
defined('ABSPATH') || exit;

class B2B_Contract_DB {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . 'b2b_contracts';

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            contract_number VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            po_id BIGINT UNSIGNED DEFAULT NULL,
            rfq_id BIGINT UNSIGNED DEFAULT NULL,
            quotation_id BIGINT UNSIGNED DEFAULT NULL,
            supplier_id BIGINT UNSIGNED DEFAULT NULL,
            supplier_name VARCHAR(200) DEFAULT '',
            po_number VARCHAR(50) DEFAULT '',
            rfq_reference VARCHAR(50) DEFAULT '',
            quotation_reference VARCHAR(50) DEFAULT '',
            status VARCHAR(20) DEFAULT 'draft',
            start_date DATE DEFAULT NULL,
            end_date DATE DEFAULT NULL,
            contract_value DECIMAL(15,3) DEFAULT 0,
            notes TEXT DEFAULT '',
            activated_at DATETIME DEFAULT NULL,
            closed_at DATETIME DEFAULT NULL,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_contract_number (contract_number),
            KEY idx_po (po_id),
            KEY idx_supplier (supplier_id),
            KEY idx_status (status)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('b2b_contract_db_version', '1.0.0');
    }

    public static function get_contracts($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_contracts';

        $defaults = array('search' => '', 'status' => '', 'per_page' => 20, 'page' => 1, 'orderby' => 'created_at', 'order' => 'DESC');
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(c.contract_number LIKE %s OR c.title LIKE %s OR c.supplier_name LIKE %s OR c.po_number LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s; $values[] = $s; $values[] = $s; $values[] = $s;
        }
        if (!empty($args['status'])) { $where[] = "c.status = %s"; $values[] = $args['status']; }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];
        $allowed = array('c.id', 'c.contract_number', 'c.title', 'c.created_at', 'c.contract_value', 'c.status');
        $orderby = in_array($args['orderby'], $allowed) ? $args['orderby'] : 'c.created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} c WHERE {$where_clause}", $values));
            $params = array_merge($values, array($args['per_page'], $offset));
            $items = $wpdb->get_results($wpdb->prepare("SELECT c.* FROM {$table} c WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $params));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} c WHERE {$where_clause}");
            $items = $wpdb->get_results($wpdb->prepare("SELECT c.* FROM {$table} c WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        return array('items' => $items ? $items : array(), 'total' => $total, 'pages' => ceil($total / $args['per_page']), 'page' => $args['page'], 'per_page' => $args['per_page']);
    }

    public static function get_contract($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_contracts WHERE id = %d", intval($id)));
    }

    public static function get_contract_by_po($po_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_contracts WHERE po_id = %d AND deleted_at IS NULL", intval($po_id)));
    }

    public static function create_contract($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_contracts';

        $contract_num = 'CTR-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $result = $wpdb->insert($table, array(
            'contract_number' => $contract_num,
            'title' => sanitize_text_field($data['title']),
            'po_id' => intval($data['po_id']),
            'rfq_id' => intval($data['rfq_id'] ?? 0),
            'quotation_id' => intval($data['quotation_id'] ?? 0),
            'supplier_id' => intval($data['supplier_id']),
            'supplier_name' => sanitize_text_field($data['supplier_name'] ?? ''),
            'po_number' => sanitize_text_field($data['po_number'] ?? ''),
            'rfq_reference' => sanitize_text_field($data['rfq_reference'] ?? ''),
            'quotation_reference' => sanitize_text_field($data['quotation_reference'] ?? ''),
            'status' => 'draft',
            'start_date' => sanitize_text_field($data['start_date']),
            'end_date' => sanitize_text_field($data['end_date']),
            'contract_value' => floatval($data['contract_value'] ?? 0),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        return $result === false ? new WP_Error('db_error', $wpdb->last_error) : array('id' => $wpdb->insert_id, 'contract_number' => $contract_num);
    }

    public static function update_contract($id, $data) {
        global $wpdb;
        $update = array('updated_at' => current_time('mysql'));
        if (isset($data['title'])) $update['title'] = sanitize_text_field($data['title']);
        if (isset($data['start_date'])) $update['start_date'] = sanitize_text_field($data['start_date']);
        if (isset($data['end_date'])) $update['end_date'] = sanitize_text_field($data['end_date']);
        if (isset($data['notes'])) $update['notes'] = sanitize_textarea_field($data['notes']);
        return $wpdb->update($wpdb->prefix . 'b2b_contracts', $update, array('id' => intval($id)));
    }

    public static function activate_contract($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_contracts', array('status' => 'active', 'activated_at' => current_time('mysql'), 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function close_contract($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_contracts', array('status' => 'closed', 'closed_at' => current_time('mysql'), 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function delete_contract($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_contracts', array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function get_stats() {
        global $wpdb;
        $t = $wpdb->prefix . 'b2b_contracts';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t));
        if ($exists !== $t) return array('total' => 0, 'draft' => 0, 'active' => 0, 'closed' => 0);
        return array(
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE deleted_at IS NULL"),
            'draft' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'draft' AND deleted_at IS NULL"),
            'active' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'active' AND deleted_at IS NULL"),
            'closed' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'closed' AND deleted_at IS NULL"),
        );
    }
}
