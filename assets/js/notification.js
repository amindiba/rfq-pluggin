/**
 * B2B Notification Management
 * Version: 1.0.0
 */
(function ($) {
    'use strict';

    window.B2BNotification = {
        page: 1,

        init: function () {
            if ($('#notification-filter').length) this.initSearch();
        },

        initSearch: function () {
            var self = this;
            $('#notification-filter').on('change', function () { self.page = 1; self.load(); });
            $('#notification-type').on('change', function () { self.page = 1; self.load(); });
            self.load();
        },

        load: function () {
            var self = this;
            var $c = $('#notification-table-container');
            $c.html('<div style="text-align:center;padding:40px;"><div class="b2b-spinner-lg" style="margin:0 auto;"></div></div>');

            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_notification_get_list',
                _b2b_nonce: b2bProcurement.nonce,
                is_read: $('#notification-filter').val() || '',
                type: $('#notification-type').val() || '',
                page: self.page,
                per_page: 20
            }, function (r) {
                if (r && r.success) self.render(r.data);
                else $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><p>خطا در بارگذاری</p></div></div></div>');
            }).fail(function (x, s, e) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><p>خطا: ' + e + '</p></div></div></div>');
            });
        },

        render: function (d) {
            var self = this;
            var $c = $('#notification-table-container');
            if (!d.items.length) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128276;</div><p>اعلانی یافت نشد</p></div></div></div>');
                $('#notification-count').text('۰ مورد');
                return;
            }

            var typeMap = { info: ['اطلاعات', 'b2b-badge-info', '&#128336;'], success: ['موفقیت', 'b2b-badge-success', '&#9989;'], warning: ['هشدار', 'b2b-badge-warning', '&#9888;'] };
            var h = '<div class="b2b-card"><div class="b2b-card-body" style="padding:0;overflow-x:auto;"><table class="b2b-table"><thead><tr>';
            h += '<th></th><th>عنوان</th><th>پیام</th><th>نوع</th><th>تاریخ</th><th>عملیات</th>';
            h += '</tr></thead><tbody>';

            for (var i = 0; i < d.items.length; i++) {
                var x = d.items[i];
                var ti = typeMap[x.type] || ['نامشخص', 'b2b-badge-default', '&#10067;'];
                var readClass = x.is_read == 0 ? 'font-weight:600;' : '';

                h += '<tr style="' + readClass + '">';
                h += '<td>' + ti[2] + '</td>';
                h += '<td><strong>' + esc(x.title) + '</strong></td>';
                h += '<td>' + esc(x.message.substring(0, 80)) + (x.message.length > 80 ? '...' : '') + '</td>';
                h += '<td><span class="b2b-badge ' + ti[1] + '">' + ti[0] + '</span></td>';
                h += '<td>' + esc(x.created_at) + '</td>';
                h += '<td style="white-space:nowrap;">';
                h += '<a href="/wp-admin/admin.php?page=b2b-notification-detail&id=' + x.id + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="مشاهده">&#128065;</a> ';
                if (x.is_read == 0) {
                    h += '<button type="button" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="خوانده شد" onclick="B2BNotification.markRead(' + x.id + ')">&#9989;</button>';
                }
                h += '</td></tr>';
            }

            h += '</tbody></table></div></div>';
            $c.html(h);

            var pg = '<div class="b2b-table-pagination"><div class="b2b-pagination-info">' + toPersianNum(d.total) + ' مورد - صفحه ' + toPersianNum(d.page) + ' از ' + toPersianNum(d.pages) + '</div><div class="b2b-pagination-links">';
            if (d.page > 1) pg += '<button class="b2b-page-link" onclick="B2BNotification.go(' + (d.page - 1) + ')">&laquo;</button>';
            for (var i = 1; i <= d.pages; i++) {
                if (i === d.page) pg += '<span class="b2b-page-link b2b-page-active">' + toPersianNum(i) + '</span>';
                else if (i === 1 || i === d.pages || Math.abs(i - d.page) <= 2) pg += '<button class="b2b-page-link" onclick="B2BNotification.go(' + i + ')">' + toPersianNum(i) + '</button>';
            }
            if (d.page < d.pages) pg += '<button class="b2b-page-link" onclick="B2BNotification.go(' + (d.page + 1) + ')">&raquo;</button>';
            pg += '</div></div>';
            $('#notification-pagination').html(pg);
            $('#notification-count').text(toPersianNum(d.total) + ' مورد');
        },

        go: function (p) { this.page = p; this.load(); },

        markRead: function (id) {
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_notification_mark_read', _b2b_nonce: b2bProcurement.nonce, item_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); B2BNotification.load(); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        markAllRead: function () {
            if (!confirm('آیا مطمئن هستید که همه اعلان‌ها را خوانده شده علامت بزنید؟')) return;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_notification_mark_all_read', _b2b_nonce: b2bProcurement.nonce }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); B2BNotification.load(); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        init: function () {
            if ($('#notification-filter').length) this.initSearch();
        }
    };

    $(document).ready(function () { B2BNotification.init(); });
})(jQuery);
