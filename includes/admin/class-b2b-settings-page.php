<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Settings_Page {

    public static function render() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');

        if (isset($_POST['b2b_save_general']) && check_admin_referer('b2b_settings_general')) {
            B2B_Procurement_Settings::register_group('general', array('option_prefix' => 'b2b_general_'));
            B2B_Procurement_Settings::update('company_name', sanitize_text_field(wp_unslash($_POST['company_name'] ?? '')), 'general');
            B2B_Procurement_Settings::update('admin_email', sanitize_email(wp_unslash($_POST['admin_email'] ?? '')), 'general');
            B2B_Procurement_Settings::update('currency', sanitize_text_field(wp_unslash($_POST['currency'] ?? 'IRR')), 'general');
            B2B_Procurement_Settings::update('enable_notifications', isset($_POST['enable_notifications']) ? '1' : '0', 'general');
            B2B_Procurement_Notices::add('تنظیمات با موفقیت ذخیره شد.', 'success');
        }

        B2B_Procurement_Settings::register_group('general', array('option_prefix' => 'b2b_general_'));
        $company = B2B_Procurement_Settings::get('company_name', '', 'general');
        $email = B2B_Procurement_Settings::get('admin_email', '', 'general');
        $currency = B2B_Procurement_Settings::get('currency', 'IRR', 'general');
        $notif = B2B_Procurement_Settings::get('enable_notifications', '1', 'general');

        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header">
            <div>
                <h1 class="b2b-workspace-title">تنظیمات</h1>
                <p class="b2b-workspace-subtitle">مدیریت تنظیمات سامانه</p>
            </div>
        </div>

        <nav class="b2b-tabs-nav"><ul>
            <li class="b2b-tab-active"><a href="#">&#9881; عمومی</a></li>
            <li><span style="color:var(--b2b-text-tertiary);">نمایش <span class="b2b-badge b2b-badge-default">به‌زودی</span></span></li>
            <li><span style="color:var(--b2b-text-tertiary);">پیشرفته <span class="b2b-badge b2b-badge-default">به‌زودی</span></span></li>
        </ul></nav>

        <div class="b2b-card">
            <div class="b2b-card-header"><h2 class="b2b-card-title">تنظیمات عمومی</h2></div>
            <div class="b2b-card-body">
                <form method="post" class="b2b-form">
                    <?php wp_nonce_field('b2b_settings_general'); ?>
                    <?php B2B_Procurement_Settings::render_field(array('type' => 'text', 'id' => 'company_name', 'name' => 'company_name', 'label' => 'نام شرکت', 'placeholder' => 'نام شرکت', 'description' => 'نام شرکت یا سازمان خود را وارد کنید.'), $company); ?>
                    <?php B2B_Procurement_Settings::render_field(array('type' => 'email', 'id' => 'admin_email', 'name' => 'admin_email', 'label' => 'ایمیل مدیر', 'placeholder' => 'admin@example.com', 'description' => 'ایمیل مدیر سامانه برای ارسال اعلان‌ها.'), $email); ?>
                    <?php B2B_Procurement_Settings::render_field(array('type' => 'select', 'id' => 'currency', 'name' => 'currency', 'label' => 'واحد پول', 'options' => array('IRR' => 'ریال ایران', 'IRT' => 'تومان ایران', 'USD' => 'دلار آمریکا', 'EUR' => 'یورو')), $currency); ?>
                    <?php B2B_Procurement_Settings::render_field(array('type' => 'switch', 'id' => 'enable_notifications', 'name' => 'enable_notifications', 'label' => 'فعال‌سازی اعلان‌ها', 'description' => 'اعلان‌های سیستم را فعال یا غیرفعال کنید.'), $notif); ?>
                    <div class="b2b-form-actions">
                        <button type="submit" name="b2b_save_general" class="b2b-btn b2b-btn-primary">ذخیره تنظیمات</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
