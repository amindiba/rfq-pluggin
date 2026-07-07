<?php
namespace B2B\DynamicSpecs\Database;

defined('ABSPATH') || exit;

class Spec_DB {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'b2b_definition_specs';
    }

    public static function create_table() {
        global $wpdb;
        $table = self::table();
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            definition_id BIGINT UNSIGNED NOT NULL,
            label VARCHAR(200) NOT NULL,
            field_key VARCHAR(100) NOT NULL,
            field_type VARCHAR(50) NOT NULL DEFAULT 'text',
            description TEXT,
            placeholder VARCHAR(500),
            default_value VARCHAR(1000),
            options TEXT,
            is_required TINYINT(1) DEFAULT 0,
            is_searchable TINYINT(1) DEFAULT 0,
            is_filterable TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_definition (definition_id)
        ) {$charset};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function insert($data) {
        global $wpdb;
        $wpdb->insert(self::table(), array(
            'definition_id' => intval($data['definition_id']),
            'label'         => sanitize_text_field($data['label']),
            'field_key'     => sanitize_key($data['field_key']),
            'field_type'    => sanitize_key($data['field_type']),
            'description'   => sanitize_textarea_field($data['description'] ?? ''),
            'placeholder'   => sanitize_text_field($data['placeholder'] ?? ''),
            'default_value' => sanitize_text_field($data['default_value'] ?? ''),
            'options'       => is_array($data['options'] ?? null) ? wp_json_encode($data['options']) : sanitize_text_field($data['options'] ?? ''),
            'is_required'   => intval($data['is_required'] ?? 0),
            'is_searchable' => intval($data['is_searchable'] ?? 0),
            'is_filterable' => intval($data['is_filterable'] ?? 0),
            'sort_order'    => intval($data['sort_order'] ?? 0),
            'is_active'     => intval($data['is_active'] ?? 1),
        ));
        return $wpdb->insert_id;
    }

    public static function update($id, $data) {
        global $wpdb;
        $update = array();
        if (isset($data['label']))         $update['label'] = sanitize_text_field($data['label']);
        if (isset($data['field_key']))     $update['field_key'] = sanitize_key($data['field_key']);
        if (isset($data['field_type']))    $update['field_type'] = sanitize_key($data['field_type']);
        if (isset($data['description']))   $update['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['placeholder']))   $update['placeholder'] = sanitize_text_field($data['placeholder']);
        if (isset($data['default_value'])) $update['default_value'] = sanitize_text_field($data['default_value']);
        if (isset($data['options']))       $update['options'] = is_array($data['options']) ? wp_json_encode($data['options']) : sanitize_text_field($data['options']);
        if (isset($data['is_required']))   $update['is_required'] = intval($data['is_required']);
        if (isset($data['is_searchable'])) $update['is_searchable'] = intval($data['is_searchable']);
        if (isset($data['is_filterable'])) $update['is_filterable'] = intval($data['is_filterable']);
        if (isset($data['sort_order']))    $update['sort_order'] = intval($data['sort_order']);
        if (isset($data['is_active']))     $update['is_active'] = intval($data['is_active']);
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

    public static function get_by_definition($definition_id) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE definition_id = %d AND deleted_at IS NULL ORDER BY sort_order ASC, id ASC",
            intval($definition_id)
        ));
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
}
