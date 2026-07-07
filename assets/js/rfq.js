/**
 * B2B RFQ Management
 * Version: 1.0.0
 */
(function ($) {
    'use strict';

    window.B2BRFQ = {
        page: 1,

        init: function () {
            if ($('#rfq-search').length) this.initSearch();
            if ($('#rfq-form').length) this.initForm();
        },

        initSearch: function () {
            var self = this;
            $('#rfq-search').on('keyup', function () {
                clearTimeout(self._timer);
                self._timer = setTimeout(function () { self.page = 1; self.load(); }, 400);
            });
            $('#rfq-status').on('change', function () { self.page = 1; self.load(); });
            self.load();
        },

        load: function () {
            var self = this;
            var $c = $('#rfq-table-container');
            $c.html('<div style="text-align:center;padding:40px;"><div class="b2b-spinner-lg" style="margin:0 auto;"></div></div>');

            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_rfq_get_list',
                _b2b_nonce: b2bProcurement.nonce,
                search: $('#rfq-search').val() || '',
                status: $('#rfq-status').val() || '',
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
            var $c = $('#rfq-table-container');
            if (!d.items.length) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128220;</div><p>درخواستی یافت نشد</p><div><a href="/wp-admin/admin.php?page=b2b-rfq-add" class="b2b-btn b2b-btn-primary">افزودن درخواست</a></div></div></div></div>');
                $('#rfq-count').text('۰ مورد');
                return;
            }

            var statusMap = { draft: ['پیش‌نویس', 'b2b-badge-default'], submitted: ['ارسال شده', 'b2b-badge-primary'], closed: ['بسته شده', 'b2b-badge-success'] };
            var h = '<div class="b2b-card"><div class="b2b-card-body" style="padding:0;overflow-x:auto;"><table class="b2b-table"><thead><tr>';
            h += '<th>شماره</th><th>عنوان</th><th>وضعیت</th><th>مهلت</th><th>تاریخ ایجاد</th><th>عملیات</th>';
            h += '</tr></thead><tbody>';

            for (var i = 0; i < d.items.length; i++) {
                var x = d.items[i];
                var si = statusMap[x.status] || ['نامشخص', 'b2b-badge-default'];
                h += '<tr>';
                h += '<td><span class="b2b-badge b2b-badge-primary">' + esc(x.reference) + '</span></td>';
                h += '<td><strong>' + esc(x.title) + '</strong></td>';
                h += '<td><span class="b2b-badge ' + si[1] + '">' + si[0] + '</span></td>';
                h += '<td>' + esc(x.deadline || '-') + '</td>';
                h += '<td>' + esc(x.created_at) + '</td>';
                h += '<td style="white-space:nowrap;">';
                h += '<a href="/wp-admin/admin.php?page=b2b-rfq-detail&id=' + x.id + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="جزئیات">&#128065;</a> ';
                if (x.status === 'draft') {
                    h += '<a href="/wp-admin/admin.php?page=b2b-rfq-edit&id=' + x.id + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="ویرایش">&#9998;</a> ';
                }
                h += '<button type="button" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="حذف" onclick="B2BRFQ.del(' + x.id + ')">&#128465;</button>';
                h += '</td></tr>';
            }

            h += '</tbody></table></div></div>';
            $c.html(h);

            var pg = '<div class="b2b-table-pagination"><div class="b2b-pagination-info">' + toPersianNum(d.total) + ' مورد - صفحه ' + toPersianNum(d.page) + ' از ' + toPersianNum(d.pages) + '</div><div class="b2b-pagination-links">';
            if (d.page > 1) pg += '<button class="b2b-page-link" onclick="B2BRFQ.go(' + (d.page - 1) + ')">&laquo;</button>';
            for (var i = 1; i <= d.pages; i++) {
                if (i === d.page) pg += '<span class="b2b-page-link b2b-page-active">' + toPersianNum(i) + '</span>';
                else if (i === 1 || i === d.pages || Math.abs(i - d.page) <= 2) pg += '<button class="b2b-page-link" onclick="B2BRFQ.go(' + i + ')">' + toPersianNum(i) + '</button>';
            }
            if (d.page < d.pages) pg += '<button class="b2b-page-link" onclick="B2BRFQ.go(' + (d.page + 1) + ')">&raquo;</button>';
            pg += '</div></div>';
            $('#rfq-pagination').html(pg);
            $('#rfq-count').text(toPersianNum(d.total) + ' مورد');
        },

        go: function (p) { this.page = p; this.load(); },

        del: function (id) {
            if (!confirm('آیا از حذف این درخواست اطمینان دارید؟')) return;
            var self = this;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_rfq_delete', _b2b_nonce: b2bProcurement.nonce, rfq_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); self.load(); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        submitRfq: function (id) {
            if (!confirm('آیا از ارسال درخواست اطمینان دارید؟ پس از ارسال قابل ویرایش نیست.')) return;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_rfq_submit', _b2b_nonce: b2bProcurement.nonce, rfq_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); setTimeout(function () { location.reload(); }, 1000); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        closeRfq: function (id) {
            if (!confirm('آیا از بستن درخواست اطمینان دارید؟')) return;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_rfq_close', _b2b_nonce: b2bProcurement.nonce, rfq_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); setTimeout(function () { location.reload(); }, 1000); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        initForm: function () {
            $('#rfq-form').on('submit', function (e) {
                e.preventDefault();
                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).html('<span class="b2b-spinner"></span> ذخیره...');

                var products = [];
                $('#rfq-products-body tr').each(function () {
                    var $r = $(this);
                    products.push({
                        product_id: $r.find('[name$="[product_id]"]').val(),
                        requested_qty: $r.find('[name$="[requested_qty]"]').val(),
                        unit: $r.find('[name$="[unit]"]').val(),
                        notes: $r.find('[name$="[notes]"]').val()
                    });
                });

                var suppliers = [];
                $('input[name="suppliers[]"]:checked').each(function () {
                    suppliers.push({ supplier_id: $(this).val(), supplier_name: $(this).data('name') });
                });

                var postData = {
                    action: 'b2b_rfq_save',
                    _b2b_nonce: b2bProcurement.nonce,
                    rfq_id: $('[name="rfq_id"]').val() || '',
                    title: $('[name="title"]').val(),
                    description: $('[name="description"]').val(),
                    deadline: $('[name="deadline"]').val(),
                    products: JSON.stringify(products),
                    suppliers: JSON.stringify(suppliers)
                };

                $.post(b2bProcurement.ajaxUrl, postData, function (r) {
                    if (r && r.success) {
                        B2BAdmin.toast(r.data.message, 'success');
                        setTimeout(function () { window.location.href = '/wp-admin/admin.php?page=b2b-rfqs'; }, 1000);
                    } else {
                        B2BAdmin.toast(r.data.message || 'خطا', 'error');
                        $btn.prop('disabled', false).html('ذخیره درخواست');
                    }
                }).fail(function () {
                    B2BAdmin.toast('خطا در ارتباط با سرور', 'error');
                    $btn.prop('disabled', false).html('ذخیره درخواست');
                });
            });
        }
    };

    function esc(t) { if (!t) return ''; var d = document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }
    $(document).ready(function () { B2BRFQ.init(); });
})(jQuery);
