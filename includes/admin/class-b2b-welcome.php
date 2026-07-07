<?php
defined('ABSPATH') || exit;

class B2B_Procurement_Welcome {

    const OPTION_KEY = 'b2b_procurement_activation_redirect';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'maybe_redirect'));
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
    }

    public static function maybe_redirect() {
        if (!get_option(self::OPTION_KEY)) return;
        delete_option(self::OPTION_KEY);
        if (!isset($_GET['page']) || 'b2b-welcome' !== $_GET['page']) {
            wp_safe_redirect(admin_url('admin.php?page=b2b-welcome'));
            exit;
        }
    }

    public static function set_activation_flag() {
        update_option(self::OPTION_KEY, 'yes');
    }

    public static function register_menu() {
        add_submenu_page(null, 'خوش آمدید', '', 'manage_woocommerce', 'b2b-welcome', array(__CLASS__, 'render'));
    }

    public static function render() {
        B2B_Procurement_Security::require_capability('manage_woocommerce');
        ?>
        <style>
            .b2b-w{margin:0;padding:0;background:var(--b2b-bg,#FAF7FF);min-height:100vh;font-family:'Vazirmatn',Tahoma,Arial,sans-serif;direction:rtl}

            .b2b-w-hero{background:linear-gradient(135deg,#581C87 0%,#7B2CBF 50%,#9D4EDD 100%);padding:60px 20px 80px;text-align:center;position:relative;overflow:hidden}
            .b2b-w-hero::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:radial-gradient(circle,rgba(255,255,255,0.05) 1px,transparent 1px);background-size:24px 24px}
            .b2b-w-hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:100px;background:linear-gradient(to top,#FAF7FF,transparent)}
            .b2b-w-hero *{position:relative;z-index:1}

            .b2b-w-logo{width:90px;height:90px;margin:0 auto 20px;background:rgba(255,255,255,0.15);border-radius:20px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(10px);border:2px solid rgba(255,255,255,0.2)}
            .b2b-w-logo svg{width:50px;height:50px;fill:#fff}

            .b2b-w-hero h1{color:#fff;font-size:32px;font-weight:800;margin:0 0 10px;letter-spacing:-0.5px}
            .b2b-w-hero>p,.b2b-w-hero .sub{color:rgba(255,255,255,0.85);font-size:17px;margin:0;font-weight:300}

            .b2b-w-wrap{max-width:1100px;margin:-40px auto 0;padding:0 20px;position:relative;z-index:2}

            .b2b-w-card{background:#fff;border-radius:16px;padding:32px;margin-bottom:20px;box-shadow:0 2px 16px rgba(90,24,154,0.06);border:1px solid var(--b2b-border,#ECE6F8)}
            .b2b-w-card h2{color:var(--b2b-text,#1F2937);font-size:20px;font-weight:700;margin:0 0 16px;padding-bottom:12px;border-bottom:2px solid var(--b2b-border,#ECE6F8);display:flex;align-items:center;gap:10px}
            .b2b-w-card h2 .ico{width:34px;height:34px;background:linear-gradient(135deg,var(--b2b-primary,#7B2CBF),#9D4EDD);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0}

            .b2b-w-about p{color:var(--b2b-text-secondary,#6B7280);font-size:15px;line-height:2;margin:0}

            .b2b-w-grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
            .b2b-w-feat{background:var(--b2b-bg,#FAF7FF);border-radius:12px;padding:24px 16px;text-align:center;border:1px solid var(--b2b-border,#ECE6F8);transition:all 0.3s ease}
            .b2b-w-feat:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(123,44,191,0.1);border-color:var(--b2b-primary,#7B2CBF)}
            .b2b-w-feat-ico{width:50px;height:50px;margin:0 auto 14px;background:linear-gradient(135deg,#F3E8FF,#E9D5FF);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:26px}
            .b2b-w-feat h3{color:var(--b2b-text,#1F2937);font-size:14px;font-weight:600;margin:0 0 6px}
            .b2b-w-feat p{color:var(--b2b-text-secondary,#6B7280);font-size:12px;margin:0;line-height:1.7}

            .b2b-w-grid8{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
            .b2b-w-mod{display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--b2b-bg,#FAF7FF);border-radius:10px;border:1px solid var(--b2b-border,#ECE6F8);font-size:13px;color:var(--b2b-text,#1F2937);font-weight:500;transition:all 0.2s}
            .b2b-w-mod:hover{border-color:var(--b2b-primary,#7B2CBF);background:#fff}
            .b2b-w-mod-ico{width:32px;height:32px;background:linear-gradient(135deg,#F3E8FF,#E9D5FF);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}

            .b2b-w-steps{display:flex;gap:16px;margin-top:16px}
            .b2b-w-step{flex:1;background:var(--b2b-bg,#FAF7FF);border-radius:12px;padding:24px 16px;text-align:center;border:1px solid var(--b2b-border,#ECE6F8)}
            .b2b-w-step-num{width:40px;height:40px;background:linear-gradient(135deg,var(--b2b-primary,#7B2CBF),#9D4EDD);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;margin:0 auto 12px;box-shadow:0 4px 12px rgba(123,44,191,0.25)}
            .b2b-w-step h4{color:var(--b2b-text,#1F2937);font-size:14px;font-weight:600;margin:0 0 6px}
            .b2b-w-step p{color:var(--b2b-text-secondary,#6B7280);font-size:12px;margin:0;line-height:1.7}

            .b2b-w-btn-wrap{text-align:center;padding:24px 0}
            .b2b-w-btn{display:inline-flex;align-items:center;gap:10px;padding:14px 44px;background:linear-gradient(135deg,var(--b2b-primary,#7B2CBF),#9D4EDD);color:#fff;text-decoration:none;border-radius:12px;font-size:17px;font-weight:600;transition:all 0.3s;box-shadow:0 4px 20px rgba(123,44,191,0.3)}
            .b2b-w-btn:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(123,44,191,0.4);color:#fff}
            .b2b-w-footer-text{color:var(--b2b-text-tertiary,#9CA3AF);font-size:12px;margin-top:16px}

            /* Developer Snackbar Button + Panel */
            .b2b-w-dev-toggle{position:fixed;bottom:20px;left:20px;z-index:99999;background:#fff;border:2px solid var(--b2b-primary,#7B2CBF);color:var(--b2b-primary,#7B2CBF);border-radius:50px;padding:10px 20px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(123,44,191,0.15);transition:all 0.3s;font-family:'Vazirmatn',Tahoma,sans-serif}
            .b2b-w-dev-toggle:hover{background:var(--b2b-primary,#7B2CBF);color:#fff;box-shadow:0 6px 24px rgba(123,44,191,0.3)}
            .b2b-w-dev-toggle svg{width:18px;height:18px}

            .b2b-w-dev-panel{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:2px solid var(--b2b-primary,#7B2CBF);box-shadow:0 -8px 32px rgba(0,0,0,0.12);z-index:100000;transform:translateY(100%);transition:transform 0.4s cubic-bezier(0.4,0,0.2,1);padding:0;border-radius:20px 20px 0 0}
            .b2b-w-dev-panel.open{transform:translateY(0)}
            .b2b-w-dev-panel-inner{max-width:900px;margin:0 auto;padding:24px 28px}
            .b2b-w-dev-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
            .b2b-w-dev-head h3{color:var(--b2b-text,#1F2937);font-size:17px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px}
            .b2b-w-dev-close{width:32px;height:32px;border-radius:8px;border:1px solid var(--b2b-border,#ECE6F8);background:var(--b2b-bg,#FAF7FF);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--b2b-text-secondary,#6B7280);transition:all 0.2s}
            .b2b-w-dev-close:hover{background:var(--b2b-danger,#EF4444);color:#fff;border-color:var(--b2b-danger,#EF4444)}

            .b2b-w-dev-body{display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap}
            .b2b-w-dev-info{flex:1;min-width:240px}
            .b2b-w-dev-name{color:var(--b2b-text,#1F2937);font-size:18px;font-weight:700;margin:0 0 4px}
            .b2b-w-dev-company{color:var(--b2b-primary,#7B2CBF);font-size:14px;font-weight:600;margin:0 0 16px}
            .b2b-w-dev-desc{color:var(--b2b-text-secondary,#6B7280);font-size:13px;line-height:1.8;margin:0 0 12px}
            .b2b-w-dev-ver{display:inline-flex;align-items:center;gap:6px;background:var(--b2b-bg,#FAF7FF);border:1px solid var(--b2b-border,#ECE6F8);border-radius:8px;padding:6px 12px;font-size:12px;color:var(--b2b-text-secondary,#6B7280)}

            .b2b-w-dev-links{flex:1;min-width:240px;display:flex;flex-direction:column;gap:10px}
            .b2b-w-dev-link{display:flex;align-items:center;gap:12px;padding:12px 16px;background:var(--b2b-bg,#FAF7FF);border:1px solid var(--b2b-border,#ECE6F8);border-radius:10px;text-decoration:none;color:var(--b2b-text,#1F2937);transition:all 0.2s;font-size:14px}
            .b2b-w-dev-link:hover{border-color:var(--b2b-primary,#7B2CBF);background:#fff;transform:translateX(-2px)}
            .b2b-w-dev-link-ico{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;color:#fff}
            .b2b-w-dev-link-ico.wa1{background:#25D366}
            .b2b-w-dev-link-ico.wa2{background:#128C7E}
            .b2b-w-dev-link-ico.web{background:var(--b2b-primary,#7B2CBF)}
            .b2b-w-dev-link-text .lbl{font-size:11px;color:var(--b2b-text-tertiary,#9CA3AF);margin-bottom:1px}
            .b2b-w-dev-link-text .val{font-weight:600;color:var(--b2b-text,#1F2937)}

            .b2b-w-dev-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.3);z-index:99999;opacity:0;pointer-events:none;transition:opacity 0.3s}
            .b2b-w-dev-overlay.open{opacity:1;pointer-events:auto}

            @media(max-width:900px){
                .b2b-w-grid4,.b2b-w-grid8{grid-template-columns:repeat(2,1fr)}
                .b2b-w-steps{flex-direction:column}
                .b2b-w-dev-body{flex-direction:column}
            }
            @media(max-width:600px){
                .b2b-w-grid4,.b2b-w-grid8{grid-template-columns:1fr}
                .b2b-w-hero h1{font-size:24px}
            }
        </style>

        <div class="b2b-w">
            <!-- Hero -->
            <div class="b2b-w-hero">
                <div class="b2b-w-logo">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                <h1>سیستم مدیریت خرید B2B</h1>
                <p class="sub">پلتفرم جامع مدیریت خرید و تامین کالا برای کسب‌وکارها</p>
            </div>

            <div class="b2b-w-wrap">
                <!-- درباره -->
                <div class="b2b-w-card b2b-w-about">
                    <h2><span class="ico">&#128218;</span> درباره این سامانه</h2>
                    <p>سیستم مدیریت خرید B2B یک پلتفرم جامع و حرفه‌ای برای مدیریت فرآیندهای خرید و تامین کالا در کسب‌وکارهای B2B است. این سامانه روی بستر وردپرس و ووکامرس توسعه یافته و ۸ ماژول تخصصی برای مدیریت کامل چرخه خرید ارائه می‌دهد.</p>
                </div>

                <!-- ۸ ماژول اصلی -->
                <div class="b2b-w-card">
                    <h2><span class="ico">&#128736;</span> ماژول‌های سامانه</h2>
                    <div class="b2b-w-grid8">
                        <div class="b2b-w-mod"><span class="b2b-w-mod-ico">&#128230;</span> کاتالوگ محصولات</div>
                        <div class="b2b-w-mod"><span class="b2b-w-mod-ico">&#128101;</span> مدیریت تامین‌کنندگان</div>
                        <div class="b2b-w-mod"><span class="b2b-w-mod-ico">&#128220;</span> درخواست خرید (RFQ)</div>
                        <div class="b2b-w-mod"><span class="b2b-w-mod-ico">&#128176;</span> پیشنهادات و مقایسه</div>
                        <div class="b2b-w-mod"><span class="b2b-w-mod-ico">&#128230;</span> سفارشات خرید</div>
                        <div class="b2b-w-mod"><span class="b2b-w-mod-ico">&#128203;</span> قراردادها</div>
                        <div class="b2b-w-mod"><span class="b2b-w-mod-ico">&#128276;</span> اعلان‌های داخلی</div>
                        <div class="b2b-w-mod"><span class="b2b-w-mod-ico">&#128202;</span> داشبورد و گزارشات</div>
                    </div>
                </div>

                <!-- ویژگی‌ها -->
                <div class="b2b-w-card">
                    <h2><span class="ico">&#9889;</span> ویژگی‌های کلیدی</h2>
                    <div class="b2b-w-grid4">
                        <div class="b2b-w-feat">
                            <div class="b2b-w-feat-ico">&#128260;</div>
                            <h3>چرخه کامل خرید</h3>
                            <p>از درخواست تا قرارداد، تمام مراحل خرید را مدیریت کنید</p>
                        </div>
                        <div class="b2b-w-feat">
                            <div class="b2b-w-feat-ico">&#128200;</div>
                            <h3>داشبورد مدیریتی</h3>
                            <p>نمای کلی KPIها و نمودارهای وضعیت برای تصمیم‌گیری سریع</p>
                        </div>
                        <div class="b2b-w-feat">
                            <div class="b2b-w-feat-ico">&#128269;</div>
                            <h3>مقایسه پیشنهادات</h3>
                            <p>مقایسه جانب‌به‌جانب قیمت و شرایط تامین‌کنندگان</p>
                        </div>
                        <div class="b2b-w-feat">
                            <div class="b2b-w-feat-ico">&#128274;</div>
                            <h3>امنیت و دسترسی</h3>
                            <p>کنترل دسترسی کاربران و اعتبارسنجی تمام عملیات</p>
                        </div>
                    </div>
                </div>

                <!-- مراحل شروع -->
                <div class="b2b-w-card">
                    <h2><span class="ico">&#128640;</span> مراحل شروع کار</h2>
                    <div class="b2b-w-steps">
                        <div class="b2b-w-step">
                            <div class="b2b-w-step-num">۱</div>
                            <h4>اطلاعات پایه</h4>
                            <p>واحدهای اندازه‌گیری و اطلاعات جغرافیایی را تنظیم کنید</p>
                        </div>
                        <div class="b2b-w-step">
                            <div class="b2b-w-step-num">۲</div>
                            <h4>محصولات و تامین‌کنندگان</h4>
                            <p>کالاها و تامین‌کنندگان خود را در سامانه ثبت کنید</p>
                        </div>
                        <div class="b2b-w-step">
                            <div class="b2b-w-step-num">۳</div>
                            <h4>شروع فرآیند خرید</h4>
                            <p>اولین درخواست خرید را ایجاد و فرآیند را آغاز کنید</p>
                        </div>
                    </div>
                </div>

                <!-- شروع کار -->
                <div class="b2b-w-btn-wrap">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=b2b-dashboard')); ?>" class="b2b-w-btn">&#128640; ورود به داشبورد</a>
                    <p class="b2b-w-footer-text">با تشکر از انتخاب شما</p>
                </div>
            </div>
        </div>

        <!-- Developer Snackbar Button -->
        <button class="b2b-w-dev-toggle" id="b2b-dev-toggle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            اطلاعات برنامه‌نویس
        </button>

        <!-- Overlay -->
        <div class="b2b-w-dev-overlay" id="b2b-dev-overlay"></div>

        <!-- Developer Panel -->
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
                        <p class="b2b-w-dev-desc">طراحی، توسعه و پیاده‌سازی سیستم مدیریت خرید B2B با استفاده از بستر وردپرس و ووکامرس. تمامی ماژول‌ها شامل محصولات، تامین‌کنندگان، درخواست خرید، پیشنهادات، سفارشات، قراردادها، اعلان‌ها و داشبورد توسط تیم توسعه آچارسایت طراحی و اجرا شده است.</p>
                        <div class="b2b-w-dev-ver">&#128736; نسخه <?php echo esc_html(B2B_PROCUREMENT_VERSION); ?></div>
                    </div>
                    <div class="b2b-w-dev-links">
                        <a href="https://www.a4site.com" target="_blank" class="b2b-w-dev-link">
                            <span class="b2b-w-dev-link-ico web">&#127760;</span>
                            <div class="b2b-w-dev-link-text">
                                <div class="lbl">وب‌سایت رسمی</div>
                                <div class="val">www.a4site.com</div>
                            </div>
                        </a>
                        <a href="https://wa.me/989102109671" target="_blank" class="b2b-w-dev-link">
                            <span class="b2b-w-dev-link-ico wa1">&#128172;</span>
                            <div class="b2b-w-dev-link-text">
                                <div class="lbl">پشتیبانی فنی</div>
                                <div class="val">09102109671</div>
                            </div>
                        </a>
                        <a href="https://wa.me/989220061267" target="_blank" class="b2b-w-dev-link">
                            <span class="b2b-w-dev-link-ico wa2">&#128176;</span>
                            <div class="b2b-w-dev-link-text">
                                <div class="lbl">فروش و بازرگانی</div>
                                <div class="val">09220061267</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function(){
            var btn=document.getElementById('b2b-dev-toggle');
            var panel=document.getElementById('b2b-dev-panel');
            var overlay=document.getElementById('b2b-dev-overlay');
            var close=document.getElementById('b2b-dev-close');
            function open(){panel.classList.add('open');overlay.classList.add('open')}
            function shut(){panel.classList.remove('open');overlay.classList.remove('open')}
            btn.addEventListener('click',open);
            close.addEventListener('click',shut);
            overlay.addEventListener('click',shut);
        })();
        </script>
        <?php
    }
}
