/**
 * Product Features - Admin JS
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Delete
        $('.b2b-pf-delete').on('click', function () {
            var $btn = $(this);
            var id = $btn.data('id');
            if (!confirm('آیا از حذف این ویژگی اطمینان دارید؟')) return;

            $.post(b2bPF.ajaxUrl, {
                action: 'b2b_pf_delete',
                _b2b_nonce: b2bPF.nonce,
                id: id
            }, function (r) {
                if (r && r.success) {
                    $btn.closest('tr').fadeOut(200, function () { $(this).remove(); });
                    if (typeof B2BAdmin !== 'undefined') B2BAdmin.toast(r.data.message, 'success');
                } else {
                    alert(r.data ? r.data.message : 'خطا');
                }
            }).fail(function () {
                alert('خطا در ارتباط با سرور');
            });
        });

        // Auto slug
        $('input[name="name"]').on('keyup', function () {
            var slug = $(this).val().toLowerCase().replace(/[^\w\u0600-\u06FF\s-]/g, '').replace(/\s+/g, '_').replace(/_+/g, '_').trim();
            $('input[name="slug"]').val(slug);
        });

        // Toggle options section
        $('select[name="feature_type"]').on('change', function () {
            var v = $(this).val();
            if (['select', 'checkbox', 'radio'].indexOf(v) !== -1) {
                $('#pf-options-section').show();
            } else {
                $('#pf-options-section').hide();
            }
        });

        // Add option
        $('#pf-add-option').on('click', function () {
            $('#pf-options-list').append(
                '<div class="b2b-spec-option-item"><input type="text" name="options[]" value="" placeholder="مقدار گزینه" style="width:300px;" /><button type="button" class="b2b-spec-option-remove">&#10005;</button></div>'
            );
        });

        // Remove option
        $(document).on('click', '.b2b-spec-option-remove', function () {
            $(this).closest('.b2b-spec-option-item').remove();
        });
    });
})(jQuery);
