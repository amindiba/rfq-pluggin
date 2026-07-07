/**
 * B2B Geography - Iran Geographic Engine
 * Version: 1.0.0
 */
(function ($) {
    'use strict';

    window.B2BGeo = {
        page: 1,
        currentType: 'province',
        _timer: null,

        init: function () {
            var self = this;
            var isCities = $('#geo-province').length > 0;
            self.currentType = isCities ? 'city' : 'province';
            self.loadData();

            $('#geo-search').on('keyup', function () {
                clearTimeout(self._timer);
                self._timer = setTimeout(function () { self.page = 1; self.loadData(); }, 400);
            });
            $('#geo-status, #geo-province').on('change', function () {
                self.page = 1;
                self.loadData();
            });

            $('#import-type').on('change', function () {
                var t = $(this).val();
                if (t === 'provinces') {
                    $('#import-format').html('فرمت: نام فارسی، نام انگلیسی، کد، وضعیت (اختیاری)، ترتیب (اختیاری)');
                } else {
                    $('#import-format').html('فرمت: کد استان، نام فارسی، نام انگلیسی، کد شهر، وضعیت (اختیاری)، ترتیب (اختیاری)');
                }
            });
            $('#import-type').trigger('change');
        },

        // ==================== LOAD ====================
        loadData: function () {
            var self = this;
            var $c = $('#geo-table-container');
            $c.html('<div style="text-align:center;padding:40px;"><div class="b2b-spinner-lg" style="margin:0 auto;"></div><p style="color:#64748b;margin-top:16px;">در حال بارگذاری...</p></div>');

            var action = self.currentType === 'province' ? 'b2b_geo_get_provinces' : 'b2b_geo_get_cities';
            var postData = {
                action: action,
                _b2b_nonce: b2bProcurement.nonce,
                search: $('#geo-search').val() || '',
                status: $('#geo-status').val() || '',
                page: self.page,
                per_page: 20
            };
            if (self.currentType === 'city') {
                postData.province_id = $('#geo-province').val() || '';
            }

            $.post(b2bProcurement.ajaxUrl, postData, function (r) {
                if (r && r.success && r.data && r.data.items) {
                    self.renderTable(r.data);
                    self.renderPag(r.data);
                    $('#geo-count').text(toPersianNum(r.data.total) + ' مورد');
                } else {
                    var msg = (r && r.data && r.data.message) ? r.data.message : 'خطا در بارگذاری';
                    $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><p class="b2b-empty-state-text">' + msg + '</p></div></div></div>');
                }
            }).fail(function (xhr, status, error) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><p class="b2b-empty-state-text">خطا: ' + error + '</p></div></div></div>');
            });
        },

        // ==================== RENDER TABLE ====================
        renderTable: function (d) {
            var self = this;
            var $c = $('#geo-table-container');
            var isP = self.currentType === 'province';

            if (!d.items.length) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><p class="b2b-empty-state-text">هیچ موردی یافت نشد.</p><div class="b2b-empty-state-action"><button class="b2b-btn b2b-btn-primary" onclick="B2BGeo.openCreate()">&#10010; افزودن</button></div></div></div></div>');
                return;
            }

            var h = '<div class="b2b-card"><div class="b2b-card-body" style="padding:0;overflow-x:auto;"><table class="b2b-table"><thead><tr>';
            h += '<th class="b2b-col-check"><input type="checkbox" class="b2b-check-all" /></th>';
            h += '<th>نام فارسی</th><th>نام انگلیسی</th><th>کد</th>';
            if (!isP) h += '<th>استان</th>';
            h += '<th>ترتیب</th><th>وضعیت</th><th>عملیات</th>';
            h += '</tr></thead><tbody>';

            for (var i = 0; i < d.items.length; i++) {
                var x = d.items[i];
                var sc = x.status === 'active' ? 'b2b-status-active' : 'b2b-status-inactive';
                var sl = x.status === 'active' ? 'فعال' : 'غیرفعال';
                var del = x.deleted_at !== null;

                h += '<tr' + (del ? ' style="opacity:0.5;"' : '') + '>';
                h += '<td class="b2b-col-check"><input type="checkbox" class="b2b-row-check" value="' + x.id + '" /></td>';
                h += '<td><strong>' + esc(x.name_fa) + '</strong></td>';
                h += '<td>' + esc(x.name_en) + '</td>';
                h += '<td><span class="b2b-badge b2b-badge-primary">' + esc(x.code) + '</span></td>';
                if (!isP) h += '<td>' + esc(x.province_name || '-') + '</td>';
                h += '<td>' + toPersianNum(x.sort_order) + '</td>';
                h += '<td><span class="b2b-status ' + sc + '">' + sl + '</span></td>';
                h += '<td>';

                if (del) {
                    h += '<button class="b2b-btn b2b-btn-sm b2b-btn-secondary b2b-tooltip" data-tooltip="بازیابی" onclick="B2BGeo.restore(' + x.id + ')">&#8630;</button> ';
                    h += '<button class="b2b-btn b2b-btn-sm b2b-btn-danger b2b-tooltip" data-tooltip="حذف دائمی" onclick="B2BGeo.permDel(' + x.id + ')">&#10005;</button>';
                } else {
                    h += '<button class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="ویرایش" onclick="B2BGeo.openEdit(' + x.id + ')">&#9998;</button> ';
                    h += '<button class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="تغییر وضعیت" onclick="B2BGeo.toggle(' + x.id + ')">&#8644;</button> ';
                    h += '<button class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="حذف" onclick="B2BGeo.openDel(' + x.id + ')">&#128465;</button>';
                }
                h += '</td></tr>';
            }

            h += '</tbody></table></div></div>';
            $c.html(h);
        },

        // ==================== PAGINATION ====================
        renderPag: function (d) {
            var $p = $('#geo-pagination');
            if (d.pages <= 1) { $p.html(''); return; }
            var h = '<div class="b2b-table-pagination"><div class="b2b-pagination-info">صفحه ' + toPersianNum(d.page) + ' از ' + toPersianNum(d.pages) + '</div><div class="b2b-pagination-links">';
            if (d.page > 1) h += '<button class="b2b-page-link" onclick="B2BGeo.go(' + (d.page - 1) + ')">&laquo;</button>';
            for (var i = 1; i <= d.pages; i++) {
                if (i === d.page) h += '<span class="b2b-page-link b2b-page-active">' + toPersianNum(i) + '</span>';
                else if (i === 1 || i === d.pages || Math.abs(i - d.page) <= 2) h += '<button class="b2b-page-link" onclick="B2BGeo.go(' + i + ')">' + toPersianNum(i) + '</button>';
                else if (Math.abs(i - d.page) === 3) h += '<span class="b2b-page-link">...</span>';
            }
            if (d.page < d.pages) h += '<button class="b2b-page-link" onclick="B2BGeo.go(' + (d.page + 1) + ')">&raquo;</button>';
            h += '</div></div>';
            $p.html(h);
        },

        go: function (p) { this.page = p; this.loadData(); },

        // ==================== MODAL HELPERS ====================
        openModal: function (id) { $('#' + id).addClass('is-active'); $('body').addClass('b2b-modal-open'); },
        closeModal: function (id) { $('#' + id).removeClass('is-active'); $('body').removeClass('b2b-modal-open'); },

        openCreate: function () {
            var $f = $('#geo-create-form')[0];
            if ($f) $f.reset();
            $f.querySelectorAll('.b2b-validation-error').forEach(function(e){ e.remove(); });
            $f.querySelectorAll('.b2b-field-invalid').forEach(function(e){ e.classList.remove('b2b-field-invalid'); });
            this.openModal('geo-create-modal');
        },

        openEdit: function (id) {
            var self = this;
            var action = self.currentType === 'province' ? 'b2b_geo_get_provinces' : 'b2b_geo_get_cities';
            var postData = { action: action, _b2b_nonce: b2bProcurement.nonce, per_page: 9999 };
            if (self.currentType === 'city') postData.province_id = '';

            $.post(b2bProcurement.ajaxUrl, postData, function (r) {
                if (r && r.success && r.data && r.data.items) {
                    var item = null;
                    for (var i = 0; i < r.data.items.length; i++) {
                        var cur = self.currentType === 'province' ? r.data.items[i] : r.data.items[i];
                        if (cur.id == id) { item = cur; break; }
                    }
                    if (item) {
                        var $f = $('#geo-edit-form');
                        $f.find('[name="item_id"]').val(item.id);
                        $f.find('[name="name_fa"]').val(item.name_fa);
                        $f.find('[name="name_en"]').val(item.name_en);
                        $f.find('[name="code"]').val(item.code);
                        $f.find('[name="sort_order"]').val(item.sort_order);
                        $f.find('[name="status"]').val(item.status);
                        if (self.currentType === 'city') {
                            $f.find('[name="province_id"]').val(item.province_id);
                        }
                        self.openModal('geo-edit-modal');
                    }
                }
            }).fail(function () { B2BAdmin.toast('خطا در بارگذاری اطلاعات', 'error'); });
        },

        openDel: function (id) { $('#delete-geo-id').val(id); this.openModal('geo-delete-modal'); },
        openImportModal: function () { this.openModal('geo-import-modal'); },

        // ==================== CRUD ====================
        saveCreate: function () {
            var self = this;
            var $f = $('#geo-create-form');
            var action = self.currentType === 'province' ? 'b2b_geo_create_province' : 'b2b_geo_create_city';
            var postData = { action: action, _b2b_nonce: b2bProcurement.nonce };

            $f.serializeArray().forEach(function (f) { postData[f.name] = f.value; });

            if (!postData.name_fa || !postData.name_en || !postData.code) {
                B2BAdmin.toast('فیلدهای الزامی را پر کنید.', 'error'); return;
            }
            if (self.currentType === 'city' && !postData.province_id) {
                B2BAdmin.toast('استان را انتخاب کنید.', 'error'); return;
            }

            $.post(b2bProcurement.ajaxUrl, postData, function (r) {
                if (r && r.success) {
                    B2BAdmin.toast(r.data.message, 'success');
                    self.closeModal('geo-create-modal');
                    self.loadData();
                } else {
                    B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error');
                }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        saveEdit: function () {
            var self = this;
            var $f = $('#geo-edit-form');
            var action = self.currentType === 'province' ? 'b2b_geo_update_province' : 'b2b_geo_update_city';
            var postData = { action: action, _b2b_nonce: b2bProcurement.nonce };

            $f.serializeArray().forEach(function (f) { postData[f.name] = f.value; });

            if (!postData.name_fa || !postData.name_en || !postData.code) {
                B2BAdmin.toast('فیلدهای الزامی را پر کنید.', 'error'); return;
            }

            $.post(b2bProcurement.ajaxUrl, postData, function (r) {
                if (r && r.success) {
                    B2BAdmin.toast(r.data.message, 'success');
                    self.closeModal('geo-edit-modal');
                    self.loadData();
                } else {
                    B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error');
                }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        confirmDel: function () {
            var self = this;
            var id = $('#delete-geo-id').val();
            var action = self.currentType === 'province' ? 'b2b_geo_delete_province' : 'b2b_geo_delete_city';

            $.post(b2bProcurement.ajaxUrl, { action: action, _b2b_nonce: b2bProcurement.nonce, item_id: id }, function (r) {
                if (r && r.success) {
                    B2BAdmin.toast(r.data.message, 'success');
                    self.closeModal('geo-delete-modal');
                    self.loadData();
                } else {
                    B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error');
                }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        restore: function (id) {
            var self = this;
            var action = self.currentType === 'province' ? 'b2b_geo_restore_province' : 'b2b_geo_restore_city';
            $.post(b2bProcurement.ajaxUrl, { action: action, _b2b_nonce: b2bProcurement.nonce, item_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); self.loadData(); }
                else { B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error'); }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        toggle: function (id) {
            var self = this;
            var action = self.currentType === 'province' ? 'b2b_geo_toggle_province' : 'b2b_geo_toggle_city';
            $.post(b2bProcurement.ajaxUrl, { action: action, _b2b_nonce: b2bProcurement.nonce, item_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); self.loadData(); }
                else { B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error'); }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        permDel: function (id) {
            if (!confirm('آیا از حذف دائمی اطمینان دارید؟')) return;
            var self = this;
            var action = self.currentType === 'province' ? 'b2b_geo_delete_province' : 'b2b_geo_delete_city';
            $.post(b2bProcurement.ajaxUrl, { action: action, _b2b_nonce: b2bProcurement.nonce, item_id: id, permanent: true }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); self.loadData(); }
                else { B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error'); }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        // ==================== IMPORT ====================
        doImport: function () {
            var self = this;
            var $f = $('#geo-import-form')[0];
            var formData = new FormData($f);
            formData.append('action', 'b2b_geo_import_csv');
            formData.append('_b2b_nonce', b2bProcurement.nonce);

            $.ajax({
                url: b2bProcurement.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (r) {
                    if (r && r.success) {
                        B2BAdmin.toast(r.data.message, 'success');
                        self.closeModal('geo-import-modal');
                        self.loadData();
                    } else {
                        B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error');
                    }
                },
                error: function () { B2BAdmin.toast('خطا در آپلود فایل', 'error'); }
            });
        },

        // ==================== EXPORT ====================
        exportCSV: function (type) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = b2bProcurement.ajaxUrl;

            var fields = {
                'action': 'b2b_geo_export_csv',
                '_b2b_nonce': b2bProcurement.nonce,
                'export_type': type,
                'status': $('#geo-status').val() || ''
            };

            for (var k in fields) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = k;
                input.value = fields[k];
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    };

    function esc(t) { if (!t) return ''; var d = document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') { $('.b2b-modal.is-active').removeClass('is-active'); $('body').removeClass('b2b-modal-open'); }
    });
    $(document).on('click', '.b2b-modal-overlay, .b2b-modal-cancel, .b2b-modal-close', function () {
        $(this).closest('.b2b-modal').removeClass('is-active');
        $('body').removeClass('b2b-modal-open');
    });

    $(document).ready(function () {
        if ($('#geo-table-container').length) B2BGeo.init();
    });

})(jQuery);
