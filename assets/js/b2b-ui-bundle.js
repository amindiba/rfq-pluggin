/* === assets/js/core/b2b-ui.js === */
/**
 * B2B Enterprise UI - Core Framework
 * Version: 1.0.0
 * Vanilla JS ES6+ Module
 */

const B2BUI = {

    // ==================== INIT ====================
    init() {
        this.initModals();
        this.initDrawers();
        this.initAccordions();
        this.initTooltips();
        this.initDropdowns();
        this.initCheckAll();
    },

    // ==================== MODAL ====================
    initModals() {
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-b2b-modal]');
            if (trigger) {
                e.preventDefault();
                const id = trigger.dataset.b2bModal;
                this.openModal(id);
            }

            if (e.target.classList.contains('b2b-modal-overlay') || e.target.closest('.b2b-modal-close') || e.target.closest('.b2b-modal-cancel')) {
                const modal = e.target.closest('.b2b-modal');
                if (modal) this.closeModal(modal);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.b2b-modal.is-active').forEach(m => this.closeModal(m));
            }
        });
    },

    openModal(id) {
        const modal = typeof id === 'string' ? document.getElementById(id) : id;
        if (modal) {
            modal.classList.add('is-active');
            document.body.classList.add('b2b-modal-open');
        }
    },

    closeModal(modal) {
        if (typeof modal === 'string') modal = document.getElementById(modal);
        if (modal) {
            modal.classList.remove('is-active');
            document.body.classList.remove('b2b-modal-open');
        }
    },

    // ==================== DRAWER ====================
    initDrawers() {
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-b2b-drawer]');
            if (trigger) {
                e.preventDefault();
                const id = trigger.dataset.b2bDrawer;
                this.openDrawer(id);
            }

            if (e.target.closest('[data-b2b-drawer-close]')) {
                const drawer = e.target.closest('.b2b-drawer');
                if (drawer) this.closeDrawer(drawer);
            }
        });
    },

    openDrawer(id) {
        const drawer = document.getElementById(id);
        if (drawer) {
            drawer.classList.add('is-active');
            document.body.classList.add('b2b-modal-open');
        }
    },

    closeDrawer(drawer) {
        if (typeof drawer === 'string') drawer = document.getElementById(drawer);
        if (drawer) {
            drawer.classList.remove('is-active');
            document.body.classList.remove('b2b-modal-open');
        }
    },

    // ==================== ACCORDION ====================
    initAccordions() {
        document.addEventListener('click', (e) => {
            const header = e.target.closest('.b2b-accordion-header');
            if (header) {
                const item = header.closest('.b2b-accordion-item');
                const accordion = header.closest('.b2b-accordion');
                const wasOpen = item.classList.contains('is-open');

                // Close all items in accordion
                accordion.querySelectorAll('.b2b-accordion-item').forEach(i => {
                    i.classList.remove('is-open');
                    const body = i.querySelector('.b2b-accordion-body');
                    if (body) body.style.maxHeight = '0';
                });

                // Toggle current item
                if (!wasOpen) {
                    item.classList.add('is-open');
                    const body = item.querySelector('.b2b-accordion-body');
                    if (body) body.style.maxHeight = body.scrollHeight + 'px';
                }
            }
        });
    },

    // ==================== TOOLTIP ====================
    initTooltips() {
        // CSS-based, no JS needed for basic tooltips
    },

    // ==================== DROPDOWN ====================
    initDropdowns() {
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-b2b-dropdown]');
            if (trigger) {
                e.preventDefault();
                e.stopPropagation();
                const dropdown = trigger.closest('.b2b-dropdown');
                const wasOpen = dropdown.classList.contains('is-open');

                // Close all dropdowns
                document.querySelectorAll('.b2b-dropdown.is-open').forEach(d => d.classList.remove('is-open'));

                if (!wasOpen) {
                    dropdown.classList.add('is-open');
                }
            }
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('.b2b-dropdown.is-open').forEach(d => d.classList.remove('is-open'));
        });
    },

    // ==================== CHECK ALL ====================
    initCheckAll() {
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('b2b-check-all')) {
                const checked = e.target.checked;
                const table = e.target.closest('.b2b-table') || e.target.closest('.b2b-table-wrap');
                if (table) {
                    table.querySelectorAll('.b2b-row-check').forEach(cb => cb.checked = checked);
                }
            }

            if (e.target.classList.contains('b2b-row-check')) {
                const table = e.target.closest('.b2b-table') || e.target.closest('.b2b-table-wrap');
                if (table) {
                    const total = table.querySelectorAll('.b2b-row-check').length;
                    const checked = table.querySelectorAll('.b2b-row-check:checked').length;
                    const checkAll = table.querySelector('.b2b-check-all');
                    if (checkAll) checkAll.checked = total === checked;
                }
            }
        });
    },

    // ==================== TOAST ====================
    toast(message, type = 'info', duration = 4000) {
        let container = document.getElementById('b2b-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'b2b-toast-container';
            container.className = 'b2b-toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `b2b-toast b2b-toast-${type}`;
        const span = document.createElement('span');
        span.textContent = message;
        toast.appendChild(span);
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    // ==================== AJAX HELPER ====================
    async ajax(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('_b2b_nonce', window.b2bProcurement?.nonce || '');

        for (const [key, value] of Object.entries(data)) {
            formData.append(key, value);
        }

        try {
            const response = await fetch(window.b2bProcurement?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            return await response.json();
        } catch (error) {
            console.error('[B2B UI] AJAX Error:', error);
            return { success: false, data: { message: 'Ø®Ø·Ø§ Ø¯Ø± Ø§Ø±ØªØ¨Ø§Ø· Ø¨Ø§ Ø³Ø±ÙˆØ±' } };
        }
    },

    // ==================== HELPER: PERSIAN NUMBERS ====================
    toPersian(num) {
        if (num === null || num === undefined) return '';
        const digits = ['Û°', 'Û±', 'Û²', 'Û³', 'Û´', 'Ûµ', 'Û¶', 'Û·', 'Û¸', 'Û¹'];
        return String(num).replace(/\d/g, d => digits[parseInt(d)]);
    },

    // ==================== HELPER: ESCAPE HTML ====================
    esc(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    },

    // ==================== HELPER: FORMAT DATE ====================
    formatDate(dateStr) {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('fa-IR');
    }
};

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => B2BUI.init());

// Export globally
window.B2BUI = B2BUI;


/* === assets/js/core/shell.js === */
/**
 * B2B Enterprise UI - Application Shell
 * Version: 1.0.0
 */

const B2BShell = {

    sidebar: null,
    main: null,
    overlay: null,

    init() {
        this.sidebar = document.querySelector('.b2b-sidebar');
        this.main = document.querySelector('.b2b-main');
        this.overlay = document.querySelector('.b2b-sidebar-overlay');

        this.initToggle();
        this.initResponsive();
        this.initNavActive();
        this.initMobileMenu();
        this.initGroupToggles();
        this.initMenuSearch();
        this.initBadgeRefresh();
        this.initFlyout();
    },

    // ==================== SIDEBAR TOGGLE ====================
    initToggle() {
        // Header toggle button
        const headerToggle = document.getElementById('b2b-sidebar-toggle');
        if (headerToggle) {
            headerToggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleSidebar();
            });
        }

        // All sidebar toggle buttons
        document.querySelectorAll('.b2b-sidebar-toggle').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleSidebar();
            });
        });
    },

    toggleSidebar() {
        if (!this.sidebar) return;

        const isMobile = window.innerWidth <= 1024;

        if (isMobile) {
            this.sidebar.classList.toggle('mobile-open');
        } else {
            this.sidebar.classList.toggle('collapsed');
            const collapsed = this.sidebar.classList.contains('collapsed');
            localStorage.setItem('b2b-sidebar-collapsed', collapsed ? '1' : '0');
        }
    },

    // ==================== RESPONSIVE ====================
    initResponsive() {
        // Restore saved state
        const saved = localStorage.getItem('b2b-sidebar-collapsed');
        if (saved === '1' && window.innerWidth > 1024 && this.sidebar) {
            this.sidebar.classList.add('collapsed');
        }

        // Handle resize
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (window.innerWidth > 1024 && this.sidebar) {
                    this.sidebar.classList.remove('mobile-open');
                }
            }, 150);
        });
    },

    // ==================== NAV ACTIVE STATE ====================
    initNavActive() {
        const currentUrl = window.location.href;
        document.querySelectorAll('.b2b-nav-item').forEach(item => {
            const href = item.getAttribute('href');
            if (href && currentUrl.includes(href.split('page=')[1] || '')) {
                item.classList.add('active');
            }
        });
    },

    // ==================== MENU SEARCH ====================
    initMenuSearch() {
        var input = document.getElementById('b2b-sidebar-search');
        var clearBtn = document.getElementById('b2b-sidebar-search-clear');
        if (!input) return;

        var groups = document.querySelectorAll('.b2b-nav-group');
        var items = document.querySelectorAll('.b2b-nav-item');

        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            if (!q) {
                groups.forEach(function (g) { g.classList.remove('hidden-by-search'); });
                return;
            }
            groups.forEach(function (g) {
                var hasMatch = false;
                g.querySelectorAll('.b2b-nav-item').forEach(function (item) {
                    var label = (item.getAttribute('data-label') || '').toLowerCase();
                    if (label.indexOf(q) !== -1) {
                        hasMatch = true;
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
                if (hasMatch) {
                    g.classList.remove('hidden-by-search');
                    g.classList.remove('collapsed');
                } else {
                    g.classList.add('hidden-by-search');
                }
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                input.value = '';
                input.dispatchEvent(new Event('input'));
                input.focus();
            });
        }
    },

    // ==================== BADGE REFRESH ====================
    initBadgeRefresh() {
        var badge = document.getElementById('b2b-nav-unread-badge');
        if (!badge || typeof b2bProcurement === 'undefined') return;

        setInterval(function () {
            var fd = new FormData();
            fd.append('action', 'b2b_notification_unread_count');
            fd.append('_b2b_nonce', b2bProcurement.nonce);
            fetch(b2bProcurement.ajaxUrl, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (r) {
                    if (r && r.success && r.data && r.data.count !== undefined) {
                        var c = parseInt(r.data.count, 10);
                        if (c > 0) { badge.textContent = c; badge.style.display = ''; }
                        else { badge.style.display = 'none'; }
                    }
                }).catch(function () {});
        }, 60000);
    },

    // ==================== FLYOUT MENU ====================
    initFlyout() {
        var self = this;
        var flyout = document.getElementById('b2b-nav-flyout');
        var flyoutTitle = document.getElementById('b2b-flyout-title');
        var flyoutItems = document.getElementById('b2b-flyout-items');
        if (!flyout || !flyoutItems) return;

        var isOpen = false;

        document.querySelectorAll('.b2b-nav-group-header').forEach(function (header) {
            header.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var groupId = header.getAttribute('data-group-id');
                var groupName = header.getAttribute('data-group-name');
                var group = document.querySelector('.b2b-nav-group[data-group="' + groupId + '"]');
                if (!group) return;

                var itemsContainer = group.querySelector('.b2b-nav-group-items');
                if (!itemsContainer) return;

                // Build flyout content
                flyoutTitle.textContent = groupName;
                flyoutItems.innerHTML = itemsContainer.innerHTML;

                // Position flyout
                var rect = header.getBoundingClientRect();
                var topPos = Math.max(10, Math.min(rect.top, window.innerHeight - 400));
                flyout.style.top = topPos + 'px';
                flyout.classList.add('active');
                isOpen = true;
            });
        });

        // Close flyout on click outside
        document.addEventListener('click', function (e) {
            if (isOpen && !flyout.contains(e.target) && !e.target.closest('.b2b-nav-group-header')) {
                flyout.classList.remove('active');
                isOpen = false;
            }
        });

        // Close flyout on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen) {
                flyout.classList.remove('active');
                isOpen = false;
            }
        });
    },

    // ==================== MOBILE MENU ====================
    initMobileMenu() {
        if (this.overlay) {
            this.overlay.addEventListener('click', () => {
                if (this.sidebar) {
                    this.sidebar.classList.remove('mobile-open');
                }
            });
        }
    },

    // ==================== GROUP TOGGLES ====================
    initGroupToggles() {
        var storage = 'b2b_sidebar_groups';
        var saved = {};
        try { saved = JSON.parse(localStorage.getItem(storage)) || {}; } catch (e) {}

        document.querySelectorAll('.b2b-nav-group-toggle').forEach(function (btn) {
            var groupId = btn.getAttribute('data-group-id');
            var group = btn.closest('.b2b-nav-group');
            if (!group) return;

            if (saved[groupId] === true) {
                group.classList.add('collapsed');
            }

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var isCollapsed = group.classList.toggle('collapsed');
                try {
                    var st = JSON.parse(localStorage.getItem(storage)) || {};
                    st[groupId] = isCollapsed;
                    localStorage.setItem(storage, JSON.stringify(st));
                } catch (ex) {}
            });
        });
    }
};

