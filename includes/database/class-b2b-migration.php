<?php
defined('ABSPATH') || exit;

class B2B_Migration {

    private static $version_key = 'b2b_catalog_db_version';
    private static $current_version = '1.4.0';

    public static function run() {
        $installed = get_option(self::$version_key, '0.0.0');

        if (version_compare($installed, self::$current_version, '<')) {
            self::migrate($installed);
            update_option(self::$version_key, self::$current_version);
        }
    }

    private static function migrate($from_version) {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // Categories table
        $categories = $wpdb->prefix . 'b2b_catalog_categories';
        $sql_categories = "CREATE TABLE IF NOT EXISTS {$categories} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            parent_id BIGINT UNSIGNED DEFAULT NULL,
            name VARCHAR(150) NOT NULL,
            slug VARCHAR(150) NOT NULL,
            description TEXT DEFAULT NULL,
            image_id BIGINT UNSIGNED DEFAULT NULL,
            sort_order INT UNSIGNED DEFAULT 0,
            status TINYINT(1) DEFAULT 1,
            depth SMALLINT UNSIGNED DEFAULT 0,
            path VARCHAR(500) DEFAULT '',
            product_count INT UNSIGNED DEFAULT 0,
            meta LONGTEXT DEFAULT NULL,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_slug (slug),
            KEY idx_parent (parent_id),
            KEY idx_status (status),
            KEY idx_sort (sort_order),
            KEY idx_deleted (deleted_at)
        ) {$charset};";

        // Products table
        $products = $wpdb->prefix . 'b2b_catalog_products';
        $sql_products = "CREATE TABLE IF NOT EXISTS {$products} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sku VARCHAR(100) NOT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            short_desc VARCHAR(500) DEFAULT NULL,
            category_id BIGINT UNSIGNED DEFAULT NULL,
            base_unit VARCHAR(20) DEFAULT 'pcs',
            weight DECIMAL(10,3) DEFAULT NULL,
            weight_unit VARCHAR(10) DEFAULT 'kg',
            min_order_qty DECIMAL(10,3) DEFAULT 1,
            max_order_qty DECIMAL(10,3) DEFAULT NULL,
            lead_time_days SMALLINT UNSIGNED DEFAULT 0,
            status TINYINT(1) DEFAULT 0,
            visibility TINYINT(1) DEFAULT 1,
            has_variants TINYINT(1) DEFAULT 0,
            has_attributes TINYINT(1) DEFAULT 0,
            meta LONGTEXT DEFAULT NULL,
            tags LONGTEXT DEFAULT NULL,
            images LONGTEXT DEFAULT NULL,
            regular_price DECIMAL(12,3) DEFAULT NULL,
            sale_price DECIMAL(12,3) DEFAULT NULL,
            stock_status VARCHAR(20) DEFAULT 'instock',
            stock_qty INT DEFAULT NULL,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            updated_by BIGINT UNSIGNED DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_sku (sku),
            UNIQUE KEY uk_slug (slug),
            KEY idx_category (category_id),
            KEY idx_status (status),
            KEY idx_visibility (visibility),
            KEY idx_created (created_at),
            KEY idx_deleted (deleted_at),
            FULLTEXT KEY ft_search (name, sku, description)
        ) {$charset};";

        // Product attributes table
        $attributes = $wpdb->prefix . 'b2b_catalog_attributes';
        $sql_attributes = "CREATE TABLE IF NOT EXISTS {$attributes} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(50) NOT NULL,
            type VARCHAR(30) NOT NULL DEFAULT 'text',
            options LONGTEXT DEFAULT NULL,
            is_required TINYINT(1) DEFAULT 0,
            is_filterable TINYINT(1) DEFAULT 0,
            is_searchable TINYINT(1) DEFAULT 0,
            sort_order INT UNSIGNED DEFAULT 0,
            status TINYINT(1) DEFAULT 1,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_code (code),
            KEY idx_type (type),
            KEY idx_status (status),
            KEY idx_deleted (deleted_at)
        ) {$charset};";

        // Attribute values table (EAV)
        $attr_values = $wpdb->prefix . 'b2b_catalog_attribute_values';
        $sql_attr_values = "CREATE TABLE IF NOT EXISTS {$attr_values} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            attribute_id BIGINT UNSIGNED NOT NULL,
            value_text TEXT DEFAULT NULL,
            value_number DECIMAL(12,3) DEFAULT NULL,
            sort_order INT UNSIGNED DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_product (product_id),
            KEY idx_attribute (attribute_id),
            KEY idx_product_attr (product_id, attribute_id),
            CONSTRAINT fk_av_product FOREIGN KEY (product_id) REFERENCES {$products}(id) ON DELETE CASCADE,
            CONSTRAINT fk_av_attribute FOREIGN KEY (attribute_id) REFERENCES {$attributes}(id) ON DELETE CASCADE
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_categories);
        dbDelta($sql_products);
        dbDelta($sql_attributes);
        dbDelta($sql_attr_values);
    }

    public static function rollback() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}b2b_catalog_attribute_values");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}b2b_catalog_attributes");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}b2b_catalog_products");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}b2b_catalog_categories");
        delete_option(self::$version_key);
    }
}
