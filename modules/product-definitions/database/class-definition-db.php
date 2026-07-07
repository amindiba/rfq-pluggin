<?php
namespace B2B\ProductDefinitions\Database;

defined('ABSPATH') || exit;

class Definition_DB {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'b2b_product_definitions';
    }

    public static function create_table() {
        global $wpdb;
        $table = self::table();
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL,
            description TEXT,
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
            'name'        => sanitize_text_field($data['name']),
            'slug'        => sanitize_title($data['slug'] ?: $data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'is_active'   => intval($data['is_active'] ?? 1),
            'sort_order'  => intval($data['sort_order'] ?? 0),
        ));
        return $wpdb->insert_id;
    }

    public static function update($id, $data) {
        global $wpdb;
        $update = array();
        if (isset($data['name']))        $update['name'] = sanitize_text_field($data['name']);
        if (isset($data['slug']))         $update['slug'] = sanitize_title($data['slug']);
        if (isset($data['description']))  $update['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['is_active']))    $update['is_active'] = intval($data['is_active']);
        if (isset($data['sort_order']))   $update['sort_order'] = intval($data['sort_order']);
        $update['updated_at'] = current_time('mysql');
        return $wpdb->update(self::table(), $update, array('id' => intval($id)));
    }

    public static function delete($id) {
        global $wpdb;
        return $wpdb->update(self::table(), array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function get($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::table() . " WHERE id = %d AND deleted_at IS NULL", intval($id)));
    }

    public static function get_all($args = array()) {
        global $wpdb;
        $table = self::table();
        $where = "deleted_at IS NULL";
        $values = array();

        if (!empty($args['search'])) {
            $where .= " AND (name LIKE %s OR slug LIKE %s)";
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search;
            $values[] = $search;
        }
        if (isset($args['is_active'])) {
            $where .= " AND is_active = %d";
            $values[] = intval($args['is_active']);
        }

        $per_page = intval($args['per_page'] ?? 20);
        $page = max(1, intval($args['page'] ?? 1));
        $offset = ($page - 1) * $per_page;

        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        $total = empty($values) ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where}") : (int) $wpdb->get_var($wpdb->prepare($count_sql, $values));

        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY sort_order ASC, id ASC LIMIT %d OFFSET %d";
        $params = $values;
        $params[] = $per_page;
        $params[] = $offset;
        $items = $wpdb->get_results($wpdb->prepare($sql, $params));

        return array(
            'items'  => $items ?: array(),
            'total'  => $total,
            'pages'  => max(1, (int) ceil($total / $per_page)),
            'page'   => $page,
        );
    }

    public static function get_active_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT id, name, slug FROM " . self::table() . " WHERE is_active = 1 AND deleted_at IS NULL ORDER BY sort_order ASC, name ASC");
    }

    public static function get_stats() {
        global $wpdb;
        $table = self::table();
        return array(
            'total'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL"),
            'active' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_active = 1 AND deleted_at IS NULL"),
        );
    }
}
