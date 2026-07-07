<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Geography_DB {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $provinces_table = $wpdb->prefix . 'b2b_provinces';
        $cities_table = $wpdb->prefix . 'b2b_cities';

        $sql_provinces = "CREATE TABLE {$provinces_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name_fa VARCHAR(100) NOT NULL,
            name_en VARCHAR(100) NOT NULL,
            code VARCHAR(10) NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            sort_order INT DEFAULT 0,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_deleted (deleted_at)
        ) {$charset};";

        $sql_cities = "CREATE TABLE {$cities_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            province_id BIGINT UNSIGNED NOT NULL,
            name_fa VARCHAR(100) NOT NULL,
            name_en VARCHAR(100) NOT NULL,
            code VARCHAR(10) NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            sort_order INT DEFAULT 0,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_province (province_id),
            KEY idx_status (status),
            KEY idx_deleted (deleted_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_provinces);
        dbDelta($sql_cities);

        update_option('b2b_geography_db_version', '1.0.0');
    }

    public static function drop_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}b2b_cities");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}b2b_provinces");
        delete_option('b2b_geography_db_version');
    }

    // ==================== PROVINCES ====================

    public static function get_provinces($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_provinces';

        $defaults = array(
            'search' => '',
            'status' => '',
            'orderby' => 'sort_order',
            'order' => 'ASC',
            'per_page' => 20,
            'page' => 1,
            'include_deleted' => false,
        );
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(name_fa LIKE %s OR name_en LIKE %s OR code LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s;
            $values[] = $s;
            $values[] = $s;
        }

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $values[] = $args['status'];
        }

        if (!$args['include_deleted']) {
            $where[] = "deleted_at IS NULL";
        }

        $where_clause = implode(' AND ', $where);

        $allowed_orderby = array('id', 'name_fa', 'name_en', 'code', 'sort_order', 'created_at');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'sort_order';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $offset = ($args['page'] - 1) * $args['per_page'];

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", $values));
            $params = array_merge($values, array($args['per_page'], $offset));
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $params));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}");
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        return array(
            'items' => $items ? $items : array(),
            'total' => $total,
            'pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
        );
    }

    public static function get_province($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}b2b_provinces WHERE id = %d", intval($id)));
    }

    public static function create_province($data) {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}b2b_provinces WHERE name_fa = %s AND deleted_at IS NOT NULL",
            sanitize_text_field($data['name_fa'])
        ));

        $result = $wpdb->insert($wpdb->prefix . 'b2b_provinces', array(
            'name_fa' => sanitize_text_field($data['name_fa']),
            'name_en' => sanitize_text_field($data['name_en']),
            'code' => sanitize_text_field($data['code']),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        return $result === false ? new WP_Error('db_error', $wpdb->last_error) : $wpdb->insert_id;
    }

    public static function update_province($id, $data) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_provinces', array(
            'name_fa' => sanitize_text_field($data['name_fa']),
            'name_en' => sanitize_text_field($data['name_en']),
            'code' => sanitize_text_field($data['code']),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'updated_at' => current_time('mysql'),
        ), array('id' => intval($id)));
    }

    public static function delete_province($id, $permanent = false) {
        global $wpdb;
        $cities = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}b2b_cities WHERE province_id = %d AND deleted_at IS NULL",
            intval($id)
        ));
        if ($cities > 0) {
            return new WP_Error('has_cities', 'این استان دارای شهر فعال است و قابل حذف نیست.');
        }
        if ($permanent) {
            return $wpdb->delete($wpdb->prefix . 'b2b_provinces', array('id' => intval($id)));
        }
        return $wpdb->update($wpdb->prefix . 'b2b_provinces', array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function restore_province($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_provinces', array('deleted_at' => null), array('id' => intval($id)));
    }

    public static function toggle_province_status($id) {
        global $wpdb;
        $current = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}b2b_provinces WHERE id = %d", intval($id)));
        $new = ($current === 'active') ? 'inactive' : 'active';
        return $wpdb->update($wpdb->prefix . 'b2b_provinces', array('status' => $new, 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function get_province_stats() {
        global $wpdb;
        $t = $wpdb->prefix . 'b2b_provinces';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t));
        if ($exists !== $t) return array('total' => 0, 'active' => 0, 'inactive' => 0, 'deleted' => 0);
        return array(
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE deleted_at IS NULL"),
            'active' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'active' AND deleted_at IS NULL"),
            'inactive' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'inactive' AND deleted_at IS NULL"),
            'deleted' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE deleted_at IS NOT NULL"),
        );
    }

    // ==================== CITIES ====================

    public static function get_cities($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_cities';
        $ptable = $wpdb->prefix . 'b2b_provinces';

        $defaults = array(
            'search' => '',
            'status' => '',
            'province_id' => '',
            'orderby' => 'c.sort_order',
            'order' => 'ASC',
            'per_page' => 20,
            'page' => 1,
            'include_deleted' => false,
        );
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(c.name_fa LIKE %s OR c.name_en LIKE %s OR c.code LIKE %s OR p.name_fa LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s;
            $values[] = $s;
            $values[] = $s;
            $values[] = $s;
        }

        if (!empty($args['status'])) {
            $where[] = "c.status = %s";
            $values[] = $args['status'];
        }

        if (!empty($args['province_id'])) {
            $where[] = "c.province_id = %d";
            $values[] = intval($args['province_id']);
        }

        if (!$args['include_deleted']) {
            $where[] = "c.deleted_at IS NULL";
        }

        $where_clause = implode(' AND ', $where);

        $allowed_orderby = array('c.id', 'c.name_fa', 'c.name_en', 'c.code', 'c.sort_order', 'c.created_at', 'p.name_fa');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'c.sort_order';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $offset = ($args['page'] - 1) * $args['per_page'];

        $join = "INNER JOIN {$ptable} p ON c.province_id = p.id";

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} c {$join} WHERE {$where_clause}", $values));
            $params = array_merge($values, array($args['per_page'], $offset));
            $items = $wpdb->get_results($wpdb->prepare("SELECT c.*, p.name_fa AS province_name FROM {$table} c {$join} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $params));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} c {$join} WHERE {$where_clause}");
            $items = $wpdb->get_results($wpdb->prepare("SELECT c.*, p.name_fa AS province_name FROM {$table} c {$join} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        return array(
            'items' => $items ? $items : array(),
            'total' => $total,
            'pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
        );
    }

    public static function get_city($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT c.*, p.name_fa AS province_name FROM {$wpdb->prefix}b2b_cities c INNER JOIN {$wpdb->prefix}b2b_provinces p ON c.province_id = p.id WHERE c.id = %d", intval($id)));
    }

    public static function create_city($data) {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}b2b_cities WHERE name_fa = %s AND province_id = %d AND deleted_at IS NOT NULL",
            sanitize_text_field($data['name_fa']),
            intval($data['province_id'])
        ));

        $result = $wpdb->insert($wpdb->prefix . 'b2b_cities', array(
            'province_id' => intval($data['province_id']),
            'name_fa' => sanitize_text_field($data['name_fa']),
            'name_en' => sanitize_text_field($data['name_en']),
            'code' => sanitize_text_field($data['code']),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));

        return $result === false ? new WP_Error('db_error', $wpdb->last_error) : $wpdb->insert_id;
    }

    public static function update_city($id, $data) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_cities', array(
            'province_id' => intval($data['province_id']),
            'name_fa' => sanitize_text_field($data['name_fa']),
            'name_en' => sanitize_text_field($data['name_en']),
            'code' => sanitize_text_field($data['code']),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'updated_at' => current_time('mysql'),
        ), array('id' => intval($id)));
    }

    public static function delete_city($id, $permanent = false) {
        global $wpdb;
        if ($permanent) {
            return $wpdb->delete($wpdb->prefix . 'b2b_cities', array('id' => intval($id)));
        }
        return $wpdb->update($wpdb->prefix . 'b2b_cities', array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function restore_city($id) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'b2b_cities', array('deleted_at' => null), array('id' => intval($id)));
    }

    public static function toggle_city_status($id) {
        global $wpdb;
        $current = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}b2b_cities WHERE id = %d", intval($id)));
        $new = ($current === 'active') ? 'inactive' : 'active';
        return $wpdb->update($wpdb->prefix . 'b2b_cities', array('status' => $new, 'updated_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public static function get_city_stats() {
        global $wpdb;
        $t = $wpdb->prefix . 'b2b_cities';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t));
        if ($exists !== $t) return array('total' => 0, 'active' => 0, 'inactive' => 0, 'deleted' => 0);
        return array(
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE deleted_at IS NULL"),
            'active' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'active' AND deleted_at IS NULL"),
            'inactive' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status = 'inactive' AND deleted_at IS NULL"),
            'deleted' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE deleted_at IS NOT NULL"),
        );
    }

    public static function bulk_delete($table, $ids) {
        global $wpdb;
        $t = $wpdb->prefix . $table;
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        return $wpdb->query($wpdb->prepare("UPDATE {$t} SET deleted_at = %s WHERE id IN ({$placeholders})", array_merge(array(current_time('mysql')), $ids)));
    }

    public static function bulk_restore($table, $ids) {
        global $wpdb;
        $t = $wpdb->prefix . $table;
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        return $wpdb->query($wpdb->prepare("UPDATE {$t} SET deleted_at = NULL WHERE id IN ({$placeholders})", $ids));
    }

    public static function seed_iran_data() {
        global $wpdb;
        $ptable = $wpdb->prefix . 'b2b_provinces';
        $ctable = $wpdb->prefix . 'b2b_cities';

        $p_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $ptable));
        if ($p_exists !== $ptable) return;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$ptable}");
        if ($count > 0) return;

        $provinces = array(
            array('آذربایجان شرقی', 'East Azerbaijan', '01'),
            array('آذربایجان غربی', 'West Azerbaijan', '02'),
            array('اردبیل', 'Ardabil', '03'),
            array('اصفهان', 'Isfahan', '04'),
            array('البرز', 'Alborz', '05'),
            array('ایلام', 'Ilam', '06'),
            array('بوشهر', 'Bushehr', '07'),
            array('تهران', 'Tehran', '08'),
            array('چهارمحال و بختیاری', 'Chaharmahal and Bakhtiari', '09'),
            array('خراسان جنوبی', 'South Khorasan', '10'),
            array('خراسان رضوی', 'Razavi Khorasan', '11'),
            array('خراسان شمالی', 'North Khorasan', '12'),
            array('خوزستان', 'Khuzestan', '13'),
            array('زنجان', 'Zanjan', '14'),
            array('سمنان', 'Semnan', '15'),
            array('سیستان و بلوچستان', 'Sistan and Baluchestan', '16'),
            array('فارس', 'Fars', '17'),
            array('قزوین', 'Qazvin', '18'),
            array('قم', 'Qom', '19'),
            array('کردستان', 'Kurdistan', '20'),
            array('کرمان', 'Kerman', '21'),
            array('کرمانشاه', 'Kermanshah', '22'),
            array('کهگیلویه و بویراحمد', 'Kohgiluyeh and Boyer-Ahmad', '23'),
            array('گلستان', 'Golestan', '24'),
            array('گیلان', 'Gilan', '25'),
            array('لرستان', 'Lorestan', '26'),
            array('مازندران', 'Mazandaran', '27'),
            array('مرکزی', 'Markazi', '28'),
            array('هرمزگان', 'Hormozgan', '29'),
            array('همدان', 'Hamedan', '30'),
            array('یزد', 'Yazd', '31'),
        );

        foreach ($provinces as $i => $p) {
            $wpdb->insert($ptable, array(
                'name_fa' => $p[0], 'name_en' => $p[1], 'code' => $p[2],
                'status' => 'active', 'sort_order' => $i + 1,
                'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql'),
            ));
            $pid = $wpdb->insert_id;
            self::seed_cities_for_province($pid, $p[0]);
        }
    }

    private static function seed_cities_for_province($pid, $province) {
        global $wpdb;
        $ctable = $wpdb->prefix . 'b2b_cities';

        $cities_map = array(
            'تهران' => array(array('تهران', 'Tehran', '001'), array('ری', 'Rey', '002'), array('تجریش', 'Tajrish', '003')),
            'اصفهان' => array(array('اصفهان', 'Isfahan', '001'), array('کاشان', 'Kashan', '002'), array('نائین', 'Nain', '003')),
            'فارس' => array(array('شیراز', 'Shiraz', '001'), array('مرودشت', 'Marvdasht', '002'), array('جهرم', 'Jahrom', '003')),
            'خراسان رضوی' => array(array('مشهد', 'Mashhad', '001'), array('نیشابور', 'Neyshabur', '002'), array('سبزوار', 'Sabzevar', '003')),
            'خوزستان' => array(array('اهواز', 'Ahvaz', '001'), array('آبادان', 'Abadan', '002'), array('ماهشهر', 'Mahshahr', '003')),
            'آذربایجان شرقی' => array(array('تبریز', 'Tabriz', '001'), array('مراغه', 'Maragheh', '002'), array('میانه', 'Mianeh', '003')),
            'آذربایجان غربی' => array(array('ارومیه', 'Urmia', '001'), array('خوی', 'Khoy', '002'), array('میاندوآب', 'Miandoab', '003')),
            'گیلان' => array(array('رشت', 'Rasht', '001'), array('لاهیجان', 'Lahijan', '002'), array('انزلی', 'Anzali', '003')),
            'مازندران' => array(array('ساری', 'Sari', '001'), array('بابل', 'Babol', '002'), array('آمل', 'Amol', '003')),
            'کرمان' => array(array('کرمان', 'Kerman', '001'), array('رفسنجان', 'Rafsanjan', '002'), array('سیرجان', 'Sirjan', '003')),
            'سیستان و بلوچستان' => array(array('زاهدان', 'Zahedan', '001'), array('چابهار', 'Chabahar', '002'), array('زابل', 'Zabol', '003')),
            'کرمانشاه' => array(array('کرمانشاه', 'Kermanshah', '001'), array('اسلام‌آباد', 'Islamabad', '002'), array('سنقر', 'Sangour', '003')),
            'گلستان' => array(array('گرگان', 'Gorgan', '001'), array('گنبدکاووس', 'Gonbad-e Kavus', '002'), array('آق‌قلا', 'Aq Qala', '003')),
            'هرمزگان' => array(array('بندرعباس', 'Bandar Abbas', '001'), array('بندرلنگه', 'Bandar Lengeh', '002'), array('قشم', 'Qeshm', '003')),
            'لرستان' => array(array('خرم‌آباد', 'Khorramabad', '001'), array('بروجرد', 'Borujerd', '002'), array('دورود', 'Dorud', '003')),
            'مرکزی' => array(array('اراک', 'Arak', '001'), array('ساوه', 'Saveh', '002'), array('خمین', 'Khomeyn', '003')),
            'همدان' => array(array('همدان', 'Hamedan', '001'), array('ملایر', 'Malayer', '002'), array('نهاوند', 'Nahavand', '003')),
            'کردستان' => array(array('سنندج', 'Sanandaj', '001'), array('سقز', 'Saqqez', '002'), array('بانه', 'Baneh', '003')),
            'زنجان' => array(array('زنجان', 'Zanjan', '001'), array('ابهر', 'Abhar', '002'), array('خدابنده', 'Khodabandeh', '003')),
            'سمنان' => array(array('سمنان', 'Semnan', '001'), array('شاهرود', 'Shahroud', '002'), array('دامغان', 'Damghan', '003')),
            'قم' => array(array('قم', 'Qom', '001')),
            'قزوین' => array(array('قزوین', 'Qazvin', '001'), array('البزر', 'Alvand', '002'), array('آبیک', 'Abik', '003')),
            'ایلام' => array(array('ایلام', 'Ilam', '001'), array('دهدران', 'Dehloran', '002'), array('آبدانان', 'Abdanan', '003')),
            'بوشهر' => array(array('بوشهر', 'Bushehr', '001'), array('برازجان', 'Brazjan', '002'), array('کنگان', 'Dayyer', '003')),
            'چهارمحال و بختیاری' => array(array('شهرکرد', 'Shahrekord', '001'), array('بروجن', 'Borujen', '002'), array('فارسان', 'Farsan', '003')),
            'خراسان جنوبی' => array(array('بیرجند', 'Birjand', '001'), array('قائنات', 'Qaen', '002'), array('فردوس', 'Ferdows', '003')),
            'خراسان شمالی' => array(array('بجنورد', 'Bojnord', '001'), array('شیروان', 'Shirvan', '002'), array('آشخانه', 'Ashkhaneh', '003')),
            'کهگیلویه و بویراحمد' => array(array('یاسوج', 'Yasuj', '001'), array('گچساران', 'Gachsaran', '002'), array('دهدشت', 'Dogonbadan', '003')),
            'اردبیل' => array(array('اردبیل', 'Ardabil', '001'), array('پارس‌آباد', 'Parsabad', '002'), array('مشگین‌شهر', 'Meshginshahr', '003')),
            'البرز' => array(array('کرج', 'Karaj', '001'), array('هشتگرد', 'Hashtgerd', '002'), array('نظرآباد', 'Nazarabad', '003')),
            'یزد' => array(array('یزد', 'Yazd', '001'), array('اردکان', 'Ardakan', '002'), array('میبد', 'Meybod', '003')),
        );

        if (isset($cities_map[$province])) {
            foreach ($cities_map[$province] as $c) {
                $wpdb->insert($ctable, array(
                    'province_id' => $pid, 'name_fa' => $c[0], 'name_en' => $c[1], 'code' => $c[2],
                    'status' => 'active', 'sort_order' => 1,
                    'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql'),
                ));
            }
        } else {
            $wpdb->insert($ctable, array(
                'province_id' => $pid, 'name_fa' => $province, 'name_en' => $province, 'code' => '001',
                'status' => 'active', 'sort_order' => 1,
                'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql'),
            ));
        }
    }
}
