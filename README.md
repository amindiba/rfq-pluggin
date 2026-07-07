# سیستم مدیریت خرید B2B | B2B Procurement System

> یک پلتفرم جامع مدیریت خرید و تامین کالا برای کسب‌وکارهای B2B مبتنی بر وردپرس و ووکامرس
>
> A comprehensive B2B procurement and supply chain management platform for WordPress and WooCommerce

---

[![Version](https://img.shields.io/badge/version-1.4.0-7B2CBF)](https://github.com/your-username/rfq-pluggin)
[![WordPress](https://img.shields.io/badge/WordPress-6.0+-21759B)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4)](https://php.net)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0+-965800)](https://woocommerce.com)
[![License](https://img.shields.io/badge/license-proprietary-red)](LICENSE)

---

## 📖 معرفی | Introduction

سیستم مدیریت خرید B2B یک افزونه سازمانی وردپرس است که فرآیند کامل خرید و تامین کالا را مدیریت می‌کند. این سیستم شامل ۸ ماژول اصلی و ۴ ماژول توسعه‌پذیر است.

B2B Procurement System is an enterprise WordPress plugin that manages the complete procurement lifecycle. It includes 8 core modules and 4 extensible modules.

---

## 🏗️ معماری | Architecture

```
RFQ_PLUGGIN/
├── bootstrap.php                          # لودر اصلی پلاگین
├── plugin.php                             # فایل اصلی وردپرس
├── includes/
│   ├── admin/                             # صفحات مدیریتی
│   │   ├── class-b2b-admin.php            # Shell، منوها، ناویگیشن
│   │   ├── class-b2b-dashboard-report.php # داشبورد و گزارشات
│   │   └── class-b2b-*.php                # صفحات هر ماژول
│   ├── database/                          # کلاس‌های پایگاه داده
│   ├── ajax/                              # هندلرهای AJAX
│   ├── catalog/                           # سرویس‌های محصولات ووکامرس
│   ├── core/                              # فعال‌سازی، غیرفعال‌سازی، امنیت
│   ├── helpers/                           # لاگر، امنیت
│   ├── storage/                           # مدیریت فایل‌ها
│   └── api/                               # REST API
├── modules/
│   ├── product-resources/                 # مدیریت منابع محصولات
│   ├── product-definitions/               # تعریف الگوهای محصولات
│   ├── dynamic-specifications/            # موتور مشخصات فنی داینامیک
│   └── product-features/                  # ویژگی‌های محصولات
├── assets/
│   ├── css/                               # فایل‌های CSS
│   ├── js/                                # فایل‌های JavaScript
│   └── fonts/                             # فونت Vazirmatn
└── languages/                             # فایل‌های ترجمه
```

---

## 🚀 ماژول‌ها | Modules

### ماژول‌های اصلی (Core Modules)

| ماژول | توضیح | Module | Description |
|-------|--------|--------|-------------|
| کاتالوگ محصولات | مدیریت محصولات ووکامرس با فیلدهای اختصاصی B2B | Product Catalog | WC product management with B2B custom fields |
| مدیریت تامین‌کنندگان | ثبت و مدیریت تامین‌کنندگان با وضعیت | Supplier Management | Supplier registration and status management |
| درخواست خرید (RFQ) | ایجاد و ارسال درخواست استعلام قیمت | Request for Quotation | Create and send RFQs |
| پیشنهادات و مقایسه | دریافت و مقایسه پیشنهادات قیمت تامین‌کنندگان | Quotation & Comparison | Receive and compare supplier quotations |
| سفارشات خرید | ایجاد و تأیید سفارشات از پیشنهاد برنده | Purchase Orders | Create and confirm POs from winning quotations |
| قراردادها | ایجاد و مدیریت قراردادهای خرید | Contracts | Contract creation and management |
| اعلان‌ها | سیستم اعلان داخلی رویدادمحور | Internal Notifications | Event-driven internal notification system |
| داشبورد و گزارشات | نمای کلی مدیریتی و گزارش‌گیری | Dashboard & Reports | Management overview and reporting |

### ماژول‌های توسعه‌پذیر (Extensible Modules)

| ماژول | توضیح | Module | Description |
|-------|--------|--------|-------------|
| منابع محصول | مدیریت فایل‌ها و مستندات هر محصول | Product Resources | Manage files and documents per product |
| تعریف محصولات | الگوهای ثابت محصولات | Product Definitions | Product template patterns |
| مشخصات فنی داینامیک | فیلدهای اختصاصی برای هر تعریف | Dynamic Specifications | Custom fields per definition |
| ویژگی‌های محصولات | مدیریت ویژگی‌ها و مشخصات | Product Features | Feature and attribute management |

---

## ⚡ نصب و راه‌اندازی | Installation

### پیش‌نیازها
- وردپرس ۶.۰ یا بالاتر
- PHP ۸.۱ یا بالاتر
- ووکامرس ۸.۰ یا بالاتر
- لینک‌های یکتا (Pretty Permalinks) فعال
- حافظه ۱۲۸MB یا بیشتر

### Prerequisites
- WordPress 6.0+
- PHP 8.1+
- WooCommerce 8.0+
- Pretty Permalinks enabled
- 128MB+ memory

### مراحل نصب
1. فایل‌ها را در پوشه `wp-content/plugins/RFQ_PLUGGIN` آپلود کنید
2. افزونه را در پنل مدیریت وردپرس فعال کنید
3. جدول‌های پایگاه داده خودکار ایجاد می‌شوند
4. منوی «سیستم مدیریت خرید» در پنل مدیریت ظاهر می‌شود

### Installation Steps
1. Upload files to `wp-content/plugins/RFQ_PLUGGIN`
2. Activate the plugin in WordPress admin
3. Database tables are created automatically
4. The "Procurement System" menu appears in admin panel

---

## 📊 پایگاه داده | Database

افزونه از ۱۳ جدول اختصاصی استفاده می‌کند (بدون تغییر در ساختار ووکامرس):

The plugin uses 13 custom tables (without modifying WooCommerce structure):

| جدول | Table | توضیح |
|------|-------|--------|
| `b2b_md_units` | واحدهای اندازه‌گیری | Measurement units |
| `b2b_provinces` | استان‌ها | Provinces |
| `b2b_cities` | شهرها | Cities |
| `b2b_suppliers` | تامین‌کنندگان | Suppliers |
| `b2b_rfqs` | درخواست‌های خرید | RFQs |
| `b2b_rfq_products` | محصولات درخواست | RFQ Products |
| `b2b_rfq_suppliers` | تامین‌کنندگان درخواست | RFQ Suppliers |
| `b2b_quotations` | پیشنهادات قیمت | Quotations |
| `b2b_quotation_items` | اقلام پیشنهاد | Quotation Items |
| `b2b_purchase_orders` | سفارشات خرید | Purchase Orders |
| `b2b_po_items` | اقلام سفارش | PO Items |
| `b2b_contracts` | قراردادها | Contracts |
| `b2b_notifications` | اعلان‌ها | Notifications |

---

## 🔒 امنیت | Security

- Nonce Verification در تمام عملیات
- Capability Check برای دسترسی‌ها
- Sanitize ورودی‌ها
- Escape خروجی‌ها
- CSRF Protection
- XSS Prevention

---

## 🎨 رابط کاربری | UI/UX

- طراحی مدرن و مینیمال سازمانی
- پالت رنگی بنفش مصوب
- فونت وزیرمتن فارسی
- ریسپانسیو برای دسکتاپ و موبایل
- انیمیشن‌های سبک و سریع
- سایدبار قابل جمع/باز شدن

---

## 🛠️ فناوری‌ها | Technologies

- **Backend:** PHP 8.1+, WordPress API, WooCommerce API
- **Frontend:** Vanilla JS, jQuery, CSS3
- **Database:** MySQL 8+, WordPress Custom Tables
- **Font:** Vazirmatn (Persian)
- **Design System:** CSS Custom Properties (Design Tokens)

---

## 📝 تغییرات اخیر | Changelog

### v1.4.0 (2026-07-07)
- اضافه شدن ماژول Product Resource Manager
- اضافه شدن ماژول Product Definition Manager
- اضافه شدن موتور Dynamic Specifications
- اضافه شدن ماژول Product Features
- بازطراحی داشبورد مدیریت
- اضافه شدن KPI با دسترسی مستقیم
- بهبود سایدبار با flyout menu
- رفع خطاهای PHP و بهبود پایداری

### v1.0.0 (2026-07-05)
- انتشار نسخه اولیه با ۸ ماژول اصلی
- پیاده‌سازی کامل چرخه خرید

---

## 👨‍💻 توسعه‌دهنده | Developer

**امین دیبا | Amin Diba**

شرکت طراحی، برنامه‌نویسی و توسعه وب **آچارسایت** | [a4site.com](https://www.a4site.com)

---

## 📄 مجوز | License

این نرم‌افزار مالکیتی (Proprietary) است. استفاده و توزیع بدون مجوز ممنوع است.

This software is proprietary. Usage and distribution without authorization is prohibited.

---

> ساخته شده با ❤️ توسط تیم توسعه آچارسایت
>
> Built with ❤️ by A4site Development Team
