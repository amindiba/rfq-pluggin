/**
 * Product Definitions - Admin JS
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Delete button
        $('.b2b-pd-delete').on('click', function () {
            var $btn = $(this);
            var id = $btn.data('id');
            if (!confirm('آیا از حذف این تعریف اطمینان دارید؟')) return;

            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_pd_delete',
                _b2b_nonce: b2bProcurement.nonce,
                id: id
            }, function (r) {
                if (r && r.success) {
                    $btn.closest('tr').fadeOut(200, function () { $(this).remove(); });
                    if (typeof B2BAdmin !== 'undefined') B2BAdmin.toast(r.data.message, 'success');
                } else {
                    if (typeof B2BAdmin !== 'undefined') B2BAdmin.toast(r.data.message || 'خطا', 'error');
                }
            }).fail(function () {
                if (typeof B2BAdmin !== 'undefined') B2BAdmin.toast('خطا در ارتباط با سرور', 'error');
            });
        });

        // Auto-generate slug from name
        $('input[name="name"]').on('keyup', function () {
            var slug = $(this).val()
                .toLowerCase()
                .replace(/[^\w\u0600-\u06FF\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            $('input[name="slug"]').val(slug);
        });
    });
})(jQuery);
