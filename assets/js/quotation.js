/**
 * B2B Quotation Management
 * Version: 1.0.0
 */
(function ($) {
    'use strict';

    window.B2BQuotation = {
        page: 1,

        init: function () {
            if ($('#quotation-search').length) this.initSearch();
            if ($('#quotation-form').length) this.initForm();
            if ($('#comparison-container').length) this.loadComparison();
        },

        // ==================== LIST ====================
        initSearch: function () {
            var self = this;
            $('#quotation-search').on('keyup', function () {
                clearTimeout(self._timer);
                self._timer = setTimeout(function () { self.page = 1; self.load(); }, 400);
            });
            $('#quotation-status').on('change', function () { self.page = 1; self.load(); });
            self.load();
        },

        load: function () {
            var self = this;
            var $c = $('#quotation-table-container');
            $c.html('<div style="text-align:center;padding:40px;"><div class="b2b-spinner-lg" style="margin:0 auto;"></div></div>');

            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_quotation_get_list',
                _b2b_nonce: b2bProcurement.nonce,
                search: $('#quotation-search').val() || '',
                status: $('#quotation-status').val() || '',
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
            var $c = $('#quotation-table-container');
            if (!d.items.length) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128176;</div><p>پیشنهادی یافت نشد</p><div><a href="/wp-admin/admin.php?page=b2b-quotation-add" class="b2b-btn b2b-btn-primary">افزودن پیشنهاد</a></div></div></div></div>');
                $('#quotation-count').text('۰ مورد');
                return;
            }

            var statusMap = { draft: ['پیش‌نویس', 'b2b-badge-default'], submitted: ['ارسال شده', 'b2b-badge-primary'], selected: ['انتخاب شده', 'b2b-badge-success'], rejected: ['رد شده', 'b2b-badge-danger'] };
            var h = '<div class="b2b-card"><div class="b2b-card-body" style="padding:0;overflow-x:auto;"><table class="b2b-table"><thead><tr>';
            h += '<th>درخواست</th><th>تامین‌کننده</th><th>وضعیت</th><th>جمع کل</th><th>تاریخ</th><th>عملیات</th>';
            h += '</tr></thead><tbody>';

            for (var i = 0; i < d.items.length; i++) {
                var x = d.items[i];
                var si = statusMap[x.status] || ['نامشخص', 'b2b-badge-default'];
                h += '<tr>';
                h += '<td><a href="/wp-admin/admin.php?page=b2b-rfq-detail&id=' + x.rfq_id + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost">' + esc(x.rfq_reference) + '</a></td>';
                h += '<td>' + esc(x.supplier_name) + '</td>';
                h += '<td><span class="b2b-badge ' + si[1] + '">' + si[0] + '</span></td>';
                h += '<td>' + toPersianNum(x.grand_total) + ' تومان</td>';
                h += '<td>' + esc(x.submitted_at || x.created_at) + '</td>';
                h += '<td style="white-space:nowrap;">';
                h += '<a href="/wp-admin/admin.php?page=b2b-quotation-detail&id=' + x.id + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="جزئیات">&#128065;</a> ';
                if (x.status === 'draft') {
                    h += '<button type="button" class="b2b-btn b2b-btn-sm b2b-btn-danger b2b-tooltip" data-tooltip="حذف" onclick="B2BQuotation.del(' + x.id + ')">&#128465;</button>';
                }
                h += '</td></tr>';
            }

            h += '</tbody></table></div></div>';
            $c.html(h);

            var pg = '<div class="b2b-table-pagination"><div class="b2b-pagination-info">' + toPersianNum(d.total) + ' مورد - صفحه ' + toPersianNum(d.page) + ' از ' + toPersianNum(d.pages) + '</div><div class="b2b-pagination-links">';
            if (d.page > 1) pg += '<button class="b2b-page-link" onclick="B2BQuotation.go(' + (d.page - 1) + ')">&laquo;</button>';
            for (var i = 1; i <= d.pages; i++) {
                if (i === d.page) pg += '<span class="b2b-page-link b2b-page-active">' + toPersianNum(i) + '</span>';
                else if (i === 1 || i === d.pages || Math.abs(i - d.page) <= 2) pg += '<button class="b2b-page-link" onclick="B2BQuotation.go(' + i + ')">' + toPersianNum(i) + '</button>';
            }
            if (d.page < d.pages) pg += '<button class="b2b-page-link" onclick="B2BQuotation.go(' + (d.page + 1) + ')">&raquo;</button>';
            pg += '</div></div>';
            $('#quotation-pagination').html(pg);
            $('#quotation-count').text(toPersianNum(d.total) + ' مورد');
        },

        go: function (p) { this.page = p; this.load(); },

        del: function (id) {
            if (!confirm('آیا از حذف این پیشنهاد اطمینان دارید؟')) return;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_quotation_delete', _b2b_nonce: b2bProcurement.nonce, quotation_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); B2BQuotation.load(); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        // ==================== FORM ====================
        initForm: function () {
            var self = this;
            $('#quotation-form').on('submit', function (e) {
                e.preventDefault();
                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).html('<span class="b2b-spinner"></span> ذخیره...');

                var items = [];
                $('#quotation-items-body tr').each(function () {
                    var $r = $(this);
                    items.push({
                        product_id: $r.find('select').val(),
                        product_name: $r.find('select option:selected').data('name') || '',
                        product_sku: $r.find('select option:selected').data('sku') || '',
                        unit_price: $r.find('[name$="[unit_price]"]').val(),
                        quantity: $r.find('[name$="[quantity]"]').val(),
                        delivery_days: $r.find('[name$="[delivery_days]"]').val(),
                        supplier_note: $r.find('[name$="[supplier_note]"]').val()
                    });
                });

                var $supplier = $('[name="supplier_id"] option:selected');
                var postData = {
                    action: 'b2b_quotation_save',
                    _b2b_nonce: b2bProcurement.nonce,
                    quotation_id: $('[name="quotation_id"]').val() || '',
                    rfq_id: $('[name="rfq_id"]').val(),
                    supplier_id: $('[name="supplier_id"]').val(),
                    supplier_name: $supplier.data('name') || $supplier.text(),
                    notes: $('[name="notes"]').val(),
                    items: JSON.stringify(items)
                };

                $.post(b2bProcurement.ajaxUrl, postData, function (r) {
                    if (r && r.success) {
                        B2BAdmin.toast(r.data.message, 'success');
                        setTimeout(function () { window.location.href = '/wp-admin/admin.php?page=b2b-quotations'; }, 1000);
                    } else {
                        B2BAdmin.toast(r.data.message || 'خطا', 'error');
                        $btn.prop('disabled', false).html('ذخیره پیشنهاد');
                    }
                }).fail(function () {
                    B2BAdmin.toast('خطا در ارتباط با سرور', 'error');
                    $btn.prop('disabled', false).html('ذخیره پیشنهاد');
                });
            });
        },

        // ==================== ITEMS ====================
        addItem: function () {
            var rfqId = $('#rfq-select').val();
            if (!rfqId) { B2BAdmin.toast('ابتدا درخواست خرید را انتخاب کنید', 'warning'); return; }

            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_rfq_get', _b2b_nonce: b2bProcurement.nonce, item_id: rfqId }, function (r) {
                if (r && r.success && r.data && r.data.products) {
                    var html = '';
                    r.data.products.forEach(function (p) {
                        html += '<tr>';
                        html += '<td><select class="b2b-select"><option value="' + p.product_id + '" data-name="' + p.product_name + '" data-sku="' + p.product_sku + '">' + p.product_name + '</option></select></td>';
                        html += '<td><input type="number" class="b2b-input" name="unit_price" min="0" step="0.01" value="0" /></td>';
                        html += '<td><input type="number" class="b2b-input" name="quantity" min="0" step="0.001" value="' + p.requested_qty + '" /></td>';
                        html += '<td><input type="number" class="b2b-input" name="delivery_days" min="0" value="0" /></td>';
                        html += '<td><input type="text" class="b2b-input" name="supplier_note" /></td>';
                        html += '<td class="b2b-text-muted">-</td>';
                        html += '<td><button type="button" class="b2b-btn b2b-btn-sm b2b-btn-danger" onclick="B2BQuotation.removeItem(this)">&#10005;</button></td>';
                        html += '</tr>';
                    });
                    $('#quotation-items-body').html(html);
                    self.updateGrandTotal();
                }
            }).fail(function () { B2BAdmin.toast('خطا در بارگذاری درخواست', 'error'); });
        },

        removeItem: function (btn) {
            $(btn).closest('tr').remove();
            this.updateGrandTotal();
        },

        updateGrandTotal: function () {
            var total = 0;
            $('#quotation-items-body tr').each(function () {
                var price = parseFloat($(this).find('[name="unit_price"]').val()) || 0;
                var qty = parseFloat($(this).find('[name="quantity"]').val()) || 0;
                total += price * qty;
                $(this).find('td:eq(5)').text(toPersianNum(total) + ' تومان');
            });
            $('#quotation-grand-total').text(toPersianNum(total));
        },

        // ==================== COMPARISON ====================
        loadComparison: function () {
            var rfqId = new URLSearchParams(window.location.search).get('rfq_id');
            if (!rfqId) { $('#comparison-container').html('<div class="b2b-card"><div class="b2b-card-body"><p>شناسه درخواست نامعتبر</p></div></div>'); return; }

            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_quotation_compare', _b2b_nonce: b2bProcurement.nonce, rfq_id: rfqId }, function (r) {
                if (r && r.success) B2BQuotation.renderComparison(r.data);
                else $('#comparison-container').html('<div class="b2b-card"><div class="b2b-card-body"><p>' + (r.data.message || 'خطا') + '</p></div></div>');
            }).fail(function () { $('#comparison-container').html('<div class="b2b-card"><div class="b2b-card-body"><p>خطا در بارگذاری مقایسه</p></div></div>'); });
        },

        renderComparison: function (d) {
            if (!d.quotations.length) {
                $('#comparison-container').html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><p>پیشنهادی برای مقایسه وجود ندارد</p></div></div></div>');
                return;
            }

            var h = '<div class="b2b-card"><div class="b2b-card-body" style="overflow-x:auto;"><table class="b2b-table"><thead><tr><th>محصول</th>';
            d.quotations.forEach(function (q) {
                h += '<th style="text-align:center;">' + esc(q.supplier_name) + '<br><small>' + toPersianNum(q.grand_total) + ' تومان</small></th>';
            });
            h += '</tr></thead><tbody>';

            d.comparison.forEach(function (row) {
                h += '<tr><td><strong>' + esc(row.product_name) + '</strong></td>';
                var prices = row.suppliers.map(function (s) { return s.unit_price; });
                var minPrice = Math.min.apply(null, prices);

                row.suppliers.forEach(function (s) {
                    var cls = (s.unit_price === minPrice) ? 'b2b-badge-success' : '';
                    h += '<td style="text-align:center;">';
                    h += '<div>' + toPersianNum(s.unit_price) + ' تومان</div>';
                    h += '<small>' + toPersianNum(s.quantity) + ' واحد - ' + toPersianNum(s.delivery_days) + ' روز</small>';
                    if (s.unit_price === minPrice) h += '<br><span class="b2b-badge ' + cls + '">کمترین قیمت</span>';
                    h += '</td>';
                });
                h += '</tr>';
            });

            h += '</tbody></table></div></div>';
            $('#comparison-container').html(h);
        },

        // ==================== SELECT WINNER ====================
        selectWinner: function (id) {
            if (!confirm('آیا از انتخاب این پیشنهاد به عنوان برنده اطمینان دارید؟')) return;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_quotation_select_winner', _b2b_nonce: b2bProcurement.nonce, quotation_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); setTimeout(function () { location.reload(); }, 1000); }
                else B2BAdmin.toast(r.data.message || 'خطا', 'error');
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        // ==================== INIT ====================
        init: function () {
            if ($('#quotation-search').length) this.initSearch();
            if ($('#quotation-form').length) this.initForm();
            if ($('#comparison-container').length) this.loadComparison();
        }
    };

    $(document).ready(function () { B2BQuotation.init(); });
})(jQuery);
