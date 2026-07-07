<?php
defined('ABSPATH') || exit;

class B2B_Attribute_Model {

    public $id = 0;
    public $name_fa = '';
    public $name_en = '';
    public $code = '';
    public $type = 'text';
    public $options = null;
    public $is_required = 0;
    public $is_filterable = 0;
    public $is_searchable = 0;
    public $sort_order = 0;
    public $status = 1;
    public $deleted_at = null;
    public $created_at = '';
    public $updated_at = '';

    public static function from_row($row) {
        $model = new self();
        $model->id = (int) $row->id;
        $model->name_fa = (string) $row->name_fa;
        $model->name_en = (string) $row->name_en;
        $model->code = (string) $row->code;
        $model->type = (string) $row->type;
        $model->options = $row->options ? json_decode($row->options, true) : null;
        $model->is_required = (int) $row->is_required;
        $model->is_filterable = (int) $row->is_filterable;
        $model->is_searchable = (int) $row->is_searchable;
        $model->sort_order = (int) $row->sort_order;
        $model->status = (int) $row->status;
        $model->deleted_at = $row->deleted_at;
        $model->created_at = (string) $row->created_at;
        $model->updated_at = (string) $row->updated_at;
        return $model;
    }

    public function to_array() {
        return array(
            'id' => $this->id,
            'name_fa' => $this->name_fa,
            'name_en' => $this->name_en,
            'code' => $this->code,
            'type' => $this->type,
            'options' => $this->options,
            'is_required' => $this->is_required,
            'is_filterable' => $this->is_filterable,
            'is_searchable' => $this->is_searchable,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        );
    }
}
