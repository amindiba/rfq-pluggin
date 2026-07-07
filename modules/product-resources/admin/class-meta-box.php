<?php
/**
 * Product Resources Meta Box
 *
 * @package B2B_Procurement
 */

namespace B2B\ProductResources\Admin;

defined('ABSPATH') || exit;

class Meta_Box {

    private $meta_key = '_b2b_product_resources';

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'register'));
        add_action('save_post_product', array($this, 'save'), 10, 2);
    }

    public function register() {
        add_meta_box(
            'b2b_product_resources',
            '&#128194; مدیریت منابع محصول (Product Resources)',
            array($this, 'render'),
            'product',
            'normal',
            'high'
        );
    }

    public function render($post) {
        wp_nonce_field('b2b_product_resources_save', 'b2b_pr_nonce');
        $resources = get_post_meta($post->ID, $this->meta_key, true);
        if (!is_array($resources)) $resources = array();
        ?>
        <div id="b2b-pr-wrap">
            <p class="description b2b-pr-hint">
                فایل‌ها و مستندات مربوط به این محصول را مدیریت کنید. هر منبع شامل عنوان، توضیح، نوع فایل، فایل ضمیمه، لینک خارجی، تصویر شاخص، ترتیب نمایش و وضعیت فعال/غیرفعال است.
            </p>

            <div class="b2b-pr-toolbar">
                <button type="button" class="button b2b-pr-add-resource"><?php _e('+ افزودن منبع', 'b2b-procurement'); ?></button>
                <button type="button" class="button b2b-pr-collapse-all" style="margin-right:8px;">&#9660; بستن همه</button>
                <button type="button" class="button b2b-pr-expand-all" style="margin-right:8px;">&#9650; باز کردن همه</button>
            </div>

            <div id="b2b-pr-list" class="b2b-pr-list">
                <?php foreach ($resources as $idx => $res) : ?>
                    <?php $this->render_card($idx, $res); ?>
                <?php endforeach; ?>
            </div>

            <input type="hidden" name="b2b_resources_order" id="b2b-pr-order" value="" />
        </div>
        <?php
    }

    private function render_card($idx, $res) {
        $title        = isset($res['title']) ? esc_attr($res['title']) : '';
        $description  = isset($res['description']) ? esc_textarea($res['description']) : '';
        $file_type    = isset($res['file_type']) ? esc_attr($res['file_type']) : 'pdf';
        $file_id      = isset($res['file_id']) ? intval($res['file_id']) : 0;
        $file_url     = '';
        if ($file_id) {
            $file_url = wp_get_attachment_url($file_id);
            if (!$file_url) $file_url = '';
        }
        $external_url = isset($res['external_url']) ? esc_url($res['external_url']) : '';
        $thumb_id     = isset($res['thumb_id']) ? intval($res['thumb_id']) : 0;
        $thumb_url    = '';
        if ($thumb_id) {
            $thumb_src = wp_get_attachment_image_src($thumb_id, 'thumbnail');
            if ($thumb_src) $thumb_url = $thumb_src[0];
        }
        $sort_order   = isset($res['sort_order']) ? intval($res['sort_order']) : $idx;
        $active       = isset($res['active']) ? intval($res['active']) : 1;

        $file_types = array(
            'pdf' => 'PDF', 'word' => 'Word', 'excel' => 'Excel', 'ppt' => 'PowerPoint',
            'zip' => 'ZIP', 'image' => 'تصویر', 'video' => 'ویدیو', 'audio' => 'صدا',
            'cad' => 'CAD', 'dwg' => 'DWG', 'dxf' => 'DXF', 'step' => 'STEP', 'stl' => 'STL',
            'link' => 'لینک خارجی', 'custom' => 'سفارشی',
        );
        ?>
        <div class="b2b-pr-card" data-index="<?php echo $idx; ?>">
            <div class="b2b-pr-card-header" onclick="jQuery(this).closest('.b2b-pr-card').toggleClass('collapsed')">
                <span class="b2b-pr-drag-handle" title="بکشید برای تغییر ترتیب">&#9776;</span>
                <span class="b2b-pr-card-title"><?php echo $title ? esc_html($title) : 'منبع #' . ($idx + 1); ?></span>
                <span class="b2b-pr-card-type"><?php echo isset($file_types[$file_type]) ? $file_types[$file_type] : $file_type; ?></span>
                <span class="b2b-pr-card-status <?php echo $active ? 'active' : 'inactive'; ?>"><?php echo $active ? 'فعال' : 'غیرفعال'; ?></span>
                <span class="b2b-pr-card-toggle">&#9660;</span>
            </div>
            <div class="b2b-pr-card-body">
                <div class="b2b-pr-row">
                    <div class="b2b-pr-field" style="flex:2;">
                        <label>عنوان <span class="required">*</span></label>
                        <input type="text" name="b2b_resources[<?php echo $idx; ?>][title]" value="<?php echo $title; ?>" class="regular-text" required placeholder="عنوان منبع" />
                    </div>
                    <div class="b2b-pr-field" style="flex:1;">
                        <label>نوع فایل</label>
                        <select name="b2b_resources[<?php echo $idx; ?>][file_type]">
                            <?php foreach ($file_types as $key => $label) : ?>
                                <option value="<?php echo $key; ?>" <?php selected($file_type, $key); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="b2b-pr-field" style="flex:1;">
                        <label>ترتیب نمایش</label>
                        <input type="number" name="b2b_resources[<?php echo $idx; ?>][sort_order]" value="<?php echo $sort_order; ?>" min="0" style="width:80px;" />
                    </div>
                    <div class="b2b-pr-field" style="flex:1;">
                        <label>وضعیت</label>
                        <select name="b2b_resources[<?php echo $idx; ?>][active]">
                            <option value="1" <?php selected($active, 1); ?>>فعال</option>
                            <option value="0" <?php selected($active, 0); ?>>غیرفعال</option>
                        </select>
                    </div>
                </div>
                <div class="b2b-pr-row">
                    <div class="b2b-pr-field" style="flex:3;">
                        <label>توضیحات</label>
                        <textarea name="b2b_resources[<?php echo $idx; ?>][description]" rows="2" class="large-text" placeholder="توضیحات اختیاری منبع"><?php echo esc_textarea($res['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="b2b-pr-row">
                    <div class="b2b-pr-field" style="flex:2;">
                        <label>فایل ضمیمه</label>
                        <div class="b2b-pr-file-wrap">
                            <input type="hidden" name="b2b_resources[<?php echo $idx; ?>][file_id]" class="b2b-pr-file-id" value="<?php echo $file_id; ?>" />
                            <div class="b2b-pr-file-preview">
                                <?php if ($file_url) : ?>
                                    <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="b2b-pr-file-link"><?php echo esc_html(basename($file_url)); ?></a>
                                <?php else : ?>
                                    <span class="b2b-pr-no-file">فایلی انتخاب نشده</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button b2b-pr-upload-file" data-target="file">انتخاب فایل</button>
                            <button type="button" class="button b2b-pr-remove-file" data-type="file" <?php echo !$file_id ? 'style="display:none;"' : ''; ?>>حذف</button>
                        </div>
                    </div>
                    <div class="b2b-pr-field" style="flex:2;">
                        <label>لینک خارجی (اختیاری)</label>
                        <input type="url" name="b2b_resources[<?php echo $idx; ?>][external_url]" value="<?php echo $external_url; ?>" class="regular-text" placeholder="https://example.com/file.pdf" />
                    </div>
                </div>
                <div class="b2b-pr-row">
                    <div class="b2b-pr-field" style="flex:1;">
                        <label>تصویر شاخص</label>
                        <div class="b2b-pr-thumb-wrap">
                            <input type="hidden" name="b2b_resources[<?php echo $idx; ?>][thumb_id]" class="b2b-pr-thumb-id" value="<?php echo $thumb_id; ?>" />
                            <div class="b2b-pr-thumb-preview">
                                <?php if ($thumb_url) : ?>
                                    <img src="<?php echo esc_url($thumb_url); ?>" class="b2b-pr-thumb-img" />
                                <?php else : ?>
                                    <div class="b2b-pr-thumb-empty">&#128247;</div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button b2b-pr-upload-thumb" data-target="thumb">انتخاب تصویر</button>
                            <button type="button" class="button b2b-pr-remove-thumb" <?php echo !$thumb_id ? 'style="display:none;"' : ''; ?>>حذف</button>
                        </div>
                    </div>
                    <div class="b2b-pr-field" style="flex:1;">
                        <label>&nbsp;</label>
                        <button type="button" class="button b2b-pr-delete-card" style="color:#d63638;border-color:#d63638;">&#128465; حذف منبع</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function save($post_id, $post) {
        if (!isset($_POST['b2b_pr_nonce']) || !wp_verify_nonce($_POST['b2b_pr_nonce'], 'b2b_product_resources_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_product', $post_id)) return;

        $raw = isset($_POST['b2b_resources']) ? $_POST['b2b_resources'] : array();
        if (!is_array($raw)) $raw = array();

        // Sort order from hidden field
        $order = isset($_POST['b2b_resources_order']) ? sanitize_text_field(wp_unslash($_POST['b2b_resources_order'])) : '';
        $order_ids = array_filter(explode(',', $order));

        $clean = array();
        foreach ($raw as $idx => $item) {
            $title = sanitize_text_field(wp_unslash($item['title'] ?? ''));
            if (empty($title)) continue;

            $res = array(
                'title'        => $title,
                'description'  => sanitize_textarea_field(wp_unslash($item['description'] ?? '')),
                'file_type'    => sanitize_key($item['file_type'] ?? 'pdf'),
                'file_id'      => intval($item['file_id'] ?? 0),
                'external_url' => esc_url_raw(wp_unslash($item['external_url'] ?? '')),
                'thumb_id'     => intval($item['thumb_id'] ?? 0),
                'sort_order'   => intval($item['sort_order'] ?? 0),
                'active'       => intval($item['active'] ?? 1),
            );

            // Clean unused file/thumb when external_url is set
            if (!empty($res['external_url']) && !empty($res['file_id'])) {
                $res['file_id'] = 0;
            }

            $clean[] = $res;
        }

        // Reorder by drag-drop order
        if (!empty($order_ids)) {
            $indexed = array();
            foreach ($clean as $c) {
                $key = md5($c['title'] . $c['file_type'] . $c['external_url']);
                $indexed[$key] = $c;
            }
            $reordered = array();
            foreach ($order_ids as $oid) {
                $oid = intval($oid);
                if (isset($clean[$oid])) {
                    $clean[$oid]['sort_order'] = count($reordered);
                    $reordered[] = $clean[$oid];
                }
            }
            if (!empty($reordered)) $clean = $reordered;
        }

        // Ensure sequential sort_order
        foreach ($clean as $i => &$c) {
            $c['sort_order'] = $i;
        }
        unset($c);

        update_post_meta($post_id, '_b2b_product_resources', $clean);
    }
}
