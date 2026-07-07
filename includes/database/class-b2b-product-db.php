<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Product_DB {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $categories_table = $wpdb->prefix . 'b2b_categories';
        $products_table = $wpdb->prefix . 'b2b_products';
        $attributes_table = $wpdb->prefix . 'b2b_product_attributes';
        $attr_values_table = $wpdb->prefix . 'b2b_attribute_values';

        $sql = "CREATE TABLE {$categories_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            parent_id BIGINT UNSIGNED DEFAULT NULL,
            name_fa VARCHAR(150) NOT NULL,
            name_en VARCHAR(150) NOT NULL,
            slug VARCHAR(150) NOT NULL,
            description TEXT DEFAULT '',
            icon VARCHAR(50) DEFAULT '',
            image_url VARCHAR(500) DEFAULT '',
            sort_order INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            depth TINYINT UNSIGNED DEFAULT 0,
            path VARCHAR(500) DEFAULT '',
            product_count INT UNSIGNED DEFAULT 0,
            meta JSON DEFAULT NULL,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_slug (slug),
            KEY idx_parent (parent_id),
            KEY idx_status (status),
            KEY idx_sort (sort_order),
            KEY idx_depth (depth),
            KEY idx_deleted (deleted_at)
        ) {$charset};";

        $sql .= "CREATE TABLE {$products_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sku VARCHAR(100) NOT NULL,
            name_fa VARCHAR(255) NOT NULL,
            name_en VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description TEXT DEFAULT '',
            short_desc VARCHAR(500) DEFAULT '',
            category_id BIGINT UNSIGNED DEFAULT NULL,
            base_unit VARCHAR(20) DEFAULT 'pcs',
            weight DECIMAL(10,3) DEFAULT NULL,
            weight_unit VARCHAR(10) DEFAULT 'kg',
            min_order_qty DECIMAL(10,3) DEFAULT 1,
            max_order_qty DECIMAL(10,3) DEFAULT NULL,
            lead_time_days SMALLINT UNSIGNED DEFAULT 0,
            status VARCHAR(20) DEFAULT 'draft',
            visibility VARCHAR(20) DEFAULT 'visible',
            has_variants TINYINT(1) DEFAULT 0,
            has_attributes TINYINT(1) DEFAULT 0,
            meta JSON DEFAULT NULL,
            tags JSON DEFAULT NULL,
            images JSON DEFAULT NULL,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            updated_by BIGINT UNSIGNED DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_sku (sku),
            UNIQUE KEY idx_slug (slug),
            KEY idx_category (category_id),
            KEY idx_status (status),
            KEY idx_visibility (visibility),
            KEY idx_deleted (deleted_at),
            FULLTEXT KEY idx_search (name_fa, name_en, sku, description)
        ) {$charset};";

        $sql .= "CREATE TABLE {$attributes_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name_fa VARCHAR(100) NOT NULL,
            name_en VARCHAR(100) NOT NULL,
            code VARCHAR(50) NOT NULL,
            type VARCHAR(30) NOT NULL DEFAULT 'text',
            options JSON DEFAULT NULL,
            is_required TINYINT(1) DEFAULT 0,
            is_filterable TINYINT(1) DEFAULT 0,
            is_searchable TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_code (code),
            KEY idx_type (type),
            KEY idx_status (status),
            KEY idx_deleted (deleted_at)
        ) {$charset};";

        $sql .= "CREATE TABLE {$attr_values_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            attribute_id BIGINT UNSIGNED NOT NULL,
            value_text TEXT DEFAULT '',
            value_number DECIMAL(12,3) DEFAULT NULL,
            sort_order INT DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_product (product_id),
            KEY idx_attribute (attribute_id),
            KEY idx_product_attr (product_id, attribute_id)
        ) {$charset}";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('b2b_product_db_version', '1.0.0');
    }

    public static function drop_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}b2b_attribute_values");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}b2b_product_attributes");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}b2b_products");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}b2b_categories");
        delete_option('b2b_product_db_version');
    }
}
