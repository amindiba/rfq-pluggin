<?php
defined('ABSPATH') || exit;

class B2B_Product_Model {

    public $id = 0;
    public $sku = '';
    public $name_fa = '';
    public $name_en = '';
    public $slug = '';
    public $description = '';
    public $short_desc = '';
    public $category_id = null;
    public $category_name = '';
    public $base_unit = 'pcs';
    public $weight = null;
    public $weight_unit = 'kg';
    public $min_order_qty = 1;
    public $max_order_qty = null;
    public $lead_time_days = 0;
    public $status = 0;
    public $visibility = 1;
    public $has_variants = 0;
    public $has_attributes = 0;
    public $meta = null;
    public $tags = null;
    public $images = null;
    public $deleted_at = null;
    public $created_at = '';
    public $updated_at = '';
    public $created_by = null;
    public $updated_by = null;
    public $attributes = array();

    public static function from_row($row) {
        $model = new self();
        $model->id = (int) $row->id;
        $model->sku = (string) $row->sku;
        $model->name_fa = (string) $row->name_fa;
        $model->name_en = (string) $row->name_en;
        $model->slug = (string) $row->slug;
        $model->description = (string) ($row->description ?? '');
        $model->short_desc = (string) ($row->short_desc ?? '');
        $model->category_id = $row->category_id ? (int) $row->category_id : null;
        $model->category_name = (string) ($row->category_name ?? '');
        $model->base_unit = (string) $row->base_unit;
        $model->weight = $row->weight !== null ? (float) $row->weight : null;
        $model->weight_unit = (string) $row->weight_unit;
        $model->min_order_qty = (float) $row->min_order_qty;
        $model->max_order_qty = $row->max_order_qty !== null ? (float) $row->max_order_qty : null;
        $model->lead_time_days = (int) $row->lead_time_days;
        $model->status = (int) $row->status;
        $model->visibility = (int) $row->visibility;
        $model->has_variants = (int) $row->has_variants;
        $model->has_attributes = (int) $row->has_attributes;
        $model->meta = $row->meta ? json_decode($row->meta, true) : null;
        $model->tags = $row->tags ? json_decode($row->tags, true) : null;
        $model->images = $row->images ? json_decode($row->images, true) : null;
        $model->deleted_at = $row->deleted_at;
        $model->created_at = (string) $row->created_at;
        $model->updated_at = (string) $row->updated_at;
        $model->created_by = $row->created_by ? (int) $row->created_by : null;
        $model->updated_by = $row->updated_by ? (int) $row->updated_by : null;
        return $model;
    }

    public function to_array() {
        return array(
            'id' => $this->id,
            'sku' => $this->sku,
            'name_fa' => $this->name_fa,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_desc' => $this->short_desc,
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
            'base_unit' => $this->base_unit,
            'weight' => $this->weight,
            'weight_unit' => $this->weight_unit,
            'min_order_qty' => $this->min_order_qty,
            'max_order_qty' => $this->max_order_qty,
            'lead_time_days' => $this->lead_time_days,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'has_variants' => $this->has_variants,
            'has_attributes' => $this->has_attributes,
            'meta' => $this->meta,
            'tags' => $this->tags,
            'images' => $this->images,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        );
    }
}
