/**
 * Product Resources - Admin JS
 * @package B2B_Procurement
 */
(function ($) {
    'use strict';

    var PR = {
        list: null,
        idx: 0,

        init: function () {
            this.list = $('#b2b-pr-list');
            this.idx = this.list.find('.b2b-pr-card').length;

            this.initSortable();
            this.initAdd();
            this.initDelete();
            this.initUpload();
            this.initRemoveFile();
            this.initCollapseAll();
            this.initExpandAll();
        },

        initSortable: function () {
            this.list.sortable({
                handle: '.b2b-pr-drag-handle',
                placeholder: 'b2b-pr-placeholder',
                tolerance: 'pointer',
                update: function () {
                    PR.updateOrder();
                }
            });
        },

        updateOrder: function () {
            var order = [];
            this.list.find('.b2b-pr-card').each(function (i) {
                $(this).attr('data-index', i);
                order.push(i);
            });
            $('#b2b-pr-order').val(order.join(','));
        },

        initAdd: function () {
            var self = this;
            $('.b2b-pr-add-resource').on('click', function () {
                self.idx++;
                var html = self.getCardTemplate(self.idx);
                self.list.append(html).find('.b2b-pr-card').last().removeClass('collapsed').find('input[name*="[title]"]').focus();
                self.updateOrder();
            });
        },

        initDelete: function () {
            this.list.on('click', '.b2b-pr-delete-card', function () {
                if (confirm('آیا از حذف این منبع اطمینان دارید؟')) {
                    $(this).closest('.b2b-pr-card').fadeOut(200, function () {
                        $(this).remove();
                        PR.updateOrder();
                    });
                }
            });
        },

        initCollapseAll: function () {
            $('.b2b-pr-collapse-all').on('click', function () {
                PR.list.find('.b2b-pr-card').addClass('collapsed');
            });
            $('.b2b-pr-expand-all').on('click', function () {
                PR.list.find('.b2b-pr-card').removeClass('collapsed');
            });
        },

        initUpload: function () {
            this.list.on('click', '.b2b-pr-upload-file', function (e) {
                e.preventDefault();
                var $btn = $(this);
                var frame = wp.media({
                    title: 'انتخاب فایل',
                    button: { text: 'انتخاب' },
                    multiple: false,
                    library: { type: '' }
                });
                frame.on('select', function () {
                    var att = frame.state().get('selection').first().toJSON();
                    $btn.closest('.b2b-pr-file-wrap').find('.b2b-pr-file-id').val(att.id);
                    $btn.closest('.b2b-pr-file-wrap').find('.b2b-pr-file-preview').html(
                        '<a href="' + att.url + '" target="_blank" class="b2b-pr-file-link">' + att.filename + '</a>'
                    );
                    $btn.closest('.b2b-pr-file-wrap').find('.b2b-pr-remove-file').show();
                });
                frame.open();
            });

            this.list.on('click', '.b2b-pr-upload-thumb', function (e) {
                e.preventDefault();
                var $btn = $(this);
                var frame = wp.media({
                    title: 'انتخاب تصویر شاخص',
                    button: { text: 'انتخاب' },
                    multiple: false,
                    library: { type: 'image' }
                });
                frame.on('select', function () {
                    var att = frame.state().get('selection').first().toJSON();
                    var thumbUrl = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                    $btn.closest('.b2b-pr-thumb-wrap').find('.b2b-pr-thumb-id').val(att.id);
                    $btn.closest('.b2b-pr-thumb-wrap').find('.b2b-pr-thumb-preview').html(
                        '<img src="' + thumbUrl + '" class="b2b-pr-thumb-img" />'
                    );
                    $btn.closest('.b2b-pr-thumb-wrap').find('.b2b-pr-remove-thumb').show();
                });
                frame.open();
            });
        },

        initRemoveFile: function () {
            this.list.on('click', '.b2b-pr-remove-file', function () {
                var $wrap = $(this).closest('.b2b-pr-file-wrap');
                $wrap.find('.b2b-pr-file-id').val('');
                $wrap.find('.b2b-pr-file-preview').html('<span class="b2b-pr-no-file">فایلی انتخاب نشده</span>');
                $(this).hide();
            });

            this.list.on('click', '.b2b-pr-remove-thumb', function () {
                var $wrap = $(this).closest('.b2b-pr-thumb-wrap');
                $wrap.find('.b2b-pr-thumb-id').val('');
                $wrap.find('.b2b-pr-thumb-preview').html('<div class="b2b-pr-thumb-empty">&#128247;</div>');
                $(this).hide();
            });
        },

        getCardTemplate: function (idx) {
            var types = '<option value="pdf">PDF</option><option value="word">Word</option><option value="excel">Excel</option>';
            types += '<option value="ppt">PowerPoint</option><option value="zip">ZIP</option><option value="image">تصویر</option>';
            types += '<option value="video">ویدیو</option><option value="audio">صدا</option><option value="cad">CAD</option>';
            types += '<option value="dwg">DWG</option><option value="dxf">DXF</option><option value="step">STEP</option>';
            types += '<option value="stl">STL</option><option value="link">لینک خارجی</option><option value="custom">سفارشی</option>';

            return '<div class="b2b-pr-card" data-index="' + idx + '">' +
                '<div class="b2b-pr-card-header">' +
                    '<span class="b2b-pr-drag-handle" title="بکشید">&#9776;</span>' +
                    '<span class="b2b-pr-card-title">منبع جدید</span>' +
                    '<span class="b2b-pr-card-type">PDF</span>' +
                    '<span class="b2b-pr-card-status active">فعال</span>' +
                    '<span class="b2b-pr-card-toggle">&#9660;</span>' +
                '</div>' +
                '<div class="b2b-pr-card-body">' +
                    '<div class="b2b-pr-row">' +
                        '<div class="b2b-pr-field" style="flex:2;"><label>عنوان <span class="required">*</span></label>' +
                            '<input type="text" name="b2b_resources[' + idx + '][title]" class="regular-text" required placeholder="عنوان منبع" /></div>' +
                        '<div class="b2b-pr-field" style="flex:1;"><label>نوع فایل</label>' +
                            '<select name="b2b_resources[' + idx + '][file_type]">' + types + '</select></div>' +
                        '<div class="b2b-pr-field" style="flex:1;"><label>ترتیب</label>' +
                            '<input type="number" name="b2b_resources[' + idx + '][sort_order]" value="' + idx + '" min="0" style="width:80px;" /></div>' +
                        '<div class="b2b-pr-field" style="flex:1;"><label>وضعیت</label>' +
                            '<select name="b2b_resources[' + idx + '][active]"><option value="1">فعال</option><option value="0">غیرفعال</option></select></div>' +
                    '</div>' +
                    '<div class="b2b-pr-row"><div class="b2b-pr-field" style="flex:3;"><label>توضیحات</label>' +
                        '<textarea name="b2b_resources[' + idx + '][description]" rows="2" class="large-text" placeholder="توضیحات اختیاری"></textarea></div></div>' +
                    '<div class="b2b-pr-row">' +
                        '<div class="b2b-pr-field" style="flex:2;"><label>فایل ضمیمه</label>' +
                            '<div class="b2b-pr-file-wrap"><input type="hidden" name="b2b_resources[' + idx + '][file_id]" class="b2b-pr-file-id" value="0" />' +
                            '<div class="b2b-pr-file-preview"><span class="b2b-pr-no-file">فایلی انتخاب نشده</span></div>' +
                            '<button type="button" class="button b2b-pr-upload-file">انتخاب فایل</button>' +
                            '<button type="button" class="button b2b-pr-remove-file" style="display:none;">حذف</button></div></div>' +
                        '<div class="b2b-pr-field" style="flex:2;"><label>لینک خارجی</label>' +
                            '<input type="url" name="b2b_resources[' + idx + '][external_url]" class="regular-text" placeholder="https://example.com" /></div>' +
                    '</div>' +
                    '<div class="b2b-pr-row">' +
                        '<div class="b2b-pr-field" style="flex:1;"><label>تصویر شاخص</label>' +
                            '<div class="b2b-pr-thumb-wrap"><input type="hidden" name="b2b_resources[' + idx + '][thumb_id]" class="b2b-pr-thumb-id" value="0" />' +
                            '<div class="b2b-pr-thumb-preview"><div class="b2b-pr-thumb-empty">&#128247;</div></div>' +
                            '<button type="button" class="button b2b-pr-upload-thumb">انتخاب تصویر</button>' +
                            '<button type="button" class="button b2b-pr-remove-thumb" style="display:none;">حذف</button></div></div>' +
                        '<div class="b2b-pr-field" style="flex:1;"><label>&nbsp;</label>' +
                            '<button type="button" class="button b2b-pr-delete-card" style="color:#d63638;border-color:#d63638;">&#128465; حذف منبع</button></div>' +
                    '</div>' +
                '</div></div>';
        }
    };

    $(document).ready(function () { PR.init(); });
})(jQuery);
