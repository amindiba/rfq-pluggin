<?php
/**
 * Modal - Reusable modal window system.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Modal
 *
 * Provides reusable modal windows for the admin area.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Modal {

    /**
     * Render a modal window.
     *
     * @param string $id Modal ID.
     * @param string $title Modal title.
     * @param string $body Modal body content.
     * @param string $footer Modal footer content.
     * @param array $args Optional arguments.
     */
    public static function render($id, $title, $body = '', $footer = '', $args = array()) {
        $defaults = array(
            'size' => 'medium',
            'close_on_overlay' => true,
            'close_on_esc' => true,
            'show_close' => true,
        );
        $args = wp_parse_args($args, $defaults);

        $size_class = 'b2b-modal-' . $args['size'];

        echo '<div id="' . esc_attr($id) . '" class="b2b-modal ' . $size_class . '" data-close-overlay="' . ($args['close_on_overlay'] ? 'true' : 'false') . '" data-close-esc="' . ($args['close_on_esc'] ? 'true' : 'false') . '">';
        echo '<div class="b2b-modal-overlay"></div>';
        echo '<div class="b2b-modal-dialog">';
        echo '<div class="b2b-modal-header">';
        echo '<h3 class="b2b-modal-title">' . esc_html($title) . '</h3>';
        if ($args['show_close']) {
            echo '<button type="button" class="b2b-modal-close" aria-label="بستن">&times;</button>';
        }
        echo '</div>';
        echo '<div class="b2b-modal-body">' . $body . '</div>';
        if ($footer) {
            echo '<div class="b2b-modal-footer">' . $footer . '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render a simple confirmation modal.
     *
     * @param string $id Modal ID.
     * @param string $title Modal title.
     * @param string $message Confirmation message.
     * @param string $confirm_url URL or action for confirm button.
     * @param string $confirm_label Confirm button label.
     */
    public static function confirm($id, $title, $message, $confirm_url = '#', $confirm_label = 'تأیید') {
        $footer = '';
        $footer .= '<button type="button" class="b2b-btn b2b-btn-secondary b2b-modal-cancel">انصراف</button>';
        $footer .= '<a href="' . esc_url($confirm_url) . '" class="b2b-btn b2b-btn-primary b2b-modal-confirm">' . esc_html($confirm_label) . '</a>';

        $body = '<p class="b2b-modal-message">' . wp_kses_post($message) . '</p>';

        self::render($id, $title, $body, $footer);
    }

    /**
     * Render an AJAX form modal.
     *
     * @param string $id Modal ID.
     * @param string $title Modal title.
     * @param string $form_content Form HTML content.
     * @param string $ajax_action AJAX action name.
     */
    public static function ajax_form($id, $title, $form_content, $ajax_action) {
        $footer = '';
        $footer .= '<button type="button" class="b2b-btn b2b-btn-secondary b2b-modal-cancel">انصراف</button>';
        $footer .= '<button type="button" class="b2b-btn b2b-btn-primary b2b-modal-submit" data-action="' . esc_attr($ajax_action) . '">';
        $footer .= '<span class="b2b-btn-text">ذخیره</span>';
        $footer .= '<span class="b2b-btn-loading" style="display:none;"><span class="b2b-spinner"></span></span>';
        $footer .= '</button>';

        $body = '<form id="' . esc_attr($id) . '-form" class="b2b-modal-form">';
        $body .= wp_nonce_field($ajax_action, '_b2b_nonce', true, false);
        $body .= $form_content;
        $body .= '</form>';

        self::render($id, $title, $body, $footer, array('size' => 'large'));
    }

    /**
     * Render loading modal.
     *
     * @param string $message Loading message.
     */
    public static function loading($message = 'در حال پردازش...') {
        echo '<div id="b2b-loading-modal" class="b2b-modal b2b-modal-small">';
        echo '<div class="b2b-modal-overlay"></div>';
        echo '<div class="b2b-modal-dialog">';
        echo '<div class="b2b-modal-body b2b-modal-loading">';
        echo '<div class="b2b-spinner-large"></div>';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Open a modal via JavaScript.
     *
     * @param string $modal_id Modal ID to open.
     */
    public static function open_script($modal_id) {
        echo '<script>document.getElementById(\'' . esc_js($modal_id) . '\').style.display=\'flex\';</script>';
    }
}
