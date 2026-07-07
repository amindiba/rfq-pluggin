/**
 * B2B Contract Management
 * Version: 1.0.0
 */
(function ($) {
    'use strict';

    window.B2BContract = {
        page: 1,

        init: function () {
            if ($('#contract-search').length) this.initSearch();
            if ($('#contract-form').length) this.initForm();
        },

        initSearch: function () {
            var self = this;
            $('#contract-search').on('keyup', function () {
                clearTimeout(self._timer);
                self._timer = setTimeout(function () { self.page = 1; self.load(); }, 400);
            });
            $('#contract-status').on('change', function () { self.page = 1; self.load(); });
            self.load();
        },

        load: function () {
            var self = this;
            var $c = $('#contract-table-container');
            $c.html('<div style="text-align:center;padding:40px;"><div class="b2b-spinner-lg" style="margin:0 auto;"></div></div>');

            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_contract_get_list',
                _b2b_nonce: b2bProcurement.nonce,
                search: $('#contract-search').val() || '',
                status: $('#contract-status').val() || '',
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
            var $c = $('#contract-table-container');
            if (!d.items.length) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128221;</div><p>قراردادی یافت نشد</p><div><a href="/wp-admin/admin.php?page=b2b-contract-add" class="b2b-btn b2b-btn-primary">افزودن قرارداد</a></div></div></div></div>');
                $('#contract-count').text('۰ مورد');
                return;
            }

            var statusMap = { draft: ['پیش‌نویس', 'b2b-badge-default'], active: ['فعال', 'b2b-badge-success'], closed: ['بسته شده', 'b2b-badge-danger'] };
            var h = '<div class="b2b-card"><div class="b2b-card-body" style="padding:0;overflow-x:auto;"><table class="b2b-table"><thead><tr>';
            h += '<th>شماره قرارداد</th><th>عنوان</th><th>تامین‌کننده</th><th>وضعیت</th><th>شروع</th><th>پایان</th><th>ارزش</th><th>عملیات</th>';
            h += '</tr></thead><tbody>';

            for (var i = 0; i < d.items.length; i++) {
                var x = d.items[i];
                var si = statusMap[x.status] || ['نامشخص', 'b2b-badge-default'];
                h += '<tr>';
                h += '<td><span class="b2b-badge b2b-badge-primary">' + esc(x.contract_number) + '</span></td>';
                h += '<td>' + esc(x.title) + '</td>';
                h += '<td>' + esc(x.supplier_name) + '</td>';
                h += '<td><span class="b2b-badge ' + si[1] + '">' + si[0] + '</span></td>';
                h += '<td>' + esc(x.start_date || '-') + '</td>';
                h += '<td>' + esc(x.end_date || '-') + '</td>';
                h += '<td>' + toPersianNum(x.contract_value) + ' تومان</td>';
                h += '<td style="white-space:nowrap;">';
                h += '<a href="/wp-admin/admin.php?page=b2b-contract-detail&id=' + x.id + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="جزئیات">&#128065;</a> ';
                h += '<button type="button" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="حذف" onclick="B2BContract.del(' + x.id + ')">&#128465;</button>';
                h += '</td></tr>';
            }

            h += '</tbody></table></div></div>';
            $c.html(h);

            var pg = '<div class="b2b-table-pagination"><div class="b2b-pagination-info">' + toPersianNum(d.total) + ' مورد - صفحه ' + toPersianNum(d.page) + ' از ' + toPersianNum(d.pages) + '</div><div class="b2b-pagination-links">';
            if (d.page > 1) pg += '<button class="b2b-page-link" onclick="B2BContract.go(' + (d.page - 1) + ')">&laquo;</button>';
            for (var i = 1; i <= d.pages; i++) {
                if (i === d.page) pg += '<span class="b2b-page-link b2b-page-active">' + toPersianNum(i) + '</span>';
                else if (i === 1 || i === d.pages || Math.abs(i - d.page) <= 2) pg += '<button class="b2b-page-link" onclick="B2BContract.go(' + i + ')">' + toPersianNum(i) + '</button>';
            }
            if (d.page < d.pages) pg += '<button class="b2b-page-link" onclick="B2BContract.go(' + (d.page + 1) + ')">&raquo;</button>';
            pg += '</div></div>';
            $('#contract-pagination').html(pg);
            $('#contract-count').text(toPersianNum(d.total) + ' مورد');
        },

        go: function (p) { this.page = p; this.load(); },

        del: function (id) {
            if (!confirm('آیا از حذف این قرارداد اطمینان دارید؟')) return;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_contract_delete', _b2b_nonce: b2bProcurement.nonce, contract_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); B2BContract.load(); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        activate: function (id) {
            if (!confirm('آیا از فعال‌سازی این قرارداد اطمینان دارید؟')) return;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_contract_activate', _b2b_nonce: b2bProcurement.nonce, contract_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); setTimeout(function () { location.reload(); }, 1000); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        close: function (id) {
            if (!confirm('آیا از بستن این قرارداد اطمینان دارید؟')) return;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_contract_close', _b2b_nonce: b2bProcurement.nonce, contract_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); setTimeout(function () { location.reload(); }, 1000); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        initForm: function () {
            $('#contract-form').on('submit', function (e) {
                e.preventDefault();
                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).html('<span class="b2b-spinner"></span> ذخیره...');

                var postData = {
                    action: 'b2b_contract_save',
                    _b2b_nonce: b2bProcurement.nonce,
                    contract_id: $('[name="contract_id"]').val() || '',
                    title: $('[name="title"]').val(),
                    start_date: $('[name="start_date"]').val(),
                    end_date: $('[name="end_date"]').val(),
                    notes: $('[name="notes"]').val()
                };

                $.post(b2bProcurement.ajaxUrl, postData, function (r) {
                    if (r && r.success) {
                        B2BAdmin.toast(r.data.message, 'success');
                        setTimeout(function () { window.location.href = '/wp-admin/admin.php?page=b2b-contracts'; }, 1000);
                    } else {
                        B2BAdmin.toast(r.data.message || 'خطا', 'error');
                        $btn.prop('disabled', false).html('ذخیره قرارداد');
                    }
                }).fail(function () {
                    B2BAdmin.toast('خطا در ارتباط با سرور', 'error');
                    $btn.prop('disabled', false).html('ذخیره قرارداد');
                });
            });
        },

        
    };

    $(document).ready(function () { B2BContract.init(); });
})(jQuery);
