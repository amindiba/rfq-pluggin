/**
 * B2B Supplier Management
 * Version: 1.0.0
 */
(function ($) {
    'use strict';

    window.B2BSupplier = {
        page: 1,

        init: function () {
            if ($('#supplier-search').length) this.initSearch();
            if ($('#supplier-form').length) this.initForm();
        },

        initSearch: function () {
            var self = this;
            $('#supplier-search').on('keyup', function () {
                clearTimeout(self._timer);
                self._timer = setTimeout(function () { self.page = 1; self.load(); }, 400);
            });
            $('#supplier-status').on('change', function () { self.page = 1; self.load(); });
            self.load();
        },

        load: function () {
            var self = this;
            var $c = $('#supplier-table-container');
            $c.html('<div style="text-align:center;padding:40px;"><div class="b2b-spinner-lg" style="margin:0 auto;"></div></div>');

            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_supplier_get_list',
                _b2b_nonce: b2bProcurement.nonce,
                search: $('#supplier-search').val() || '',
                status: $('#supplier-status').val() || '',
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
            var $c = $('#supplier-table-container');
            if (!d.items.length) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128100;</div><p>تامین‌کننده‌ای یافت نشد</p><div><a href="/wp-admin/admin.php?page=b2b-supplier-add" class="b2b-btn b2b-btn-primary">افزودن</a></div></div></div></div>');
                $('#supplier-count').text('۰ مورد');
                return;
            }

            var h = '<div class="b2b-card"><div class="b2b-card-body" style="padding:0;overflow-x:auto;"><table class="b2b-table"><thead><tr>';
            h += '<th class="b2b-col-check"><input type="checkbox" class="b2b-check-all" /></th>';
            h += '<th>کد</th><th>نام</th><th>شرکت</th><th>تماس</th><th>وضعیت</th><th>عملیات</th>';
            h += '</tr></thead><tbody>';

            for (var i = 0; i < d.items.length; i++) {
                var x = d.items[i];
                var sc = x.status == 1 ? 'b2b-status-active' : 'b2b-status-inactive';
                var sl = x.status == 1 ? 'فعال' : 'غیرفعال';
                h += '<tr>';
                h += '<td class="b2b-col-check"><input type="checkbox" class="b2b-row-check" value="' + x.id + '" /></td>';
                h += '<td><span class="b2b-badge b2b-badge-primary">' + esc(x.code) + '</span></td>';
                h += '<td><strong>' + esc(x.name) + '</strong></td>';
                h += '<td>' + esc(x.company_name || '-') + '</td>';
                h += '<td>' + esc(x.phone || x.mobile || '-') + '</td>';
                h += '<td><span class="b2b-status ' + sc + '">' + sl + '</span></td>';
                h += '<td style="white-space:nowrap;">';
                h += '<a href="/wp-admin/admin.php?page=b2b-supplier-detail&id=' + x.id + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="جزئیات">&#128065;</a> ';
                h += '<a href="/wp-admin/admin.php?page=b2b-supplier-edit&id=' + x.id + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="ویرایش">&#9998;</a> ';
                h += '<button type="button" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="تغییر وضعیت" onclick="B2BSupplier.toggle(' + x.id + ')">&#8644;</button> ';
                h += '<button type="button" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="حذف" onclick="B2BSupplier.del(' + x.id + ')">&#128465;</button>';
                h += '</td></tr>';
            }

            h += '</tbody></table></div></div>';
            $c.html(h);

            var pg = '<div class="b2b-table-pagination"><div class="b2b-pagination-info">' + toPersianNum(d.total) + ' مورد - صفحه ' + toPersianNum(d.page) + ' از ' + toPersianNum(d.pages) + '</div><div class="b2b-pagination-links">';
            if (d.page > 1) pg += '<button class="b2b-page-link" onclick="B2BSupplier.go(' + (d.page - 1) + ')">&laquo;</button>';
            for (var i = 1; i <= d.pages; i++) {
                if (i === d.page) pg += '<span class="b2b-page-link b2b-page-active">' + toPersianNum(i) + '</span>';
                else if (i === 1 || i === d.pages || Math.abs(i - d.page) <= 2) pg += '<button class="b2b-page-link" onclick="B2BSupplier.go(' + i + ')">' + toPersianNum(i) + '</button>';
            }
            if (d.page < d.pages) pg += '<button class="b2b-page-link" onclick="B2BSupplier.go(' + (d.page + 1) + ')">&raquo;</button>';
            pg += '</div></div>';
            $('#supplier-pagination').html(pg);
            $('#supplier-count').text(toPersianNum(d.total) + ' مورد');
        },

        go: function (p) { this.page = p; this.load(); },

        toggle: function (id) {
            var self = this;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_supplier_toggle', _b2b_nonce: b2bProcurement.nonce, item_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); self.load(); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        del: function (id) {
            if (!confirm('آیا از حذف این تامین‌کننده اطمینان دارید؟')) return;
            var self = this;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_supplier_delete', _b2b_nonce: b2bProcurement.nonce, item_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); self.load(); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        initForm: function () {
            $('#supplier-form').on('submit', function (e) {
                e.preventDefault();
                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).html('<span class="b2b-spinner"></span> ذخیره...');
                $.post(b2bProcurement.ajaxUrl, $(this).serialize(), function (r) {
                    if (r && r.success) {
                        B2BAdmin.toast(r.data.message, 'success');
                        setTimeout(function () { window.location.href = '/wp-admin/admin.php?page=b2b-suppliers'; }, 1000);
                    } else {
                        B2BAdmin.toast(r.data.message || 'خطا', 'error');
                        $btn.prop('disabled', false).html('ذخیره تامین‌کننده');
                    }
                }).fail(function () {
                    B2BAdmin.toast('خطا در ارتباط با سرور', 'error');
                    $btn.prop('disabled', false).html('ذخیره تامین‌کننده');
                });
            });
        }
    };

    function esc(t) { if (!t) return ''; var d = document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }
    $(document).ready(function () { B2BSupplier.init(); });
})(jQuery);
