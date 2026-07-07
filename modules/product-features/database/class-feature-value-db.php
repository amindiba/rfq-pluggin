<?php
namespace B2B\ProductFeatures\Database;

defined('ABSPATH') || exit;

class FeatureValue_DB {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'b2b_product_feature_values';
    }

    public static function create_table() {
        global $wpdb;
        $table = self::table();
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            feature_id BIGINT UNSIGNED NOT NULL,
            feature_key VARCHAR(100) NOT NULL,
            value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_product (product_id),
            KEY idx_feature (feature_id)
        ) {$charset};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function save_values($product_id, $features, $values) {
        global $wpdb;
        $table = self::table();

        foreach ($features as $feat) {
            $val = isset($values[$feat->slug]) ? $values[$feat->slug] : '';
            if (is_array($val)) $val = wp_json_encode($val);
            $val = sanitize_text_field($val);

            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE product_id = %d AND feature_key = %s",
                intval($product_id), $feat->slug
            ));

            if ($existing) {
                $wpdb->update($table, array('value' => $val, 'updated_at' => current_time('mysql')), array('id' => intval($existing)));
            } else {
                $wpdb->insert($table, array(
                    'product_id'  => intval($product_id),
                    'feature_id'  => intval($feat->id),
                    'feature_key' => $feat->slug,
                    'value'       => $val,
                ));
            }
        }
    }

    public static function get_values($product_id) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT feature_key, value FROM " . self::table() . " WHERE product_id = %d",
            intval($product_id)
        ));
        $result = array();
        if ($rows) {
            foreach ($rows as $row) {
                $result[$row->feature_key] = $row->value;
            }
        }
        return $result;
    }

    public static function delete_by_product($product_id) {
        global $wpdb;
        return $wpdb->delete(self::table(), array('product_id' => intval($product_id)));
    }
}
