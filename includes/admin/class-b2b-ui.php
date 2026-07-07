<?php
/**
 * UI Components - Reusable admin UI elements.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_UI
 *
 * Provides reusable HTML components for the admin area.
 *
 * @since 1.0.0
 */
class B2B_Procurement_UI {

    /**
     * Render page header.
     *
     * @param string $title Page title.
     * @param string $subtitle Optional subtitle.
     * @param string $action_html Optional action buttons HTML.
     */
    public static function page_header($title, $subtitle = '', $action_html = '') {
        echo '<div class="b2b-page-header">';
        echo '<div class="b2b-page-header-left">';
        echo '<h1 class="b2b-page-title">' . esc_html($title) . '</h1>';
        if ($subtitle) {
            echo '<p class="b2b-page-subtitle">' . esc_html($subtitle) . '</p>';
        }
        echo '</div>';
        if ($action_html) {
            echo '<div class="b2b-page-header-right">' . $action_html . '</div>';
        }
        echo '</div>';
    }

    /**
     * Render page container.
     *
     * @param mixed $content Page content (string or callable).
     */
    public static function page_container($content) {
        echo '<div class="b2b-admin-page">';
        if (is_callable($content)) {
            call_user_func($content);
        } else {
            echo $content;
        }
        echo '</div>';
    }

    /**
     * Render a card.
     *
     * @param string $title Card title.
     * @param string $content Card content.
     * @param array $args Optional arguments.
     */
    public static function card($title, $content, $args = array()) {
        $defaults = array(
            'class' => '',
            'id' => '',
            'header_actions' => '',
        );
        $args = wp_parse_args($args, $defaults);

        $class = 'b2b-card';
        if ($args['class']) {
            $class .= ' ' . esc_attr($args['class']);
        }

        echo '<div class="' . $class . '"' . ($args['id'] ? ' id="' . esc_attr($args['id']) . '"' : '') . '>';
        echo '<div class="b2b-card-header">';
        echo '<h2 class="b2b-card-title">' . esc_html($title) . '</h2>';
        if ($args['header_actions']) {
            echo '<div class="b2b-card-actions">' . $args['header_actions'] . '</div>';
        }
        echo '</div>';
        echo '<div class="b2b-card-body">' . $content . '</div>';
        echo '</div>';
    }

    /**
     * Render info box.
     *
     * @param string $message Info message.
     * @param string $type Info type (info, success, warning, error).
     * @param bool $dismissible Whether the notice is dismissible.
     */
    public static function info_box($message, $type = 'info', $dismissible = false) {
        $class = 'b2b-info-box b2b-info-' . esc_attr($type);
        if ($dismissible) {
            $class .= ' is-dismissible';
        }
        echo '<div class="' . $class . '">';
        echo '<p>' . wp_kses_post($message) . '</p>';
        echo '</div>';
    }

