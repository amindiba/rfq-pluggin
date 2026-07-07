<?php
defined('ABSPATH') || exit;

class B2B_Category_Repository {

    private function table() {
        global $wpdb;
        return $wpdb->prefix . 'b2b_categories';
    }

    public function find($id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $this->table() . " WHERE id = %d", intval($id)));
        return $row ? B2B_Category_Model::from_row($row) : null;
    }

    public function find_by_slug($slug) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $this->table() . " WHERE slug = %s AND deleted_at IS NULL", sanitize_title($slug)));
        return $row ? B2B_Category_Model::from_row($row) : null;
    }

    public function find_all($args = array()) {
        global $wpdb;
        $table = $this->table();

        $defaults = array('search' => '', 'status' => '', 'parent_id' => null, 'per_page' => 20, 'page' => 1, 'orderby' => 'sort_order', 'order' => 'ASC', 'include_deleted' => false);
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(name_fa LIKE %s OR name_en LIKE %s OR slug LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s; $values[] = $s; $values[] = $s;
        }
        if (!empty($args['status'])) { $where[] = "status = %s"; $values[] = $args['status']; }
        if ($args['parent_id'] !== null) { $where[] = "parent_id = %d"; $values[] = intval($args['parent_id']); }
        if (!$args['include_deleted']) { $where[] = "deleted_at IS NULL"; }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];
        $allowed = array('id', 'name_fa', 'name_en', 'slug', 'sort_order', 'created_at');
        $orderby = in_array($args['orderby'], $allowed) ? $args['orderby'] : 'sort_order';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        if (!empty($values)) {
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", $values));
            $params = array_merge($values, array($args['per_page'], $offset));
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $params));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}");
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $args['per_page'], $offset));
        }

        $items = array();
        if ($rows) { foreach ($rows as $r) { $items[] = B2B_Category_Model::from_row($r); } }

        return array('items' => $items, 'total' => $total, 'pages' => ceil($total / $args['per_page']), 'page' => $args['page'], 'per_page' => $args['per_page']);
    }

    public function find_children($parent_id) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $this->table() . " WHERE parent_id = %d AND deleted_at IS NULL ORDER BY sort_order ASC", intval($parent_id)));
        $items = array();
        if ($rows) { foreach ($rows as $r) { $items[] = B2B_Category_Model::from_row($r); } }
        return $items;
    }

    public function find_tree() {
        $all = $this->find_all(array('per_page' => 9999));
        $tree = array();
        $lookup = array();
        foreach ($all['items'] as $cat) {
            $cat->children = array();
            $lookup[$cat->id] = $cat;
        }
        foreach ($lookup as $cat) {
            if ($cat->parent_id && isset($lookup[$cat->parent_id])) {
                $lookup[$cat->parent_id]->children[] = $cat;
            } else {
                $tree[] = $cat;
            }
        }
        return $tree;
    }

    public function save(B2B_Category_Model $model) {
        global $wpdb;
        $table = $this->table();
        $data = $model->to_array();
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['product_count']);

        $data['parent_id'] = $data['parent_id'] ?: null;
        $data['image_url'] = $data['image_url'] ?: null;
        $data['description'] = $data['description'] ?: null;
        $data['icon'] = $data['icon'] ?: null;
        $data['updated_at'] = current_time('mysql');

        if ($model->id > 0) {
            $wpdb->update($table, $data, array('id' => $model->id));
            return $model->id;
        }

        $data['created_at'] = current_time('mysql');
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }

    public function delete($id, $permanent = false) {
        global $wpdb;
        $table = $this->table();
        if ($permanent) {
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

        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}", $values));
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    }

    public function update_product_count($category_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}b2b_products WHERE category_id = %d AND deleted_at IS NULL AND status != 0", intval($category_id)));
        return $wpdb->update($this->table(), array('product_count' => intval($count)), array('id' => intval($category_id)));
    }
}
