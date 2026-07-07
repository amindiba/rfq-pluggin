<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Tools_Page {
    public static function render() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        B2B_Procurement_Admin::shell_start();
        ?>
        <div class="b2b-workspace-header"><div><h1 class="b2b-workspace-title">ابزارها</h1><p class="b2b-workspace-subtitle">ابزارهای مدیریتی سامانه</p></div></div>
        <div class="b2b-card"><div class="b2b-card-body">
            <div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128295;</div><p class="b2b-empty-state-text">هنوز ابزاری در دسترس نیست.</p></div>
        </div></div>
        <?php
        B2B_Procurement_Admin::shell_end();
    }
}
