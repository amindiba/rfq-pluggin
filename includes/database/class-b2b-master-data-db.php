<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Master_Data_DB {

    public static function create_tables() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';
        $charset = $wpdb->get_charset_collate();

        // Drop old indexes first
        $wpdb->query("ALTER TABLE {$table} DROP INDEX IF EXISTS idx_title");
        $wpdb->query("ALTER TABLE {$table} DROP INDEX IF EXISTS idx_short_name");

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(100) NOT NULL,
            short_name VARCHAR(20) NOT NULL,
            description TEXT DEFAULT '',
            sort_order INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_title (title),
            UNIQUE KEY uk_short_name (short_name)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Ensure indexes exist on existing tables
        $wpdb->query("ALTER TABLE {$table} ADD UNIQUE KEY IF NOT EXISTS uk_title (title)");
        $wpdb->query("ALTER TABLE {$table} ADD UNIQUE KEY IF NOT EXISTS uk_short_name (short_name)");

        update_option('b2b_md_db_version', '1.0.2');
    }

    public static function create_unit($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';

        $title = sanitize_text_field($data['title']);
        $short_name = sanitize_text_field($data['short_name']);

        // Remove any soft-deleted records with same title or short_name
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE (title = %s OR short_name = %s) AND deleted_at IS NOT NULL", $title, $short_name));

        $result = $wpdb->insert($table, array(
            'title' => $title,
            'short_name' => $short_name,
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    public static function update_unit($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';

        return $wpdb->update($table, array(
            'title' => sanitize_text_field($data['title']),
            'short_name' => sanitize_text_field($data['short_name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'updated_at' => current_time('mysql'),
        ), array('id' => intval($id)));
    }

    public static function delete_unit($id, $permanent = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';

        if ($permanent) {
            return $wpdb->delete($table, array('id' => intval($id)));
        }

        return $wpdb->update($table, array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function get_units($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';

        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'status' => '',
            'search' => '',
            'include_deleted' => false,
        );
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $values[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $where[] = "(title LIKE %s OR short_name LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s;
            $values[] = $s;
        }

        if (!$args['include_deleted']) {
            $where[] = "deleted_at IS NULL";
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", $values));
            $values[] = $args['per_page'];
            $values[] = $offset;
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY sort_order ASC LIMIT %d OFFSET %d", $values));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}");
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY sort_order ASC LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        return array(
            'items' => $items ? $items : array(),
            'total' => $total,
            'pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
        );
    }

    public static function restore_unit($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';
        return $wpdb->update($table, array('deleted_at' => null), array('id' => intval($id)));
    }

    public static function toggle_unit_status($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';

        $current = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$table} WHERE id = %d", intval($id)));
        $new = ($current === 'active') ? 'inactive' : 'active';

        return $wpdb->update($table, array('status' => $new, 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function seed_defaults() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';

        $units = array(
            array('کیلوگرم', 'kg', 'واحد وزن', 1),
            array('گرم', 'g', 'واحد وزن', 2),
            array('تن', 'ton', 'واحد وزن', 3),
            array('لیتر', 'L', 'واحد حجم', 4),
            array('متر', 'm', 'واحد طول', 5),
            array('عدد', 'pcs', 'شمارشی', 6),
            array('جعبه', 'box', 'بسته‌بندی', 7),
            array('بسته', 'pack', 'بسته‌بندی', 8),
            array('رول', 'roll', 'بسته‌بندی', 9),
        );

        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) {
            return;
        }

        foreach ($units as $u) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE title = %s", $u[0]));
            if ($count == 0) {
                $wpdb->insert($table, array(
                    'title' => $u[0],
                    'short_name' => $u[1],
                    'description' => $u[2],
                    'sort_order' => $u[3],
                    'status' => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ));
            }
        }
    }

    public static function get_unit_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_md_units';

        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) {
            return array('total' => 0, 'active' => 0, 'inactive' => 0, 'deleted' => 0);
        }

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL");
        $active = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active' AND deleted_at IS NULL");
        $inactive = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'inactive' AND deleted_at IS NULL");
        $deleted = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NOT NULL");

        return array(
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'deleted' => $deleted,
        );
    }
}
