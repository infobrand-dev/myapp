import './bootstrap';
import '@tabler/core/dist/js/tabler.min.js';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// ── Theme toggler (light/dark + primary color) ─────────────────────────────
const applyTheme = (mode, color) => {
    const root = document.documentElement;
    root.setAttribute('data-bs-theme', mode || 'light');
    if (color) {
        root.style.setProperty('--tblr-primary', color);
        const rgb = color.match(/[0-9a-f]{2}/gi)?.map(h => parseInt(h, 16)) ?? [32, 107, 196];
        root.style.setProperty('--tblr-primary-rgb', rgb.join(','));
    }
    localStorage.setItem('theme-mode', mode || 'light');
    if (color) localStorage.setItem('theme-color', color);
};

document.addEventListener('DOMContentLoaded', () => {
    // Restore saved theme
    const savedMode = localStorage.getItem('theme-mode') || 'light';
    const savedColor = localStorage.getItem('theme-color');
    applyTheme(savedMode, savedColor);

    // Theme mode toggle buttons
    document.querySelectorAll('[data-theme-mode]').forEach(btn => {
        btn.addEventListener('click', () => applyTheme(btn.dataset.themeMode, localStorage.getItem('theme-color')));
    });

    // Theme color swatch buttons
    document.querySelectorAll('[data-theme-color]').forEach(btn => {
        btn.addEventListener('click', () => applyTheme(localStorage.getItem('theme-mode') || 'light', btn.dataset.themeColor));
    });

    // ── Delete confirmation (data-confirm attribute) ───────────────────────
    // Usage: <form data-confirm="Are you sure?">...</form>
    //     or: <a href="..." data-confirm="Delete this item?">Delete</a>
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('submit', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (!window.confirm(msg)) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });
        // For <a> tags with data-confirm
        if (el.tagName === 'A') {
            el.addEventListener('click', function (e) {
                const msg = this.dataset.confirm || 'Are you sure?';
                if (!window.confirm(msg)) {
                    e.preventDefault();
                }
            });
        }
    });
});

// ── Toast notification utility ─────────────────────────────────────────────
// Usage: window.AppToast.success('Saved!') / .error() / .warning() / .info()
window.AppToast = (() => {
    const COLORS = {
        success: { bg: 'bg-success', icon: 'ti-circle-check' },
        error:   { bg: 'bg-danger',  icon: 'ti-alert-circle' },
        warning: { bg: 'bg-warning', icon: 'ti-alert-triangle' },
        info:    { bg: 'bg-info',    icon: 'ti-info-circle' },
    };

    function show(message, type = 'info', duration = 4000) {
        const palette = COLORS[type] || COLORS.info;

        // Container
        let container = document.getElementById('app-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'app-toast-container';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'false');
            Object.assign(container.style, {
                position: 'fixed',
                bottom: '1.25rem',
                right: '1.25rem',
                zIndex: '9999',
                display: 'flex',
                flexDirection: 'column',
                gap: '0.5rem',
                maxWidth: '22rem',
                width: '100%',
            });
            document.body.appendChild(container);
        }

        // Toast element
        const toast = document.createElement('div');
        toast.className = `toast show align-items-center text-white border-0 ${palette.bg}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex align-items-center gap-2 p-3">
                <i class="ti ${palette.icon} flex-shrink-0" style="font-size:1.1rem;" aria-hidden="true"></i>
                <span class="me-auto">${String(message).replace(/</g, '&lt;')}</span>
                <button type="button" class="btn-close btn-close-white ms-2" aria-label="Close"></button>
            </div>`;

        toast.querySelector('.btn-close').addEventListener('click', () => dismiss(toast));
        container.appendChild(toast);

        // Animate in
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(0.5rem)';
        toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });
        });

        // Auto dismiss
        const timer = setTimeout(() => dismiss(toast), duration);
        toast._dismissTimer = timer;
    }

    function dismiss(toast) {
        if (toast._dismissed) return;
        toast._dismissed = true;
        clearTimeout(toast._dismissTimer);
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(0.5rem)';
        setTimeout(() => toast.remove(), 200);
    }

    return {
        success: (msg, duration) => show(msg, 'success', duration),
        error:   (msg, duration) => show(msg, 'error',   duration),
        warning: (msg, duration) => show(msg, 'warning', duration),
        info:    (msg, duration) => show(msg, 'info',    duration),
    };
})();
