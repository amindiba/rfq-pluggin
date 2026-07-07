/**
 * Dynamic Specifications - Admin JS
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Spec list sortable
        if ($('#b2b-spec-list').length) {
            $('#b2b-spec-list').sortable({
                handle: '.b2b-spec-drag-handle',
                placeholder: 'b2b-spec-placeholder',
                tolerance: 'pointer'
            });
        }

        // Add spec field
        $('#b2b-spec-add-btn').on('click', function () {
            var defId = $('#b2b-spec-list').data('definition-id');
            if (!defId) { alert('ابتدا تعریف محصول را انتخاب کنید'); return; }
            self_addNewField(defId);
        });

        // Delete spec
        $('#b2b-spec-list').on('click', '.b2b-spec-delete', function (e) {
            e.stopPropagation();
            if (!confirm('آیا از حذف این فیلد اطمینان دارید؟')) return;
            var $card = $(this).closest('.b2b-spec-card');
            var specId = $card.data('spec-id');

            if (specId) {
                $.post(b2bSpec.ajaxUrl, {
                    action: 'b2b_spec_delete',
                    _b2b_nonce: b2bSpec.nonce,
                    spec_id: specId
                }, function (r) {
                    if (r && r.success) {
                        $card.fadeOut(200, function () { $(this).remove(); });
                    } else {
                        alert(r.data ? r.data.message : 'خطا');
                    }
                });
            } else {
                $card.fadeOut(200, function () { $(this).remove(); });
            }
        });

        // Save all specs
        $('#b2b-spec-list').on('click', '.b2b-spec-save', function () {
            saveSpecs();
        });

        // Toggle collapse
        $('#b2b-spec-list').on('click', '.b2b-spec-card-header', function (e) {
            if ($(e.target).is('input, select, .b2b-spec-drag-handle, .b2b-spec-delete')) return;
            $(this).closest('.b2b-spec-card').toggleClass('collapsed');
        });

        // Add option
        $('#b2b-spec-list').on('click', '.b2b-spec-option-add', function () {
            var $list = $(this).prev('.b2b-spec-options-list');
            var idx = $(this).data('idx');
            var oidx = $list.find('.b2b-spec-option-item').length;
            $list.append(
                '<div class="b2b-spec-option-item" data-oidx="' + oidx + '">' +
                '<span class="b2b-spec-drag-handle" style="cursor:grab;">&#9776;</span>' +
                '<input type="text" name="specs[' + idx + '][options][' + oidx + ']" value="" placeholder="مقدار گزینه" style="width:250px;" />' +
                '<button type="button" class="b2b-spec-option-remove">&#10005;</button></div>'
            );
        });

        // Remove option
        $('#b2b-spec-list').on('click', '.b2b-spec-option-remove', function () {
            $(this).closest('.b2b-spec-option-item').remove();
        });

        // Auto-generate field_key from label
        $('#b2b-spec-list').on('keyup', 'input[name*="[label]"]', function () {
            var $card = $(this).closest('.b2b-spec-card');
            var slug = $(this).val()
                .toLowerCase()
                .replace(/[^\w\u0600-\u06FF\s-]/g, '')
                .replace(/\s+/g, '_')
                .replace(/_+/g, '_')
                .trim();
            $card.find('input[name*="[field_key]"]').val(slug);
        });

        // Load dynamic fields on product page
        if ($('#b2b-product-definition-id').length) {
            loadDynamicFields();
            $('#b2b-product-definition-id').on('change', function () {
                loadDynamicFields();
            });
        }

        // Save on Ctrl+S
        $(document).on('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveSpecs();
            }
        });
    });

    var specCounter = 100;

    function self_addNewField(defId) {
        specCounter++;
        var idx = specCounter;
        var types = b2bSpec.types;
        var typeOptions = '';
        for (var k in types) {
            typeOptions += '<option value="' + k + '">' + types[k]['label'] + '</option>';
        }

        var html = '<div class="b2b-spec-card" data-spec-id="0" data-index="' + idx + '">' +
            '<div class="b2b-spec-card-header">' +
            '<span class="b2b-spec-drag-handle">&#9776;</span>' +
            '<span class="b2b-spec-card-title">فیلد جدید</span>' +
            '<span class="b2b-spec-card-type">متن</span>' +
            '<span class="b2b-spec-card-toggle">&#9660;</span>' +
            '<button type="button" class="b2b-spec-delete">&#128465;</button>' +
            '</div>' +
            '<div class="b2b-spec-card-body">' +
            '<div class="b2b-pr-row">' +
            '<div class="b2b-pr-field" style="flex:2;"><label>عنوان <span style="color:#EF4444;">*</span></label>' +
            '<input type="text" name="specs[' + idx + '][label]" class="regular-text" required placeholder="عنوان فیلد" /></div>' +
            '<div class="b2b-pr-field" style="flex:1;"><label>کلید <span style="color:#EF4444;">*</span></label>' +
            '<input type="text" name="specs[' + idx + '][field_key]" class="regular-text" required pattern="[a-z0-9_-]+" /></div>' +
            '<div class="b2b-pr-field" style="flex:1;"><label>نوع</label>' +
            '<select name="specs[' + idx + '][field_type]" class="spec-type-select">' + typeOptions + '</select></div>' +
            '</div>' +
            '<div class="b2b-pr-row">' +
            '<div class="b2b-pr-field" style="flex:3;"><label>توضیح</label>' +
            '<input type="text" name="specs[' + idx + '][description]" class="regular-text" placeholder="راهنما" /></div>' +
            '<div class="b2b-pr-field" style="flex:2;"><label>Placeholder</label>' +
            '<input type="text" name="specs[' + idx + '][placeholder]" class="regular-text" /></div>' +
            '</div>' +
            '<div class="b2b-pr-row">' +
            '<div class="b2b-pr-field" style="flex:1;"><label>مقدار پیش‌فرض</label>' +
            '<input type="text" name="specs[' + idx + '][default_value]" class="regular-text" /></div>' +
            '<div class="b2b-pr-field" style="flex:1;"><label>ترتیب</label>' +
            '<input type="number" name="specs[' + idx + '][sort_order]" value="' + idx + '" min="0" style="width:80px;" /></div>' +
            '</div>' +
            '<div class="b2b-pr-row">' +
            '<div class="b2b-pr-field"><label><input type="checkbox" name="specs[' + idx + '][is_required]" value="1" /> اجباری</label></div>' +
            '<div class="b2b-pr-field"><label><input type="checkbox" name="specs[' + idx + '][is_searchable]" value="1" /> قابل جستجو</label></div>' +
            '<div class="b2b-pr-field"><label><input type="checkbox" name="specs[' + idx + '][is_filterable]" value="1" /> قابل فیلتر</label></div>' +
            '<div class="b2b-pr-field"><label><input type="checkbox" name="specs[' + idx + '][is_active]" value="1" checked /> فعال</label></div>' +
            '</div>' +
            '<input type="hidden" name="specs[' + idx + '][spec_id]" value="0" />' +
            '<div class="b2b-pr-row"><button type="button" class="b2b-btn b2b-btn-primary b2b-spec-save" style="margin-top:8px;">ذخیره فیلد</button></div>' +
            '</div></div>';

        $('#b2b-spec-list').append(html);
        $('#b2b-spec-list').find('.b2b-spec-card').last().find('input[name*="[label]"]').focus();
    }

    function saveSpecs() {
        var defId = $('#b2b-spec-list').data('definition-id');
        if (!defId) return;

        $.ajax({
            url: b2bSpec.ajaxUrl,
            type: 'POST',
            data: $('#b2b-spec-list').find('input, select, textarea').serialize() + '&action=b2b_spec_save&_b2b_nonce=' + b2bSpec.nonce + '&definition_id=' + defId,
            success: function (r) {
                if (r && r.success) {
                    location.reload();
                } else {
                    alert(r.data ? r.data.message : 'خطا در ذخیره‌سازی');
                }
            },
            error: function () { alert('خطا در ارتباط با سرور'); }
        });
    }

    function loadDynamicFields() {
        var defId = $('#b2b-product-definition-id').val();
        var postId = $('#post_ID').val();
        var $container = $('#b2b-spec-product-fields');

        if (!defId || !defId.trim()) {
            $container.html('<p class="description">ابتدا یک Product Definition انتخاب کنید.</p>');
            return;
        }

        $container.html('<p class="description">در حال بارگذاری مشخصات...</p>');

        $.post(b2bSpec.ajaxUrl, {
            action: 'b2b_spec_load_fields',
            _b2b_nonce: b2bSpec.nonce,
            definition_id: defId,
            product_id: postId
        }, function (r) {
            if (r && r.success && r.data.fields.length > 0) {
                var html = '';
                for (var i = 0; i < r.data.fields.length; i++) {
                    var f = r.data.fields[i];
                    html += renderSpecField(f);
                }
                $container.html(html);
            } else {
                $container.html('<p class="description">هیچ مشخصات فعالی برای این تعریف وجود ندارد.</p>');
            }
        });
    }

    function renderSpecField(f) {
        var name = 'b2b_spec_values[' + f.field_key + ']';
        var req = f.is_required ? ' required' : '';
        var ph = f.placeholder ? ' placeholder="' + f.placeholder + '"' : '';
        var desc = f.description ? '<p class="description" style="margin-top:2px;margin-bottom:4px;">' + f.description + '</p>' : '';
        var h = '<div class="b2b-spec-product-field" style="margin-bottom:12px;">';
        h += '<label style="font-weight:600;font-size:13px;color:#1F2937;display:block;margin-bottom:4px;">' + f.label + (f.is_required ? ' <span style="color:#EF4444;">*</span>' : '') + '</label>';
        h += desc;

        var v = f.value || '';

        switch (f.field_type) {
            case 'textarea':
                h += '<textarea name="' + name + '" class="large-text" rows="3"' + ph + req + '>' + v + '</textarea>';
                break;
            case 'number':
                h += '<input type="number" name="' + name + '" class="regular-text" value="' + v + '"' + ph + req + ' />';
                break;
            case 'decimal':
                h += '<input type="number" name="' + name + '" class="regular-text" step="0.01" value="' + v + '"' + ph + req + ' />';
                break;
            case 'select':
                h += '<select name="' + name + '" class="b2b-select" style="width:100%;"' + req + '><option value="">— انتخاب —</option>';
                if (f.options) {
                    for (var i = 0; i < f.options.length; i++) {
                        h += '<option value="' + f.options[i] + '"' + (v === f.options[i] ? ' selected' : '') + '>' + f.options[i] + '</option>';
                    }
                }
                h += '</select>';
                break;
            case 'radio':
                h += '<div style="display:flex;flex-wrap:wrap;gap:12px;">';
                if (f.options) {
                    for (var i = 0; i < f.options.length; i++) {
                        h += '<label style="display:flex;align-items:center;gap:4px;font-size:13px;"><input type="radio" name="' + name + '" value="' + f.options[i] + '"' + (v === f.options[i] ? ' checked' : '') + req + '/> ' + f.options[i] + '</label>';
                    }
                }
                h += '</div>';
                break;
            case 'checkbox':
            case 'switch':
                h += '<label style="display:flex;align-items:center;gap:6px;font-size:13px;"><input type="checkbox" name="' + name + '" value="1"' + (v === '1' ? ' checked' : '') + req + '/> فعال</label>';
                break;
            case 'date':
                h += '<input type="date" name="' + name + '" class="regular-text" value="' + v + '"' + req + ' />';
                break;
            case 'time':
                h += '<input type="time" name="' + name + '" class="regular-text" value="' + v + '"' + req + ' />';
                break;
            case 'datetime':
                h += '<input type="datetime-local" name="' + name + '" class="regular-text" value="' + v + '"' + req + ' />';
                break;
            case 'url':
                h += '<input type="url" name="' + name + '" class="regular-text" value="' + v + '"' + ph + req + ' />';
                break;
            case 'email':
                h += '<input type="email" name="' + name + '" class="regular-text" value="' + v + '"' + ph + req + ' />';
                break;
            case 'phone':
                h += '<input type="tel" name="' + name + '" class="regular-text" value="' + v + '"' + ph + req + ' />';
                break;
            case 'color':
                h += '<input type="color" name="' + name + '" value="' + (v || '#7B2CBF') + '"' + req + ' style="width:60px;height:36px;" />';
                break;
            case 'range':
                h += '<input type="range" name="' + name + '" min="0" max="100" value="' + (v || '0') + '"' + req + ' style="width:100%;" />';
                break;
            default:
                h += '<input type="text" name="' + name + '" class="regular-text" value="' + v + '"' + ph + req + ' />';
                break;
        }

        h += '</div>';
        return h;
    }
})(jQuery);