    /**
     * Render a table.
     *
     * @param array $columns Table columns.
     * @param array $rows Table rows.
     * @param array $args Optional arguments.
     */
    public static function table($columns, $rows, $args = array()) {
        $defaults = array(
            'class' => 'b2b-table',
            'id' => '',
            'empty_message' => 'داده‌ای موجود نیست.',
        );
        $args = wp_parse_args($args, $defaults);

        echo '<table class="' . esc_attr($args['class']) . '"' . ($args['id'] ? ' id="' . esc_attr($args['id']) . '"' : '') . '>';

        // Header.
        echo '<thead><tr>';
        foreach ($columns as $key => $col) {
            $th_class = isset($col['class']) ? ' class="' . esc_attr($col['class']) . '"' : '';
            $th_style = isset($col['style']) ? ' style="' . esc_attr($col['style']) . '"' : '';
            echo '<th' . $th_class . $th_style . '>' . esc_html($col['label']) . '</th>';
        }
        echo '</tr></thead>';

        // Body.
        echo '<tbody>';
        if (empty($rows)) {
            echo '<tr><td colspan="' . count($columns) . '" class="b2b-table-empty">';
            echo esc_html($args['empty_message']);
            echo '</td></tr>';
        } else {
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($columns as $key => $col) {
                    $td_class = isset($col['class']) ? ' class="' . esc_attr($col['class']) . '"' : '';
                    $value = isset($row[$key]) ? $row[$key] : '';
                    echo '<td' . $td_class . '>' . wp_kses_post($value) . '</td>';
                }
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
    }

    /**
     * Render tabs.
     *
     * @param array $tabs Tab definitions.
     * @param string $current Current active tab.
     */
    public static function tabs($tabs, $current = '') {
        if (empty($tabs)) {
            return;
        }

        if (!$current) {
            $current = array_key_first($tabs);
        }

        echo '<div class="b2b-tabs">';
        echo '<nav class="b2b-tab-nav">';
        echo '<ul>';
        foreach ($tabs as $key => $tab) {
            $active = ($key === $current) ? ' class="b2b-tab-active"' : '';
            $url = isset($tab['url']) ? $tab['url'] : '#tab-' . $key;
            echo '<li' . $active . '>';
            echo '<a href="' . esc_url($url) . '">';
            if (isset($tab['icon'])) {
                echo '<span class="b2b-tab-icon">' . $tab['icon'] . '</span> ';
            }
            echo esc_html($tab['label']);
            if (isset($tab['count'])) {
                echo ' <span class="b2b-tab-count">' . intval($tab['count']) . '</span>';
            }
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</nav>';
        echo '<div class="b2b-tab-content">';
    }

    /**
     * Close tabs container.
     */
    public static function tabs_end() {
        echo '</div></div>';
    }

    /**
     * Render button.
     *
     * @param string $label Button label.
     * @param array $args Button arguments.
     */
    public static function button($label, $args = array()) {
        $defaults = array(
            'tag' => 'a',
            'href' => '#',
            'class' => 'b2b-btn',
            'variant' => '',
            'size' => '',
            'icon' => '',
            'icon_position' => 'before',
            'id' => '',
            'onclick' => '',
            'type' => '',
            'disabled' => false,
        );
        $args = wp_parse_args($args, $defaults);

        $class = $args['class'];
        if ($args['variant']) {
            $class .= ' b2b-btn-' . $args['variant'];
        }
        if ($args['size']) {
            $class .= ' b2b-btn-' . $args['size'];
        }

        $tag = ($args['tag'] === 'button') ? 'button' : 'a';
        $attrs = '';
        $attrs .= ' class="' . esc_attr($class) . '"';
        if ($tag === 'a') {
            $attrs .= ' href="' . esc_url($args['href']) . '"';
        }
        if ($args['id']) {
            $attrs .= ' id="' . esc_attr($args['id']) . '"';
        }
        if ($args['onclick']) {
            $attrs .= ' onclick="' . esc_attr($args['onclick']) . '"';
        }
        if ($tag === 'button' && $args['type']) {
            $attrs .= ' type="' . esc_attr($args['type']) . '"';
        }
        if ($args['disabled']) {
            $attrs .= ' disabled';
        }

        $icon_html = $args['icon'] ? '<span class="b2b-btn-icon">' . $args['icon'] . '</span> ' : '';
        $label_html = '<span class="b2b-btn-text">' . esc_html($label) . '</span>';

        $content = ($args['icon_position'] === 'before') ? $icon_html . $label_html : $label_html . $icon_html;

        echo '<' . $tag . $attrs . '>' . $content . '</' . $tag . '>';
    }

    /**
     * Render badge.
     *
     * @param string $label Badge label.
     * @param string $variant Badge variant.
     */
    public static function badge($label, $variant = 'default') {
        echo '<span class="b2b-badge b2b-badge-' . esc_attr($variant) . '">' . esc_html($label) . '</span>';
    }

    /**
     * Render status label.
     *
     * @param string $label Status label.
     * @param string $status Status type.
     */
    public static function status_label($label, $status = 'active') {
        echo '<span class="b2b-status b2b-status-' . esc_attr($status) . '">' . esc_html($label) . '</span>';
    }

    /**
     * Render empty state.
     *
     * @param string $message Empty state message.
     * @param string $icon Optional icon.
     * @param string $action_html Optional action HTML.
     */
    public static function empty_state($message, $icon = '&#128194;', $action_html = '') {
        echo '<div class="b2b-empty-state">';
        echo '<div class="b2b-empty-state-icon">' . $icon . '</div>';
        echo '<p class="b2b-empty-state-text">' . esc_html($message) . '</p>';
        if ($action_html) {
            echo '<div class="b2b-empty-state-action">' . $action_html . '</div>';
        }
        echo '</div>';
    }

    /**
     * Render loading overlay.
     *
     * @param string $message Loading message.
     */
    public static function loading_overlay($message = 'در حال بارگذاری...') {
        echo '<div class="b2b-loading-overlay">';
        echo '<div class="b2b-loading-spinner"></div>';
        echo '<p class="b2b-loading-text">' . esc_html($message) . '</p>';
        echo '</div>';
    }

    /**
     * Render confirmation dialog markup.
     *
     * @param string $id Dialog ID.
     * @param string $title Dialog title.
     * @param string $message Dialog message.
     * @param string $confirm_label Confirm button label.
     * @param string $cancel_label Cancel button label.
     */
    public static function confirmation_dialog($id, $title, $message, $confirm_label = 'تأیید', $cancel_label = 'انصراف') {
        echo '<div id="' . esc_attr($id) . '" class="b2b-modal" style="display:none;">';
        echo '<div class="b2b-modal-overlay"></div>';
        echo '<div class="b2b-modal-content">';
        echo '<div class="b2b-modal-header">';
        echo '<h3 class="b2b-modal-title">' . esc_html($title) . '</h3>';
        echo '<button class="b2b-modal-close" type="button">&times;</button>';
        echo '</div>';
        echo '<div class="b2b-modal-body">';
        echo '<p>' . wp_kses_post($message) . '</p>';
        echo '</div>';
        echo '<div class="b2b-modal-footer">';
        self::button($cancel_label, array('tag' => 'button', 'class' => 'b2b-btn b2b-btn-secondary b2b-modal-cancel', 'type' => 'button'));
        self::button($confirm_label, array('tag' => 'button', 'class' => 'b2b-btn b2b-btn-primary b2b-modal-confirm', 'type' => 'button'));
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render success message.
     *
     * @param string $message Success message.
     */
    public static function success_message($message) {
        self::info_box($message, 'success', true);
    }

    /**
     * Render error message.
     *
     * @param string $message Error message.
     */
    public static function error_message($message) {
        self::info_box($message, 'error', false);
    }

    /**
     * Render warning message.
     *
     * @param string $message Warning message.
     */
    public static function warning_message($message) {
        self::info_box($message, 'warning', true);
    }

    /**
     * Render search box.
     *
     * @param string $id Search input ID.
     * @param string $placeholder Placeholder text.
     */
    public static function search_box($id = 'b2b-search', $placeholder = 'جستجو...') {
        echo '<div class="b2b-search-box">';
        echo '<input type="search" id="' . esc_attr($id) . '" class="b2b-search-input" placeholder="' . esc_attr($placeholder) . '" />';
        echo '</div>';
    }

    /**
     * Render toolbar.
     *
     * @param string $left_content Left side content.
     * @param string $right_content Right side content.
     */
    public static function toolbar($left_content = '', $right_content = '') {
        echo '<div class="b2b-toolbar">';
        echo '<div class="b2b-toolbar-left">' . $left_content . '</div>';
        echo '<div class="b2b-toolbar-right">' . $right_content . '</div>';
        echo '</div>';
    }

    /**
     * Render section header.
     *
     * @param string $title Section title.
     * @param string $description Optional description.
     */
    public static function section_header($title, $description = '') {
        echo '<div class="b2b-section-header">';
        echo '<h2 class="b2b-section-title">' . esc_html($title) . '</h2>';
        if ($description) {
            echo '<p class="b2b-section-desc">' . esc_html($description) . '</p>';
        }
        echo '</div>';
    }
}
