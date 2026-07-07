<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Admin {

    private static $current_page = '';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menus'), 99);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('admin_body_class', array(__CLASS__, 'add_body_class'));
        add_action('admin_head', array(__CLASS__, 'hide_wp_notices'));
    }

    public static function hide_wp_notices() {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'b2b-') !== false) {
            echo '<style>#message, #update-nag, .updated, .error:not(.b2b-error), .notice:not(.b2b-notice), .is-dismissible { display: none !important; }</style>';
        }
    }

    public static function add_body_class($classes) {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'b2b-') !== false) {
            $classes .= ' b2b-app-active';
        }
        return $classes;
    }

    public static function redirect_old_dashboard() {
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        // Redirect old product pages to WooCommerce native
        if ($page === 'b2b-products' || $page === 'b2b-product-add') {
            wp_safe_redirect(admin_url('edit.php?post_type=product'));
            exit;
        }
        if ($page === 'b2b-categories' || $page === 'b2b-category-add') {
            wp_safe_redirect(admin_url('edit-tags.php?taxonomy=product_cat'));
            exit;
        }
    }

    public static function register_menus() {
        // Main menu - visible
        add_menu_page(
            'سیستم مدیریت خرید',
            'سیستم مدیریت خرید',
            'manage_woocommerce',
            'b2b-procurement',
            array('B2B_Procurement_Dashboard', 'render'),
            'dashicons-businessman',
            56
        );

        // RFQ menus
        add_submenu_page(
            'b2b-procurement',
            'درخواست‌های خرید',
            'درخواست‌های خرید',
            'manage_woocommerce',
            'b2b-rfqs',
            array('B2B_Rfq_Admin', 'render_list')
        );
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-rfq-add', array('B2B_Rfq_Admin', 'render_form'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-rfq-edit', array('B2B_Rfq_Admin', 'render_form'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-rfq-detail', array('B2B_Rfq_Admin', 'render_detail'));

        // Quotation menus
        add_submenu_page(
            'b2b-procurement',
            'پیشنهادات قیمت',
            'پیشنهادات قیمت',
            'manage_woocommerce',
            'b2b-quotations',
            array('B2B_Quotation_Admin', 'render_list')
        );
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-quotation-add', array('B2B_Quotation_Admin', 'render_form'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-quotation-detail', array('B2B_Quotation_Admin', 'render_detail'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-quotation-compare', array('B2B_Quotation_Admin', 'render_comparison'));

        // Purchase Order menus
        add_submenu_page(
            'b2b-procurement',
            'سفارشات خرید',
            'سفارشات خرید',
            'manage_woocommerce',
            'b2b-pos',
            array('B2B_PO_Admin', 'render_list')
        );
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-po-detail', array('B2B_PO_Admin', 'render_detail'));

        // Contract menus
        add_submenu_page(
            'b2b-procurement',
            'قراردادها',
            'قراردادها',
            'manage_woocommerce',
            'b2b-contracts',
            array('B2B_Contract_Admin', 'render_list')
        );
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-contract-add', array('B2B_Contract_Admin', 'render_form'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-contract-edit', array('B2B_Contract_Admin', 'render_form'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-contract-detail', array('B2B_Contract_Admin', 'render_detail'));

        // Notification menus
        add_submenu_page(
            'b2b-procurement',
            'اعلان‌ها',
            'اعلان‌ها',
            'manage_woocommerce',
            'b2b-notifications',
            array('B2B_Notification_Admin', 'render_list')
        );
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-notification-detail', array('B2B_Notification_Admin', 'render_detail'));

        // Redirect old dashboard to management dashboard
        add_action('admin_init', array(__CLASS__, 'redirect_old_dashboard'));

        // Dashboard & Reports
        add_submenu_page(
            'b2b-procurement',
            'داشبورد مدیریت',
            'داشبورد مدیریت',
            'manage_woocommerce',
            'b2b-dashboard',
            array('B2B_Dashboard_Report', 'render_dashboard')
        );
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-report-rfqs', array('B2B_Dashboard_Report', 'render_rfq_report'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-report-pos', array('B2B_Dashboard_Report', 'render_po_report'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-report-contracts', array('B2B_Dashboard_Report', 'render_contract_report'));

        // Supplier menus
        add_submenu_page(
            'b2b-procurement',
            'تامین‌کنندگان',
            'تامین‌کنندگان',
            'manage_woocommerce',
            'b2b-suppliers',
            array('B2B_Supplier_Admin', 'render_list')
        );
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-supplier-add', array('B2B_Supplier_Admin', 'render_form'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-supplier-edit', array('B2B_Supplier_Admin', 'render_form'));
        add_submenu_page(null, '', '', 'manage_woocommerce', 'b2b-supplier-detail', array('B2B_Supplier_Admin', 'render_detail'));

        // Hidden submenus - required for pages to work
        $hidden = array(
            array('b2b-master-data', array('B2B_Procurement_Master_Data', 'render_dashboard')),
            array('b2b-measurement-units', array('B2B_Procurement_Master_Data', 'render_units')),
            array('b2b-geography', array('B2B_Procurement_Geography', 'render_dashboard')),
            array('b2b-provinces', array('B2B_Procurement_Geography', 'render_provinces')),
            array('b2b-cities', array('B2B_Procurement_Geography', 'render_cities')),
            array('b2b-settings', array('B2B_Procurement_Settings_Page', 'render')),
            array('b2b-tools', array('B2B_Procurement_Tools_Page', 'render')),
            array('b2b-logs', array('B2B_Procurement_Logs_Page', 'render')),
            array('b2b-system-status', array('B2B_Procurement_System_Status_Page', 'render')),
            array('b2b-help', array('B2B_Procurement_Help_Page', 'render')),
        );

        foreach ($hidden as $item) {
            add_submenu_page('b2b-procurement', '', '', 'manage_woocommerce', $item[0], $item[1]);
        }
    }

    public static function enqueue_assets($hook) {
        if (strpos($hook, 'b2b-') === false && strpos($hook, 'toplevel_page_b2b') === false) {
            return;
        }

        // Enterprise UI Core - Bundled
        wp_enqueue_style('b2b-ui-bundle', B2B_PROCUREMENT_PLUGIN_URL . 'assets/css/b2b-ui-bundle.css', array(), B2B_PROCUREMENT_VERSION);
        wp_enqueue_style('b2b-admin', B2B_PROCUREMENT_PLUGIN_URL . 'assets/css/admin.css', array('b2b-ui-bundle'), B2B_PROCUREMENT_VERSION);

        // Scripts - Bundled
        wp_enqueue_script('b2b-ui-bundle', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/b2b-ui-bundle.js', array('jquery'), B2B_PROCUREMENT_VERSION, true);
        wp_enqueue_script('b2b-admin', B2B_PROCUREMENT_PLUGIN_URL . 'assets/js/admin.js', array('b2b-ui-bundle'), B2B_PROCUREMENT_VERSION, true);

        wp_localize_script('b2b-admin', 'b2bProcurement', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(B2B_Procurement_Security::NONCE_ACTION),
            'restUrl' => rest_url('b2b-procurement/v1/'),
            'version' => B2B_PROCUREMENT_VERSION,
        ));
    }

    // ==================== SHELL HELPERS ====================

    public static function get_current_page() {
        return isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : 'b2b-procurement';
    }

    public static function get_page_title() {
        $titles = array(
            'b2b-procurement' => 'داشبورد',
            'b2b-dashboard' => 'داشبورد مدیریت',
            'b2b-report-rfqs' => 'گزارش درخواست‌ها',
            'b2b-report-pos' => 'گزارش سفارشات',
            'b2b-report-contracts' => 'گزارش قراردادها',
            'b2b-settings' => 'تنظیمات',
            'b2b-tools' => 'ابزارها',
            'b2b-logs' => 'لاگ‌ها',
            'b2b-system-status' => 'وضعیت سیستم',
            'b2b-help' => 'راهنما',
        );
        $page = self::get_current_page();
        return isset($titles[$page]) ? $titles[$page] : 'سیستم مدیریت خرید';
    }

    public static function get_nav_items() {
        $current = self::get_current_page();
        return array(
            array('group' => 'اصلی', 'items' => array(
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>', 'label' => 'داشبورد مدیریت', 'url' => 'admin.php?page=b2b-dashboard', 'slug' => 'b2b-dashboard'),
            )),
            array('group' => 'اطلاعات پایه', 'items' => array(
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>', 'label' => 'واحدهای اندازه‌گیری', 'url' => 'admin.php?page=b2b-measurement-units', 'slug' => 'b2b-measurement-units'),
                array('icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>', 'label' => 'جغرافیای ایران', 'url' => 'admin.php?page=b2b-geography', 'slug' => 'b2b-geography'),
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>', 'label' => 'استان‌ها', 'url' => 'admin.php?page=b2b-provinces', 'slug' => 'b2b-provinces'),
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>', 'label' => 'شهرها', 'url' => 'admin.php?page=b2b-cities', 'slug' => 'b2b-cities'),
            )),
            array('group' => 'کاتالوگ محصولات', 'items' => array(
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>', 'label' => 'محصولات', 'url' => 'edit.php?post_type=product', 'slug' => 'edit.php'),
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>', 'label' => 'دسته‌بندی‌ها', 'url' => 'edit-tags.php?taxonomy=product_cat', 'slug' => 'edit-tags.php'),
            )),
            array('group' => 'درخواست خرید', 'items' => array(
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>', 'label' => 'درخواست‌های خرید', 'url' => 'admin.php?page=b2b-rfqs', 'slug' => 'b2b-rfqs'),
                array('icon' => '<svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>', 'label' => 'پیشنهادات قیمت', 'url' => 'admin.php?page=b2b-quotations', 'slug' => 'b2b-quotations'),
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>', 'label' => 'سفارشات خرید', 'url' => 'admin.php?page=b2b-pos', 'slug' => 'b2b-pos'),
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>', 'label' => 'قراردادها', 'url' => 'admin.php?page=b2b-contracts', 'slug' => 'b2b-contracts'),
            )),
            array('group' => 'گزارشات', 'items' => array(
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>', 'label' => 'گزارش درخواست‌ها', 'url' => 'admin.php?page=b2b-report-rfqs', 'slug' => 'b2b-report-rfqs'),
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/></svg>', 'label' => 'گزارش سفارشات', 'url' => 'admin.php?page=b2b-report-pos', 'slug' => 'b2b-report-pos'),
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>', 'label' => 'گزارش قراردادها', 'url' => 'admin.php?page=b2b-report-contracts', 'slug' => 'b2b-report-contracts'),
            )),
            array('group' => 'سیستم', 'items' => array(
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>', 'label' => 'اعلان‌ها', 'url' => 'admin.php?page=b2b-notifications', 'slug' => 'b2b-notifications'),
            )),
            array('group' => 'تامین‌کنندگان', 'items' => array(
                array('icon' => '<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>', 'label' => 'تامین‌کنندگان', 'url' => 'admin.php?page=b2b-suppliers', 'slug' => 'b2b-suppliers'),
            )),
            array('group' => 'سیستم', 'items' => array(
                array('icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>', 'label' => 'تنظیمات', 'url' => 'admin.php?page=b2b-settings', 'slug' => 'b2b-settings'),
                array('icon' => '<svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>', 'label' => 'وضعیت سیستم', 'url' => 'admin.php?page=b2b-system-status', 'slug' => 'b2b-system-status'),
                array('icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>', 'label' => 'راهنما', 'url' => 'admin.php?page=b2b-help', 'slug' => 'b2b-help'),
            )),
        );
    }

    public static function render_shell_header() {
        $page_title = self::get_page_title();
        $user = wp_get_current_user();
        $unread = B2B_Notification_DB::get_unread_count(get_current_user_id());
        ?>
        <header class="b2b-header">
            <div class="b2b-header-right">
                <a href="<?php echo admin_url(); ?>" class="b2b-btn b2b-btn-secondary b2b-btn-sm" title="بازگشت به پنل مدیریت وردپرس">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    وردپرس
                </a>
            </div>
            <div class="b2b-header-center">
                <h1 class="b2b-header-title"><?php echo esc_html($page_title); ?></h1>
            </div>
            <div class="b2b-header-left">
                <div class="b2b-header-notif" title="اعلان‌ها">
                    <a href="<?php echo admin_url('admin.php?page=b2b-notifications'); ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:4px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <?php if ($unread > 0) : ?><span class="b2b-header-notif-badge"><?php echo number_format($unread); ?></span><?php endif; ?>
                    </a>
                </div>
                <span class="b2b-header-version">v<?php echo B2B_PROCUREMENT_VERSION; ?></span>
                <div class="b2b-header-user">
                    <div class="b2b-header-user-avatar"><?php echo mb_substr($user->display_name, 0, 1); ?></div>
                    <span class="b2b-header-user-name"><?php echo esc_html($user->display_name); ?></span>
                </div>
            </div>
        </header>
        <?php
    }

    public static function render_shell_sidebar() {
        $current = self::get_current_page();
        $nav_items = self::get_nav_items();
        $user = wp_get_current_user();
        $unread = B2B_Notification_DB::get_unread_count(get_current_user_id());
        ?>
        <aside class="b2b-sidebar">
            <div class="b2b-sidebar-header">
                <div class="b2b-sidebar-logo">B2B</div>
                <span class="b2b-sidebar-brand">سیستم مدیریت خرید</span>
            </div>

            <div class="b2b-sidebar-search">
                <div class="b2b-sidebar-search-wrap">
                    <svg class="b2b-sidebar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" class="b2b-sidebar-search-input" id="b2b-sidebar-search" placeholder="جستجو در منو..." />
                    <button type="button" class="b2b-sidebar-search-clear" id="b2b-sidebar-search-clear">&times;</button>
                </div>
            </div>

            <nav class="b2b-sidebar-nav">
                <?php foreach ($nav_items as $gIdx => $group) :
                    $slug = 'nav-group-' . $gIdx;
                    $first_icon = $group['items'][0]['icon'];
                ?>
                    <div class="b2b-nav-group" data-group="<?php echo $slug; ?>">
                        <button type="button" class="b2b-nav-group-label b2b-nav-group-toggle" data-group-id="<?php echo $slug; ?>">
                            <?php echo esc_html($group['group']); ?>
                            <svg class="b2b-nav-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="b2b-nav-group-header" data-group-id="<?php echo $slug; ?>" data-group-name="<?php echo esc_attr($group['group']); ?>" title="<?php echo esc_attr($group['group']); ?>">
                            <span class="b2b-group-icon"><?php echo $first_icon; ?></span>
                            <span class="b2b-group-tooltip"><?php echo esc_html($group['group']); ?></span>
                        </div>
                        <div class="b2b-nav-group-items">
                        <?php foreach ($group['items'] as $item) :
                            $active = ($current === $item['slug']) ? ' active' : '';
                            $badge = ($item['slug'] === 'b2b-notifications' && $unread > 0) ? '<span class="b2b-nav-badge-count" id="b2b-nav-unread-badge">' . number_format($unread) . '</span>' : '';
                        ?>
                            <a href="<?php echo admin_url($item['url']); ?>" class="b2b-nav-item<?php echo $active; ?>" data-label="<?php echo esc_attr($item['label']); ?>">
                                <span class="b2b-nav-icon"><?php echo $item['icon']; ?></span>
                                <span class="b2b-nav-label"><?php echo esc_html($item['label']); ?></span>
                                <?php echo $badge; ?>
                                <span class="b2b-tooltip-text"><?php echo esc_html($item['label']); ?></span>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </nav>

            <?php
            $pipeline_stages = array(
                array('label' => 'درخواست', 'slug' => 'b2b-rfqs'),
                array('label' => 'پیشنهاد', 'slug' => 'b2b-quotations'),
                array('label' => 'سفارش', 'slug' => 'b2b-pos'),
                array('label' => 'قرارداد', 'slug' => 'b2b-contracts'),
            );
            $current_idx = -1;
            foreach ($pipeline_stages as $idx => $stage) {
                if ($stage['slug'] === $current) { $current_idx = $idx; break; }
            }
            ?>
            <div class="b2b-sidebar-progress">
                <div class="b2b-sidebar-progress-title">مسیر فرآیند خرید</div>
                <div class="b2b-sidebar-progress-steps">
                    <?php foreach ($pipeline_stages as $idx => $stage) :
                        $cls = '';
                        if ($current_idx >= 0 && $idx < $current_idx) $cls = 'done';
                        elseif ($idx === $current_idx) $cls = 'active';
                    ?>
                        <div class="b2b-sidebar-progress-step <?php echo $cls; ?>"></div>
                    <?php endforeach; ?>
                </div>
                <div class="b2b-sidebar-progress-labels">
                    <?php foreach ($pipeline_stages as $stage) : ?>
                        <span class="b2b-sidebar-progress-label"><?php echo esc_html($stage['label']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="b2b-sidebar-user">
                <div class="b2b-sidebar-user-avatar"><?php echo mb_substr($user->display_name, 0, 1); ?></div>
                <div class="b2b-sidebar-user-info">
                    <div class="b2b-sidebar-user-name"><?php echo esc_html($user->display_name); ?></div>
                    <div class="b2b-sidebar-user-role"><?php echo esc_html(implode(', ', $user->roles)); ?></div>
                </div>
            </div>

            <div class="b2b-sidebar-footer">
                <button class="b2b-sidebar-toggle" title="باز/بستن منو">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
            </div>
        </aside>
        <div class="b2b-sidebar-overlay"></div>
        <div class="b2b-nav-flyout" id="b2b-nav-flyout">
            <div class="b2b-nav-flyout-title" id="b2b-flyout-title"></div>
            <div id="b2b-flyout-items"></div>
        </div>
        <?php
    }

    public static function render_shell_footer() {
        global $wpdb;
        ?>
        <footer class="b2b-app-footer">
            <div class="b2b-app-footer-inner">
                <div class="b2b-app-footer-right">
                    <span class="b2b-app-footer-brand">B2B Procurement</span>
                    <span class="b2b-app-footer-divider">|</span>
                    <span>v<?php echo B2B_PROCUREMENT_VERSION; ?></span>
                </div>
                <div class="b2b-app-footer-center">
                    <span>WordPress <?php echo get_bloginfo('version'); ?></span>
                    <span class="b2b-app-footer-divider">|</span>
                    <span>PHP <?php echo PHP_VERSION; ?></span>
                    <span class="b2b-app-footer-divider">|</span>
                    <span>MySQL <?php echo $wpdb->get_var('SELECT VERSION()'); ?></span>
                </div>
                <div class="b2b-app-footer-left">
                    <span id="b2b-dev-trigger" style="cursor:pointer;text-decoration:underline dotted;color:var(--b2b-text-tertiary);">Designed by <strong>Amir Diba</strong></span>
                    <span class="b2b-app-footer-divider">|</span>
                    <a href="https://www.a4site.com" target="_blank" class="b2b-app-footer-link">a4site.com</a>
                </div>
            </div>
        </footer>

        <!-- Developer Info Panel -->
        <div class="b2b-w-dev-overlay" id="b2b-dev-overlay"></div>
        <div class="b2b-w-dev-panel" id="b2b-dev-panel">
            <div class="b2b-w-dev-panel-inner">
                <div class="b2b-w-dev-head">
                    <h3>&#128100; اطلاعات تیم توسعه</h3>
                    <button class="b2b-w-dev-close" id="b2b-dev-close">&times;</button>
                </div>
                <div class="b2b-w-dev-body">
                    <div class="b2b-w-dev-info">
                        <p class="b2b-w-dev-name">امین دیبا</p>
                        <p class="b2b-w-dev-company">شرکت طراحی، برنامه‌نویسی و توسعه وب آچارسایت</p>
                        <p class="b2b-w-dev-desc">طراحی، توسعه و پیاده‌سازی سیستم مدیریت خرید B2B با استفاده از بستر وردپرس و ووکامرس.</p>
                        <div class="b2b-w-dev-ver">&#128736; نسخه <?php echo esc_html(B2B_PROCUREMENT_VERSION); ?></div>
                    </div>
                    <div class="b2b-w-dev-links">
                        <a href="https://www.a4site.com" target="_blank" class="b2b-w-dev-link">
                            <span class="b2b-w-dev-link-ico web">&#127760;</span>
                            <div class="b2b-w-dev-link-text"><div class="lbl">وب‌سایت رسمی</div><div class="val">www.a4site.com</div></div>
                        </a>
                        <a href="https://wa.me/989102109671" target="_blank" class="b2b-w-dev-link">
                            <span class="b2b-w-dev-link-ico wa1">&#128172;</span>
                            <div class="b2b-w-dev-link-text"><div class="lbl">پشتیبانی فنی</div><div class="val">09102109671</div></div>
                        </a>
                        <a href="https://wa.me/989220061267" target="_blank" class="b2b-w-dev-link">
                            <span class="b2b-w-dev-link-ico wa2">&#128176;</span>
                            <div class="b2b-w-dev-link-text"><div class="lbl">فروش و بازرگانی</div><div class="val">09220061267</div></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function(){
            var trigger=document.getElementById('b2b-dev-trigger');
            var panel=document.getElementById('b2b-dev-panel');
            var overlay=document.getElementById('b2b-dev-overlay');
            var close=document.getElementById('b2b-dev-close');
            if(!trigger||!panel)return;
            function open(){panel.classList.add('open');overlay.classList.add('open')}
            function shut(){panel.classList.remove('open');overlay.classList.remove('open')}
            trigger.addEventListener('click',open);
            if(close)close.addEventListener('click',shut);
            if(overlay)overlay.addEventListener('click',shut);
        })();
        </script>
        <?php
    }

    /**
     * Render the application shell wrapper.
     * Call this at the beginning of each page.
     */
    public static function shell_start() {
        echo '<div class="b2b-app b2b-ui">';
        self::render_shell_sidebar();
        echo '<main class="b2b-main">';
        self::render_shell_header();
        echo '<div class="b2b-workspace">';
    }

    /**
     * Close the application shell wrapper.
     * Call this at the end of each page.
     */
    public static function shell_end() {
        echo '</div>'; // workspace
        self::render_shell_footer();
        echo '</main>'; // main
        echo '</div>'; // app
    }

    public static function footer() {
        // Empty - footer is in shell
    }
}
