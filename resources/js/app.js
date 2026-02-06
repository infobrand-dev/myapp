import './bootstrap';
import '@tabler/core/dist/js/tabler.min.js';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Theme toggler (light/dark + primary color)
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

// Apply saved on load
document.addEventListener('DOMContentLoaded', () => {
    const savedMode = localStorage.getItem('theme-mode') || 'light';
    const savedColor = localStorage.getItem('theme-color');
    applyTheme(savedMode, savedColor);

    document.querySelectorAll('[data-theme-mode]').forEach(btn => {
        btn.addEventListener('click', () => applyTheme(btn.dataset.themeMode, localStorage.getItem('theme-color')));
    });

    document.querySelectorAll('[data-theme-color]').forEach(btn => {
        btn.addEventListener('click', () => applyTheme(localStorage.getItem('theme-mode') || 'light', btn.dataset.themeColor));
    });
});
