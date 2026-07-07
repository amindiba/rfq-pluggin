/**
 * B2B Procurement - Design System JavaScript
 * Version: 3.0.0
 */

/* global jQuery, b2bProcurement */
(function ($) {
    'use strict';

    var B2BAdmin = {

        init: function () {
            this.initModals();
            this.initDropdowns();
            this.initTabs();
            this.initAccordion();
            this.initTooltips();
            this.initBulkActions();
            this.initConfirmDialogs();
            this.initAjaxForms();
            this.initSearch();
            this.initCheckAll();
            this.initOffcanvas();
            this.initSkeletons();
        },

        /* ---- Modal ---- */
        initModals: function () {
            $(document).on('click', '[data-open-modal]', function (e) {
                e.preventDefault();
                B2BAdmin.openModal($(this).data('open-modal'));
            });
            $(document).on('click', '.b2b-modal-close, .b2b-modal-cancel', function () {
                B2BAdmin.closeModal($(this).closest('.b2b-modal'));
            });
            $(document).on('click', '.b2b-modal-overlay', function () {
                var $m = $(this).closest('.b2b-modal');
                if ($m.data('close-overlay') !== false) B2BAdmin.closeModal($m);
            });
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    $('.b2b-modal.is-active').each(function () {
                        if ($(this).data('close-esc') !== false) B2BAdmin.closeModal($(this));
                    });
                }
            });
        },

        openModal: function (id) {
            var $m = typeof id === 'string' ? $('#' + id) : id;
            if ($m.length) {
                $m.addClass('is-active');
                $('body').addClass('b2b-modal-open');
            }
        },

        closeModal: function ($m) {
            $m.removeClass('is-active');
            $('body').removeClass('b2b-modal-open');
        },

        /* ---- Dropdown ---- */
        initDropdowns: function () {
            $(document).on('click', '[data-dropdown]', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $dd = $(this).closest('.b2b-dropdown');
                var wasOpen = $dd.hasClass('is-open');
                $('.b2b-dropdown').removeClass('is-open');
                if (!wasOpen) $dd.addClass('is-open');
            });
            $(document).on('click', function () {
                $('.b2b-dropdown').removeClass('is-open');
            });
        },

        /* ---- Tabs ---- */
        initTabs: function () {
            $(document).on('click', '.b2b-tab-nav a[data-tab]', function (e) {
                e.preventDefault();
                var $nav = $(this).closest('.b2b-tab-nav');
                var target = $(this).data('tab');
                $nav.find('li').removeClass('b2b-tab-active');
                $(this).parent().addClass('b2b-tab-active');
                var $container = $nav.next('.b2b-tab-content');
                $container.children('[data-tab-panel]').hide();
                $container.children('[data-tab-panel="' + target + '"]').show();
            });
        },

        /* ---- Accordion ---- */
        initAccordion: function () {
            $(document).on('click', '.b2b-accordion-header', function () {
                var $item = $(this).closest('.b2b-accordion-item');
                var $acc = $item.closest('.b2b-accordion');
                var wasOpen = $item.hasClass('is-open');
                $acc.find('.b2b-accordion-item').removeClass('is-open');
                $acc.find('.b2b-accordion-body').css('max-height', '0');
                if (!wasOpen) {
                    $item.addClass('is-open');
                    var $body = $item.find('.b2b-accordion-body');
                    $body.css('max-height', $body[0].scrollHeight + 'px');
                }
            });
        },

        /* ---- Tooltips ---- */
        initTooltips: function () {
            // CSS-based tooltips, no JS needed
        },

        /* ---- Bulk Actions ---- */
        initBulkActions: function () {
            $(document).on('click', '.b2b-bulk-submit', function () {
                var action = $('.b2b-bulk-select').val();
                var checked = $('.b2b-row-check:checked');
                if (!action) {
                    B2BAdmin.toast('لطفاً یک عملیات انتخاب کنید.', 'warning');
                    return;
                }
                if (!checked.length) {
                    B2BAdmin.toast('لطفاً حداقل یک مورد انتخاب کنید.', 'warning');
                    return;
                }
                var ids = [];
                checked.each(function () { ids.push($(this).val()); });
                if (confirm('آیا مطمئن هستید؟')) {
                    B2BAdmin.ajaxRequest(action, { ids: ids }, function (r) {
                        if (r.success) {
                            B2BAdmin.toast(r.data.message || 'عملیات با موفقیت انجام شد.', 'success');
                            location.reload();
                        } else {
                            B2BAdmin.toast(r.data.message || 'خطا در انجام عملیات.', 'error');
                        }
                    });
                }
            });
        },

        /* ---- Confirm Dialogs ---- */
        initConfirmDialogs: function () {
            $(document).on('click', '[data-confirm]', function (e) {
                e.preventDefault();
                var msg = $(this).data('confirm');
                var href = $(this).attr('href');
                if (confirm(msg)) {
                    if (href && href !== '#') window.location.href = href;
                }
            });
        },

        /* ---- Ajax Forms ---- */
        initAjaxForms: function () {
            $(document).on('submit', '.b2b-ajax-form', function (e) {
                e.preventDefault();
                var $form = $(this);
                var action = $form.data('action');
                var $btn = $form.find('.b2b-form-submit, .b2b-modal-submit');
                var $loading = $btn.find('.b2b-btn-loading');
                var $text = $btn.find('.b2b-btn-text');

                $btn.prop('disabled', true);
                if ($loading.length) $loading.show();
                if ($text.length) $text.hide();

                $.ajax({
                    url: b2bProcurement.ajaxUrl,
                    type: 'POST',
                    data: $form.serialize() + '&action=' + action,
                    success: function (r) {
                        if (r.success) {
                            B2BAdmin.toast(r.data.message || 'ذخیره با موفقیت انجام شد.', 'success');
                            var $modal = $form.closest('.b2b-modal');
                            if ($modal.length) B2BAdmin.closeModal($modal);
                            if ($form.data('reload')) location.reload();
                        } else {
                            B2BAdmin.toast(r.data.message || 'خطا در ذخیره‌سازی.', 'error');
                            if (r.data.errors) {
                                $.each(r.data.errors, function (f, m) {
                                    var $f = $form.find('[name="' + f + '"]').closest('.b2b-form-field');
                                    $f.addClass('b2b-field-error').find('.b2b-form-error').remove();
                                    $f.append('<p class="b2b-form-error">' + m + '</p>');
                                });
                            }
                        }
                    },
                    error: function () {
                        B2BAdmin.toast('خطا در ارتباط با سرور.', 'error');
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                        if ($loading.length) $loading.hide();
                        if ($text.length) $text.show();
                    }
                });
            });
        },

        /* ---- Search ---- */
        initSearch: function () {
            var t;
            $(document).on('input', '#b2b-log-search', function () {
                var q = $(this).val().toLowerCase();
                clearTimeout(t);
                t = setTimeout(function () {
                    $('#b2b-log-content').each(function () {
                        var text = $(this).text().toLowerCase();
                        $(this).toggle(q === '' || text.indexOf(q) > -1);
                    });
                }, 300);
            });
        },

        /* ---- Check All ---- */
        initCheckAll: function () {
            $(document).on('change', '.b2b-check-all', function () {
                $('.b2b-row-check').prop('checked', $(this).prop('checked'));
            });
            $(document).on('change', '.b2b-row-check', function () {
                var total = $('.b2b-row-check').length;
                var checked = $('.b2b-row-check:checked').length;
                $('.b2b-check-all').prop('checked', total === checked);
            });
        },

        /* ---- Offcanvas ---- */
        initOffcanvas: function () {
            $(document).on('click', '[data-open-offcanvas]', function (e) {
                e.preventDefault();
                var id = $(this).data('open-offcanvas');
                $('#' + id).addClass('is-active');
                $('body').addClass('b2b-modal-open');
            });
            $(document).on('click', '[data-close-offcanvas]', function () {
                $(this).closest('.b2b-offcanvas').removeClass('is-active');
                $('body').removeClass('b2b-modal-open');
            });
        },

        /* ---- Skeletons ---- */
        initSkeletons: function () {
            // Auto-replace skeleton loaders after content loads
            $(document).on('b2b:contentLoaded', '.b2b-skeleton-container', function () {
                $(this).find('.b2b-skeleton').removeClass('b2b-skeleton');
            });
        },

        /* ---- Toast Notification ---- */
        toast: function (message, type) {
            type = type || 'info';
            var $container = $('#b2b-toast-container');
            if (!$container.length) {
                $container = $('<div id="b2b-toast-container" class="b2b-toast-container"></div>').appendTo('body');
            }
            var icons = {
                success: '&#10003;',
                error: '&#10007;',
                warning: '&#9888;',
                info: '&#8505;'
            };
            var $toast = $('<div class="b2b-toast b2b-toast-' + type + '">' +
                '<span>' + (icons[type] || '') + '</span>' +
                '<span>' + message + '</span>' +
                '</div>');
            $container.append($toast);
            setTimeout(function () {
                $toast.css({ opacity: 0, transform: 'translateY(-10px)' });
                setTimeout(function () { $toast.remove(); }, 300);
            }, 4000);
        },

        /* ---- Ajax Helper ---- */
        ajaxRequest: function (action, data, callback) {
            data = data || {};
            data.action = action;
            data._b2b_nonce = b2bProcurement.nonce;
            $.post(b2bProcurement.ajaxUrl, data, function (r) {
                if (typeof callback === 'function') callback(r);
            }).fail(function () {
                B2BAdmin.toast('خطا در ارتباط با سرور.', 'error');
            });
        }
    };

    $(document).ready(function () {
        B2BAdmin.init();
    });

    window.B2BAdmin = B2BAdmin;

    // Global Persian number converter
    window.toPersianNum = function (num) {
        if (num === null || num === undefined) return '';
        var persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return String(num).replace(/\d/g, function (d) { return persianDigits[parseInt(d)]; });
    };

})(jQuery);
