/**
 * B2B Master Data - Measurement Units
 * Version: 1.1.0
 */
(function ($) {
    'use strict';

    window.B2BUnits = {

        page: 1,

        init: function () {
            var self = this;
            self.loadUnits();

            $('#units-search').on('keyup', function () {
                clearTimeout(self._timer);
                self._timer = setTimeout(function () { self.page = 1; self.loadUnits(); }, 400);
            });

            $('#units-status-filter').on('change', function () {
                self.page = 1;
                self.loadUnits();
            });
        },

        // ==================== LOAD ====================
        loadUnits: function () {
            var self = this;
            var $c = $('#units-table-container');
            $c.html('<div style="text-align:center;padding:40px;"><div class="b2b-spinner-lg" style="margin:0 auto;"></div><p style="color:#64748b;margin-top:16px;">در حال بارگذاری...</p></div>');

            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_md_get_units',
                _b2b_nonce: b2bProcurement.nonce,
                search: $('#units-search').val() || '',
                status: $('#units-status-filter').val() || '',
                page: self.page,
                per_page: 20
            }, function (r) {
                if (r && r.success && r.data && r.data.items) {
                    self.renderTable(r.data);
                    self.renderPag(r.data);
                    $('#units-count').text(toPersianNum(r.data.total) + ' مورد');
                } else {
                    var msg = (r && r.data && r.data.message) ? r.data.message : 'خطا در بارگذاری';
                    $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#9888;</div><p class="b2b-empty-state-text">' + msg + '</p></div></div></div>');
                }
            }).fail(function (xhr, status, error) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#9888;</div><p class="b2b-empty-state-text">خطا در ارتباط با سرور: ' + error + '</p></div></div></div>');
            });
        },

        // ==================== RENDER TABLE ====================
        renderTable: function (d) {
            var $c = $('#units-table-container');
            if (!d.items.length) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128203;</div><p class="b2b-empty-state-text">هیچ واحدی یافت نشد.</p><div class="b2b-empty-state-action"><button class="b2b-btn b2b-btn-primary" onclick="B2BUnits.openCreate()">افزودن واحد</button></div></div></div></div>');
                return;
            }

            var h = '<div class="b2b-card"><div class="b2b-card-body" style="padding:0;overflow-x:auto;"><table class="b2b-table"><thead><tr>';
            h += '<th class="b2b-col-check"><input type="checkbox" class="b2b-check-all" /></th>';
            h += '<th>عنوان</th><th>نام اختصاری</th><th>توضیحات</th><th>ترتیب</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th>';
            h += '</tr></thead><tbody>';

            for (var i = 0; i < d.items.length; i++) {
                var x = d.items[i];
                var sc = x.status === 'active' ? 'b2b-status-active' : 'b2b-status-inactive';
                var sl = x.status === 'active' ? 'فعال' : 'غیرفعال';
                var del = x.deleted_at !== null;

                h += '<tr' + (del ? ' style="opacity:0.5;"' : '') + '>';
                h += '<td class="b2b-col-check"><input type="checkbox" class="b2b-row-check" value="' + x.id + '" /></td>';
                h += '<td><strong>' + esc(x.title) + '</strong></td>';
                h += '<td><span class="b2b-badge b2b-badge-primary">' + esc(x.short_name) + '</span></td>';
                h += '<td>' + esc(x.description || '-') + '</td>';
                h += '<td>' + toPersianNum(x.sort_order) + '</td>';
                h += '<td><span class="b2b-status ' + sc + '">' + sl + '</span></td>';
                h += '<td>' + fmtDate(x.created_at) + '</td>';
                h += '<td>';

                if (del) {
                    h += '<button class="b2b-btn b2b-btn-sm b2b-btn-secondary b2b-tooltip" data-tooltip="بازیابی واحد حذف شده" onclick="B2BUnits.restore(' + x.id + ')">&#8630;</button> ';
                    h += '<button class="b2b-btn b2b-btn-sm b2b-btn-danger b2b-tooltip" data-tooltip="حذف دائمی واحد" onclick="B2BUnits.permDel(' + x.id + ')">&#10005;</button>';
                } else {
                    h += '<button class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="ویرایش اطلاعات واحد" onclick="B2BUnits.openEdit(' + x.id + ')">&#9998;</button> ';
                    h += '<button class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="تغییر وضعیت فعال/غیرفعال" onclick="B2BUnits.toggle(' + x.id + ')">&#8644;</button> ';
                    h += '<button class="b2b-btn b2b-btn-sm b2b-btn-ghost b2b-tooltip" data-tooltip="انتقال به زباله‌دان" onclick="B2BUnits.openDel(' + x.id + ')">&#128465;</button>';
                }
                h += '</td></tr>';
            }

            h += '</tbody></table></div></div>';
            $c.html(h);
        },

        // ==================== PAGINATION ====================
        renderPag: function (d) {
            var $p = $('#units-pagination');
            if (d.pages <= 1) { $p.html(''); return; }
            var self = this;
            var h = '<div class="b2b-table-pagination"><div class="b2b-pagination-info">صفحه ' + toPersianNum(d.page) + ' از ' + toPersianNum(d.pages) + '</div><div class="b2b-pagination-links">';

            if (d.page > 1) h += '<button class="b2b-page-link" onclick="B2BUnits.go(' + (d.page - 1) + ')">&laquo;</button>';
            for (var i = 1; i <= d.pages; i++) {
                if (i === d.page) h += '<span class="b2b-page-link b2b-page-active">' + toPersianNum(i) + '</span>';
                else if (i === 1 || i === d.pages || Math.abs(i - d.page) <= 2) h += '<button class="b2b-page-link" onclick="B2BUnits.go(' + i + ')">' + toPersianNum(i) + '</button>';
                else if (Math.abs(i - d.page) === 3) h += '<span class="b2b-page-link">...</span>';
            }
            if (d.page < d.pages) h += '<button class="b2b-page-link" onclick="B2BUnits.go(' + (d.page + 1) + ')">&raquo;</button>';
            h += '</div></div>';
            $p.html(h);
        },

        go: function (p) { this.page = p; this.loadUnits(); },

        // ==================== VALIDATION ====================
        validateShortName: function (val) {
            // Only English letters, no numbers, no spaces, no symbols
            return /^[a-zA-Z]+$/.test(val);
        },

        // ==================== CREATE ====================
        openCreate: function () {
            var $f = $('#unit-create-form')[0];
            if ($f) $f.reset();
            $('#unit-create-modal').addClass('is-active');
            $('body').addClass('b2b-modal-open');
        },

        saveCreate: function () {
            var self = this;
            var $f = $('#unit-create-form');
            var title = $f.find('[name="title"]').val().trim();
            var short_name = $f.find('[name="short_name"]').val().trim();

            // Clear previous errors
            $f.find('.b2b-validation-error').remove();
            $f.find('.b2b-field-invalid').removeClass('b2b-field-invalid');

            if (!title) {
                B2BAdmin.toast('عنوان الزامی است.', 'error');
                return;
            }
            if (!short_name) {
                B2BAdmin.toast('نام اختصاری الزامی است.', 'error');
                return;
            }
            if (!self.validateShortName(short_name)) {
                B2BAdmin.toast('نام اختصاری باید فقط شامل حروف انگلیسی باشد (بدون عدد، فاصله یا علامت)', 'error');
                $f.find('[name="short_name"]').addClass('b2b-field-invalid');
                $f.find('[name="short_name"]').after('<p class="b2b-validation-error">فقط حروف انگلیسی مجاز است (a-z, A-Z)</p>');
                return;
            }

            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_md_create_unit',
                _b2b_nonce: b2bProcurement.nonce,
                title: title,
                short_name: short_name,
                description: $f.find('[name="description"]').val(),
                sort_order: $f.find('[name="sort_order"]').val() || 0,
                status: $f.find('[name="status"]').val() || 'active'
            }, function (r) {
                if (r && r.success) {
                    B2BAdmin.toast(r.data.message, 'success');
                    self.closeModal('unit-create-modal');
                    self.loadUnits();
                } else {
                    B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا در ذخیره‌سازی', 'error');
                }
            }).fail(function () {
                B2BAdmin.toast('خطا در ارتباط با سرور', 'error');
            });
        },

        // ==================== EDIT ====================
        openEdit: function (id) {
            var self = this;
            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_md_get_units',
                _b2b_nonce: b2bProcurement.nonce,
                per_page: 1000
            }, function (r) {
                if (r && r.success && r.data && r.data.items) {
                    var unit = null;
                    for (var i = 0; i < r.data.items.length; i++) {
                        if (r.data.items[i].id == id) { unit = r.data.items[i]; break; }
                    }
                    if (unit) {
                        var $f = $('#unit-edit-form');
                        $f.find('[name="unit_id"]').val(unit.id);
                        $f.find('[name="title"]').val(unit.title);
                        $f.find('[name="short_name"]').val(unit.short_name);
                        $f.find('[name="description"]').val(unit.description);
                        $f.find('[name="sort_order"]').val(unit.sort_order);
                        $f.find('[name="status"]').val(unit.status);
                        $('#unit-edit-modal').addClass('is-active');
                        $('body').addClass('b2b-modal-open');
                    }
                }
            }).fail(function () { B2BAdmin.toast('خطا در بارگذاری اطلاعات', 'error'); });
        },

        saveEdit: function () {
            var self = this;
            var $f = $('#unit-edit-form');
            var title = $f.find('[name="title"]').val().trim();
            var short_name = $f.find('[name="short_name"]').val().trim();

            // Clear previous errors
            $f.find('.b2b-validation-error').remove();
            $f.find('.b2b-field-invalid').removeClass('b2b-field-invalid');

            if (!title) {
                B2BAdmin.toast('عنوان الزامی است.', 'error');
                return;
            }
            if (!short_name) {
                B2BAdmin.toast('نام اختصاری الزامی است.', 'error');
                return;
            }
            if (!self.validateShortName(short_name)) {
                B2BAdmin.toast('نام اختصاری باید فقط شامل حروف انگلیسی باشد', 'error');
                $f.find('[name="short_name"]').addClass('b2b-field-invalid');
                $f.find('[name="short_name"]').after('<p class="b2b-validation-error">فقط حروف انگلیسی مجاز است (a-z, A-Z)</p>');
                return;
            }

            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_md_update_unit',
                _b2b_nonce: b2bProcurement.nonce,
                unit_id: $f.find('[name="unit_id"]').val(),
                title: title,
                short_name: short_name,
                description: $f.find('[name="description"]').val(),
                sort_order: $f.find('[name="sort_order"]').val() || 0,
                status: $f.find('[name="status"]').val() || 'active'
            }, function (r) {
                if (r && r.success) {
                    B2BAdmin.toast(r.data.message, 'success');
                    self.closeModal('unit-edit-modal');
                    self.loadUnits();
                } else {
                    B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا در بروزرسانی', 'error');
                }
            }).fail(function () {
                B2BAdmin.toast('خطا در ارتباط با سرور', 'error');
            });
        },

        // ==================== DELETE ====================
        openDel: function (id) {
            $('#delete-unit-id').val(id);
            $('#unit-delete-modal').addClass('is-active');
            $('body').addClass('b2b-modal-open');
        },

        confirmDel: function () {
            var self = this;
            var id = $('#delete-unit-id').val();
            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_md_delete_unit',
                _b2b_nonce: b2bProcurement.nonce,
                unit_id: id
            }, function (r) {
                if (r && r.success) {
                    B2BAdmin.toast(r.data.message, 'success');
                    self.closeModal('unit-delete-modal');
                    self.loadUnits();
                } else {
                    B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا در حذف', 'error');
                }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        // ==================== RESTORE ====================
        restore: function (id) {
            var self = this;
            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_md_restore_unit',
                _b2b_nonce: b2bProcurement.nonce,
                unit_id: id
            }, function (r) {
                if (r && r.success) {
                    B2BAdmin.toast(r.data.message, 'success');
                    self.loadUnits();
                } else {
                    B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا در بازیابی', 'error');
                }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        // ==================== TOGGLE STATUS ====================
        toggle: function (id) {
            var self = this;
            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_md_toggle_unit',
                _b2b_nonce: b2bProcurement.nonce,
                unit_id: id
            }, function (r) {
                if (r && r.success) {
                    B2BAdmin.toast(r.data.message, 'success');
                    self.loadUnits();
                } else {
                    B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error');
                }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        // ==================== PERMANENT DELETE ====================
        permDel: function (id) {
            if (!confirm('آیا از حذف دائمی اطمینان دارید؟')) return;
            var self = this;
            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_md_delete_unit',
                _b2b_nonce: b2bProcurement.nonce,
                unit_id: id,
                permanent: true
            }, function (r) {
                if (r && r.success) {
                    B2BAdmin.toast(r.data.message, 'success');
                    self.loadUnits();
                } else {
                    B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error');
                }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        // ==================== MODAL HELPERS ====================
        closeModal: function (id) {
            $('#' + id).removeClass('is-active');
            $('body').removeClass('b2b-modal-open');
        }
    };

    // ==================== HELPERS ====================
    function esc(t) { if (!t) return ''; var d = document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }

    // Persian numbers
    function toPersianNum(num) {
        if (num === null || num === undefined) return '';
        var persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return String(num).replace(/\d/g, function(d) { return persianDigits[parseInt(d)]; });
    }
    function fmtDate(s) { if (!s) return '-'; return new Date(s).toLocaleDateString('fa-IR'); }

    // Close modals on ESC
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('.b2b-modal.is-active').removeClass('is-active');
            $('body').removeClass('b2b-modal-open');
        }
    });

    $(document).on('click', '.b2b-modal-overlay, .b2b-modal-cancel, .b2b-modal-close', function () {
        $(this).closest('.b2b-modal').removeClass('is-active');
        $('body').removeClass('b2b-modal-open');
    });

    $(document).ready(function () {
        if ($('#units-table-container').length) {
            B2BUnits.init();
        }
    });

})(jQuery);
