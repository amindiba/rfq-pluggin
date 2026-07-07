<?php
defined('ABSPATH') || exit;

class B2B_Product_Repository {

    private function table() {
        global $wpdb;
        return $wpdb->prefix . 'b2b_products';
    }

    public function find($id) {
        global $wpdb;
        $t = $this->table();
        $ct = $wpdb->prefix . 'b2b_categories';
        $row = $wpdb->get_row($wpdb->prepare("SELECT p.*, c.name_fa AS category_name FROM {$t} p LEFT JOIN {$ct} c ON p.category_id = c.id WHERE p.id = %d", intval($id)));
        return $row ? B2B_Product_Model::from_row($row) : null;
    }

    public function find_by_sku($sku) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $this->table() . " WHERE sku = %s AND deleted_at IS NULL", sanitize_text_field($sku)));
        return $row ? B2B_Product_Model::from_row($row) : null;
    }

    public function find_all($args = array()) {
        global $wpdb;
        $pt = $this->table();
        $ct = $wpdb->prefix . 'b2b_categories';

        $defaults = array('search' => '', 'status' => '', 'category_id' => '', 'per_page' => 20, 'page' => 1, 'orderby' => 'created_at', 'order' => 'DESC', 'include_deleted' => false);
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(p.name_fa LIKE %s OR p.name_en LIKE %s OR p.sku LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s; $values[] = $s; $values[] = $s;
        }
        if (!empty($args['status'])) { $where[] = "p.status = %s"; $values[] = $args['status']; }
        if (!empty($args['category_id'])) { $where[] = "p.category_id = %d"; $values[] = intval($args['category_id']); }
        if (!$args['include_deleted']) { $where[] = "p.deleted_at IS NULL"; }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];
        $allowed = array('id', 'sku', 'name_fa', 'name_en', 'created_at', 'sort_order');
        $orderby = in_array($args['orderby'], $allowed) ? "p.{$args['orderby']}" : 'p.created_at';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $join = "LEFT JOIN {$ct} c ON p.category_id = c.id";

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$pt} p WHERE {$where_clause}", $values));
            $params = array_merge($values, array($args['per_page'], $offset));
            $rows = $wpdb->get_results($wpdb->prepare("SELECT p.*, c.name_fa AS category_name FROM {$pt} p {$join} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $params));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pt} p WHERE {$where_clause}");
            $rows = $wpdb->get_results($wpdb->prepare("SELECT p.*, c.name_fa AS category_name FROM {$pt} p {$join} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        $items = array();
        if ($rows) { foreach ($rows as $r) { $items[] = B2B_Product_Model::from_row($r); } }

        return array('items' => $items, 'total' => $total, 'pages' => ceil($total / $args['per_page']), 'page' => $args['page'], 'per_page' => $args['per_page']);
    }

    public function save(B2B_Product_Model $model) {
        global $wpdb;
        $table = $this->table();
        $data = $model->to_array();
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['category_name'], $data['attributes']);

        $data['category_id'] = $data['category_id'] ?: null;
        $data['description'] = $data['description'] ?: null;
        $data['short_desc'] = $data['short_desc'] ?: null;
        $data['weight'] = $data['weight'] ?: null;
        $data['max_order_qty'] = $data['max_order_qty'] ?: null;
        $data['meta'] = $data['meta'] ? wp_json_encode($data['meta']) : null;
        $data['tags'] = $data['tags'] ? wp_json_encode($data['tags']) : null;
        $data['images'] = $data['images'] ? wp_json_encode($data['images']) : null;
        $data['updated_at'] = current_time('mysql');
        $data['updated_by'] = get_current_user_id();

        if ($model->id > 0) {
            $wpdb->update($table, $data, array('id' => $model->id));
            return $model->id;
        }

        $data['created_at'] = current_time('mysql');
        $data['created_by'] = get_current_user_id();
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }

    public function delete($id, $permanent = false) {
        global $wpdb;
        $table = $this->table();
        if ($permanent) {
            $wpdb->delete($wpdb->prefix . 'b2b_attribute_values', array('product_id' => intval($id)));
            return $wpdb->delete($table, array('id' => intval($id)));
        }
        return $wpdb->update($table, array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public function restore($id) {
        global $wpdb;
        return $wpdb->update($this->table(), array('deleted_at' => null), array('id' => intval($id)));
    }

    public function count($filters = array()) {
        global $wpdb;
        $table = $this->table();
        $where = "deleted_at IS NULL";
        $values = array();

        if (!empty($filters['status'])) { $where .= " AND status = %s"; $values[] = $filters['status']; }
        if (!empty($filters['category_id'])) { $where .= " AND category_id = %d"; $values[] = intval($filters['category_id']); }

        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}", $values));
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    }

    public function update_category_count($category_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table()} WHERE category_id = %d AND deleted_at IS NULL AND status != 0", intval($category_id)));
        $wpdb->update($wpdb->prefix . 'b2b_categories', array('product_count' => intval($count)), array('id' => intval($category_id)));
    }
}
