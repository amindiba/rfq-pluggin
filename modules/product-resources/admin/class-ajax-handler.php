<?php
/**
 * Product Resources AJAX Handler
 *
 * @package B2B_Procurement
 */

namespace B2B\ProductResources\Admin;

defined('ABSPATH') || exit;

class Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_b2b_pr_upload', array($this, 'handle_upload'));
    }

    public function handle_upload() {
        check_ajax_referer('b2b_product_resources_nonce', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
        }

        if (empty($_POST['type'])) {
            wp_send_json_error(array('message' => 'نوع فایل مشخص نیست'));
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload('file', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        wp_send_json_success(array(
            'id'  => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
        ));
    }
}
