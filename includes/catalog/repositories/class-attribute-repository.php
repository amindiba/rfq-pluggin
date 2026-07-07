<?php
defined('ABSPATH') || exit;

class B2B_Attribute_Repository {

    private function table() {
        global $wpdb;
        return $wpdb->prefix . 'b2b_product_attributes';
    }

    private function values_table() {
        global $wpdb;
        return $wpdb->prefix . 'b2b_attribute_values';
    }

    public function find($id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $this->table() . " WHERE id = %d", intval($id)));
        return $row ? B2B_Attribute_Model::from_row($row) : null;
    }

    public function find_by_code($code) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $this->table() . " WHERE code = %s AND deleted_at IS NULL", sanitize_text_field($code)));
        return $row ? B2B_Attribute_Model::from_row($row) : null;
    }

    public function find_all($args = array()) {
        global $wpdb;
        $table = $this->table();

        $defaults = array('search' => '', 'type' => '', 'per_page' => 20, 'page' => 1, 'orderby' => 'sort_order', 'order' => 'ASC', 'include_deleted' => false);
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(name_fa LIKE %s OR name_en LIKE %s OR code LIKE %s)";
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $s; $values[] = $s; $values[] = $s;
        }
        if (!empty($args['type'])) { $where[] = "type = %s"; $values[] = $args['type']; }
        if (!$args['include_deleted']) { $where[] = "deleted_at IS NULL"; }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];
        $allowed = array('id', 'name_fa', 'name_en', 'code', 'sort_order', 'created_at');
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
        if ($rows) { foreach ($rows as $r) { $items[] = B2B_Attribute_Model::from_row($r); } }

        return array('items' => $items, 'total' => $total, 'pages' => ceil($total / $args['per_page']), 'page' => $args['page'], 'per_page' => $args['per_page']);
    }

    public function save(B2B_Attribute_Model $model) {
        global $wpdb;
        $table = $this->table();
        $data = array(
            'name_fa' => $model->name_fa,
            'name_en' => $model->name_en,
            'code' => $model->code,
            'type' => $model->type,
            'options' => $model->options ? wp_json_encode($model->options) : null,
            'is_required' => $model->is_required,
            'is_filterable' => $model->is_filterable,
            'is_searchable' => $model->is_searchable,
            'sort_order' => $model->sort_order,
            'status' => $model->status,
            'updated_at' => current_time('mysql'),
        );

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
        if ($permanent) {
            $wpdb->delete($this->values_table(), array('attribute_id' => intval($id)));
            return $wpdb->delete($this->table(), array('id' => intval($id)));
        }
        return $wpdb->update($this->table(), array('deleted_at' => current_time('mysql')), array('id' => intval($id)));
    }

    public function restore($id) {
        global $wpdb;
        return $wpdb->update($this->table(), array('deleted_at' => null), array('id' => intval($id)));
    }

    public function get_product_attributes($product_id) {
        global $wpdb;
        $av = $this->values_table();
        $a = $this->table();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT av.*, a.name_fa, a.name_en, a.code, a.type, a.options FROM {$av} av INNER JOIN {$a} a ON av.attribute_id = a.id WHERE av.product_id = %d ORDER BY av.sort_order ASC",
            intval($product_id)
        ));
    }

    public function set_product_attribute($product_id, $attribute_id, $value) {
        global $wpdb;
        $table = $this->values_table();
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE product_id = %d AND attribute_id = %d", intval($product_id), intval($attribute_id)));

        $data = array(
            'product_id' => intval($product_id),
            'attribute_id' => intval($attribute_id),
            'value_text' => is_string($value) ? sanitize_text_field($value) : '',
            'value_number' => is_numeric($value) ? floatval($value) : null,
        );

        if ($existing) {
            return $wpdb->update($table, $data, array('id' => intval($existing)));
        }
        return $wpdb->insert($table, $data);
    }

    public function delete_product_attribute($product_id, $attribute_id) {
        global $wpdb;
        return $wpdb->delete($this->values_table(), array('product_id' => intval($product_id), 'attribute_id' => intval($attribute_id)));
    }

    public function count($filters = array()) {
        global $wpdb;
        $table = $this->table();
        $where = "deleted_at IS NULL";
        $values = array();
        if (!empty($filters['type'])) { $where .= " AND type = %s"; $values[] = $filters['type']; }
        if (!empty($values)) { return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}", $values)); }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    }
}
