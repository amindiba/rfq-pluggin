<?php
namespace B2B\ProductDefinitions\Admin;

use B2B\ProductDefinitions\Database\Definition_DB;

defined('ABSPATH') || exit;

class Definition_Product_Meta {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'register'));
        add_action('save_post_product', array($this, 'save'), 10, 2);
    }

    public function register() {
        add_meta_box(
            'b2b_product_definition',
            'تعریف محصول (Product Definition)',
            array($this, 'render'),
            'product',
            'side',
            'default'
        );
    }

    public function render($post) {
        wp_nonce_field('b2b_pd_product_save', 'b2b_pd_product_nonce');

        $current = get_post_meta($post->ID, '_b2b_product_definition_id', true);
        $definitions = Definition_DB::get_active_all();
        ?>
        <p class="description" style="margin-bottom:10px;">یک تعریف الگو برای این محصول انتخاب کنید. این الگو مشخصات ثابت محصول را تعیین می‌کند.</p>
        <select name="b2b_product_definition_id" class="b2b-select" style="width:100%;">
            <option value="">— بدون تعریف —</option>
            <?php foreach ($definitions as $def) : ?>
                <option value="<?php echo $def->id; ?>" <?php selected($current, $def->id); ?>>
                    <?php echo esc_html($def->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($current && $def_obj = Definition_DB::get($current)) : ?>
            <p class="description" style="margin-top:8px;color:#7B2CBF;">
                &#128203; تعریف فعلی: <strong><?php echo esc_html($def_obj->name); ?></strong>
                <?php if ($def_obj->description) echo '<br>' . esc_html(mb_substr($def_obj->description, 0, 100)); ?>
            </p>
        <?php endif; ?>
        <?php
    }

    public function save($post_id, $post) {
        if (!isset($_POST['b2b_pd_product_nonce']) || !wp_verify_nonce($_POST['b2b_pd_product_nonce'], 'b2b_pd_product_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_product', $post_id)) return;

        $def_id = intval($_POST['b2b_product_definition_id'] ?? 0);
        if ($def_id) {
            $def = Definition_DB::get($def_id);
            if ($def) {
                update_post_meta($post_id, '_b2b_product_definition_id', $def_id);
                update_post_meta($post_id, '_b2b_product_definition_slug', $def->slug);
            }
        } else {
            delete_post_meta($post_id, '_b2b_product_definition_id');
            delete_post_meta($post_id, '_b2b_product_definition_slug');
        }
    }
}
