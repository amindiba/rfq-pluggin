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
