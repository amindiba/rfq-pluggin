<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Help_Page {
    public static function render() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header"><div><h1 class="b2b-workspace-title">راهنما</h1><p class="b2b-workspace-subtitle">اطلاعات و راهنمای استفاده از سامانه</p></div></div>

        <div class="b2b-card-grid b2b-card-grid-2">
            <div class="b2b-card"><div class="b2b-card-header"><h2 class="b2b-card-title">&#128196; اطلاعات پلاگین</h2></div><div class="b2b-card-body"><table class="b2b-info-table">
                <tr><td class="b2b-info-label">نام</td><td>سیستم مدیریت خرید B2B</td></tr>
                <tr><td class="b2b-info-label">ورژن</td><td><?php echo B2B_PROCUREMENT_VERSION; ?></td></tr>
                <tr><td class="b2b-info-label">توسعه‌دهنده</td><td>امین دیبا</td></tr>
                <tr><td class="b2b-info-label">وب‌سایت</td><td><a href="https://www.a4site.com" target="_blank">a4site.com</a></td></tr>
                <tr><td class="b2b-info-label">تلفن</td><td>09102109671</td></tr>
            </table></div></div>

            <div class="b2b-card"><div class="b2b-card-header"><h2 class="b2b-card-title">&#128222; پشتیبانی</h2></div><div class="b2b-card-body"><div class="b2b-support-methods">
                <div class="b2b-support-item"><strong>&#127760; وب‌سایت:</strong> <a href="https://www.a4site.com" target="_blank">a4site.com</a></div>
                <div class="b2b-support-item"><strong>&#128241; واتساپ:</strong> <a href="https://wa.me/989102109671" target="_blank">09102109671</a></div>
                <div class="b2b-support-item"><strong>&#128222; تلفن:</strong> <a href="tel:09102109671">09102109671</a></div>
            </div></div></div>
        </div>

        <div class="b2b-card"><div class="b2b-card-header"><h2 class="b2b-card-title">&#128218; مستندات</h2></div><div class="b2b-card-body"><div class="b2b-help-section">
            <h4>معرفی سامانه</h4><p>سیستم مدیریت خرید B2B یک پلتفرم جامع برای مدیریت فرآیندهای خرید و تامین کالا در کسب‌وکارها است.</p>
            <h4>نحوه شروع</h4><ol><li>تنظیمات پایه را از بخش تنظیمات انجام دهید</li><li>تامین‌کنندگان خود را ثبت کنید</li><li>اولین درخواست خرید را ایجاد کنید</li></ol>
        </div></div></div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
