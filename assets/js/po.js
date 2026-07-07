/**
 * B2B Purchase Order Management
 * Version: 1.0.0
 */
(function ($) {
    'use strict';

    window.B2BPO = {
        page: 1,

        init: function () {
            if ($('#po-search').length) this.initSearch();
        },

        initSearch: function () {
            var self = this;
            $('#po-search').on('keyup', function () {
                clearTimeout(self._timer);
                self._timer = setTimeout(function () { self.page = 1; self.load(); }, 400);
            });
            $('#po-status').on('change', function () { self.page = 1; self.load(); });
            self.load();
        },

        load: function () {
            var self = this;
            var $c = $('#po-table-container');
            $c.html('<div style="text-align:center;padding:40px;"><div class="b2b-spinner-lg" style="margin:0 auto;"></div></div>');

            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_po_get_list',
                _b2b_nonce: b2bProcurement.nonce,
                search: $('#po-search').val() || '',
                status: $('#po-status').val() || '',
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
            var $c = $('#po-table-container');
            if (!d.items.length) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128230;</div><p>سفارش خریدی یافت نشد</p></div></div></div>');
                $('#po-count').text('۰ مورد');
                return;
            }

            var statusMap = { draft: ['پیش‌نویس', 'b2b-badge-default'], confirmed: ['تأیید شده', 'b2b-badge-success'], cancelled: ['لغو شده', 'b2b-badge-danger'] };
            var h = '<div class="b2b-card"><div class="b2b-card-body" style="padding:0;overflow-x:auto;"><table class="b2b-table"><thead><tr>';
            h += '<th>شماره سفارش</th><th>تامین‌کننده</th><th>درخواست</th><th>وضعیت</th><th>جمع کل</th><th>تاریخ</th><th>عملیات</th>';
            h += '</tr></thead><tbody>';

            for (var i = 0; i < d.items.length; i++) {
                var x = d.items[i];
                var si = statusMap[x.status] || ['نامشخص', 'b2b-badge-default'];
                h += '<tr>';
                h += '<td><span class="b2b-badge b2b-badge-primary">' + esc(x.po_number) + '</span></td>';
                h += '<td>' + esc(x.supplier_name) + '</td>';
                h += '<td>' + esc(x.rfq_reference) + '</td>';
                h += '<td><span class="b2b-badge ' + si[1] + '">' + si[0] + '</span></td>';
                h += '<td>' + toPersianNum(x.grand_total) + ' تومان</td>';
                h += '<td>' + esc(x.created_at) + '</td>';
                h += '<td style="white-space:nowrap;">';
                h += '<a href="/wp-admin/admin.php?page=b2b-po-detail&id=' + x.id + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="جزئیات">&#128065;</a> ';
                h += '<button type="button" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="حذف" onclick="B2BPO.del(' + x.id + ')">&#128465;</button>';
                h += '</td></tr>';
            }

            h += '</tbody></table></div></div>';
            $c.html(h);

            var pg = '<div class="b2b-table-pagination"><div class="b2b-pagination-info">' + toPersianNum(d.total) + ' مورد - صفحه ' + toPersianNum(d.page) + ' از ' + toPersianNum(d.pages) + '</div><div class="b2b-pagination-links">';
            if (d.page > 1) pg += '<button class="b2b-page-link" onclick="B2BPO.go(' + (d.page - 1) + ')">&laquo;</button>';
            for (var i = 1; i <= d.pages; i++) {
                if (i === d.page) pg += '<span class="b2b-page-link b2b-page-active">' + toPersianNum(i) + '</span>';
                else if (i === 1 || i === d.pages || Math.abs(i - d.page) <= 2) pg += '<button class="b2b-page-link" onclick="B2BPO.go(' + i + ')">' + toPersianNum(i) + '</button>';
            }
            if (d.page < d.pages) pg += '<button class="b2b-page-link" onclick="B2BPO.go(' + (d.page + 1) + ')">&raquo;</button>';
            pg += '</div></div>';
            $('#po-pagination').html(pg);
            $('#po-count').text(toPersianNum(d.total) + ' مورد');
        },

        go: function (p) { this.page = p; this.load(); },

        del: function (id) {
            if (!confirm('آیا از حذف این سفارش اطمینان دارید؟')) return;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_po_delete', _b2b_nonce: b2bProcurement.nonce, po_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); B2BPO.load(); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        confirm: function (id) {
            if (!confirm('آیا از تأیید این سفارش اطمینان دارید؟')) return;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_po_confirm', _b2b_nonce: b2bProcurement.nonce, po_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); setTimeout(function () { location.reload(); }, 1000); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        cancel: function (id) {
            if (!confirm('آیا از لغو این سفارش اطمینان دارید؟')) return;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_po_cancel', _b2b_nonce: b2bProcurement.nonce, po_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); setTimeout(function () { location.reload(); }, 1000); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        
    };

    $(document).ready(function () { B2BPO.init(); });
})(jQuery);
