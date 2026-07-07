<?php
namespace B2B\ProductFeatures\Database;

defined('ABSPATH') || exit;

class Feature_DB {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'b2b_product_features';
    }

    public static function create_table() {
        global $wpdb;
        $table = self::table();
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL,
            group_name VARCHAR(200) DEFAULT '',
            feature_type VARCHAR(50) NOT NULL DEFAULT 'text',
            unit VARCHAR(50) DEFAULT NULL,
            options TEXT,
            is_required TINYINT(1) DEFAULT 0,
            is_searchable TINYINT(1) DEFAULT 0,
            is_filterable TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_slug (slug)
        ) {$charset};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function insert($data) {
        global $wpdb;
        $wpdb->insert(self::table(), array(
            'name'          => sanitize_text_field($data['name']),
            'slug'          => sanitize_title($data['slug'] ?: $data['name']),
            'group_name'    => sanitize_text_field($data['group_name'] ?? ''),
            'feature_type'  => sanitize_key($data['feature_type'] ?? 'text'),
            'unit'          => sanitize_text_field($data['unit'] ?? ''),
            'options'       => is_array($data['options'] ?? null) ? wp_json_encode($data['options']) : sanitize_text_field($data['options'] ?? ''),
            'is_required'   => intval($data['is_required'] ?? 0),
            'is_searchable' => intval($data['is_searchable'] ?? 0),
            'is_filterable' => intval($data['is_filterable'] ?? 0),
            'is_active'     => intval($data['is_active'] ?? 1),
            'sort_order'    => intval($data['sort_order'] ?? 0),
        ));
        return $wpdb->insert_id;
    }

    public static function update($id, $data) {
        global $wpdb;
        $update = array();
        $fields = array('name','slug','group_name','feature_type','unit','is_required','is_searchable','is_filterable','is_active','sort_order');
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = in_array($f, array('is_required','is_searchable','is_filterable','is_active','sort_order'), true) ? intval($data[$f]) : sanitize_text_field($data[$f]);
            }
        }
        if (array_key_exists('options', $data)) {
            $update['options'] = is_array($data['options']) ? wp_json_encode($data['options']) : sanitize_text_field($data['options']);
        }
        $update['updated_at'] = current_time('mysql');
        return $wpdb->update(self::table(), $update, array('id' => intval($id)));
    }

    public static function delete($id) {
        global $wpdb;
        return $wpdb->update(self::table(), array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function get($id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::table() . " WHERE id = %d AND deleted_at IS NULL", intval($id)));
        if ($row && !empty($row->options)) {
            $decoded = json_decode($row->options, true);
            $row->options = is_array($decoded) ? $decoded : array();
        }
        return $row;
    }

    public static function get_all($args = array()) {
        global $wpdb;
        $table = self::table();
        $where = "deleted_at IS NULL";
        $values = array();

        if (!empty($args['search'])) {
            $where .= " AND (name LIKE %s OR slug LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s;
            $values[] = $s;
        }
        if (!empty($args['group_name'])) {
            $where .= " AND group_name = %s";
            $values[] = sanitize_text_field($args['group_name']);
        }
        if (isset($args['is_active'])) {
            $where .= " AND is_active = %d";
            $values[] = intval($args['is_active']);
        }

        $per_page = intval($args['per_page'] ?? 20);
        $page = max(1, intval($args['page'] ?? 1));
        $offset = ($page - 1) * $per_page;

        $total = empty($values)
            ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where}")
            : (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}", $values));

        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY sort_order ASC, name ASC LIMIT %d OFFSET %d";
        $params = array_merge($values, array($per_page, $offset));
        $items = $wpdb->get_results($wpdb->prepare($sql, $params));

        if ($items) {
            foreach ($items as &$item) {
                if (!empty($item->options)) {
                    $decoded = json_decode($item->options, true);
                    $item->options = is_array($decoded) ? $decoded : array();
                }
            }
        }

        return array(
            'items' => $items ?: array(),
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $per_page)),
            'page'  => $page,
        );
    }

    public static function get_active_all() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT id, name, slug, group_name, feature_type, unit, options, is_required FROM " . self::table() . " WHERE is_active = 1 AND deleted_at IS NULL ORDER BY sort_order ASC, name ASC");
        if ($rows) {
            foreach ($rows as &$row) {
                if (!empty($row->options)) {
                    $decoded = json_decode($row->options, true);
                    $row->options = is_array($decoded) ? $decoded : array();
                }
            }
        }
        return $rows ?: array();
    }

    public static function get_groups() {
        global $wpdb;
        $rows = $wpdb->get_col("SELECT DISTINCT group_name FROM " . self::table() . " WHERE deleted_at IS NULL AND group_name != '' ORDER BY group_name ASC");
        return $rows ?: array();
    }

    public static function get_stats() {
        global $wpdb;
        $table = self::table();
        return array(
            'total'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL"),
            'active' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_active = 1 AND deleted_at IS NULL"),
            'groups' => count(self::get_groups()),
        );
    }
}