document.addEventListener('DOMContentLoaded', () => B2BShell.init());
window.B2BShell = B2BShell;


/* === assets/js/admin.js === */
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
                    B2BAdmin.toast('Ù„Ø·ÙØ§Ù‹ ÛŒÚ© Ø¹Ù…Ù„ÛŒØ§Øª Ø§Ù†ØªØ®Ø§Ø¨ Ú©Ù†ÛŒØ¯.', 'warning');
                    return;
                }
                if (!checked.length) {
                    B2BAdmin.toast('Ù„Ø·ÙØ§Ù‹ Ø­Ø¯Ø§Ù‚Ù„ ÛŒÚ© Ù…ÙˆØ±Ø¯ Ø§Ù†ØªØ®Ø§Ø¨ Ú©Ù†ÛŒØ¯.', 'warning');
                    return;
                }
                var ids = [];
                checked.each(function () { ids.push($(this).val()); });
                if (confirm('Ø¢ÛŒØ§ Ù…Ø·Ù…Ø¦Ù† Ù‡Ø³ØªÛŒØ¯ØŸ')) {
                    B2BAdmin.ajaxRequest(action, { ids: ids }, function (r) {
                        if (r.success) {
                            B2BAdmin.toast(r.data.message || 'Ø¹Ù…Ù„ÛŒØ§Øª Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø§Ù†Ø¬Ø§Ù… Ø´Ø¯.', 'success');
                            location.reload();
                        } else {
                            B2BAdmin.toast(r.data.message || 'Ø®Ø·Ø§ Ø¯Ø± Ø§Ù†Ø¬Ø§Ù… Ø¹Ù…Ù„ÛŒØ§Øª.', 'error');
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
                            B2BAdmin.toast(r.data.message || 'Ø°Ø®ÛŒØ±Ù‡ Ø¨Ø§ Ù…ÙˆÙÙ‚ÛŒØª Ø§Ù†Ø¬Ø§Ù… Ø´Ø¯.', 'success');
                            var $modal = $form.closest('.b2b-modal');
                            if ($modal.length) B2BAdmin.closeModal($modal);
                            if ($form.data('reload')) location.reload();
                        } else {
                            B2BAdmin.toast(r.data.message || 'Ø®Ø·Ø§ Ø¯Ø± Ø°Ø®ÛŒØ±Ù‡â€ŒØ³Ø§Ø²ÛŒ.', 'error');
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
                        B2BAdmin.toast('Ø®Ø·Ø§ Ø¯Ø± Ø§Ø±ØªØ¨Ø§Ø· Ø¨Ø§ Ø³Ø±ÙˆØ±.', 'error');
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
                B2BAdmin.toast('Ø®Ø·Ø§ Ø¯Ø± Ø§Ø±ØªØ¨Ø§Ø· Ø¨Ø§ Ø³Ø±ÙˆØ±.', 'error');
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
        var persianDigits = ['Û°', 'Û±', 'Û²', 'Û³', 'Û´', 'Ûµ', 'Û¶', 'Û·', 'Û¸', 'Û¹'];
        return String(num).replace(/\d/g, function (d) { return persianDigits[parseInt(d)]; });
    };

})(jQuery);



