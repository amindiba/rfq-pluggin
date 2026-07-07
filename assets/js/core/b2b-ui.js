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
            return { success: false, data: { message: 'خطا در ارتباط با سرور' } };
        }
    },

    // ==================== HELPER: PERSIAN NUMBERS ====================
    toPersian(num) {
        if (num === null || num === undefined) return '';
        const digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
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
