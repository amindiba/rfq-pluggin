<?php
defined('ABSPATH') || exit;

class B2B_Category_Model {

    public $id = 0;
    public $parent_id = null;
    public $name_fa = '';
    public $name_en = '';
    public $slug = '';
    public $description = '';
    public $icon = '';
    public $image_url = '';
    public $sort_order = 0;
    public $status = 1;
    public $depth = 0;
    public $path = '';
    public $product_count = 0;
    public $deleted_at = null;
    public $created_at = '';
    public $updated_at = '';

    public static function from_row($row) {
        $model = new self();
        $model->id = (int) $row->id;
        $model->parent_id = $row->parent_id ? (int) $row->parent_id : null;
        $model->name_fa = (string) $row->name_fa;
        $model->name_en = (string) $row->name_en;
        $model->slug = (string) $row->slug;
        $model->description = (string) ($row->description ?? '');
        $model->icon = (string) ($row->icon ?? '');
        $model->image_url = (string) ($row->image_url ?? '');
        $model->sort_order = (int) $row->sort_order;
        $model->status = (int) $row->status;
        $model->depth = (int) $row->depth;
        $model->path = (string) $row->path;
        $model->product_count = (int) $row->product_count;
        $model->deleted_at = $row->deleted_at;
        $model->created_at = (string) $row->created_at;
        $model->updated_at = (string) $row->updated_at;
        return $model;
    }

    public function to_array() {
        return array(
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name_fa' => $this->name_fa,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'image_url' => $this->image_url,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'depth' => $this->depth,
            'path' => $this->path,
            'product_count' => $this->product_count,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        );
    }
}
