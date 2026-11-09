import '@fontsource-variable/inter';
import '../scss/app.scss';
import * as bootstrap from 'bootstrap';
import Alpine from 'alpinejs';
import { initCharts } from './charts';
import pos from './pos';
import { ksConfirm, initConfirmations } from './confirm';

window.bootstrap = bootstrap;
window.ksConfirm = ksConfirm;

// ---------- Toasts ----------
window.toast = (message, type = 'success') => {
    let host = document.querySelector('.ks-toasts');
    if (!host) { host = document.createElement('div'); host.className = 'ks-toasts'; host.setAttribute('aria-live', 'polite'); document.body.appendChild(host); }
    const el = document.createElement('div');
    el.className = `ks-toast ${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    const icon = { success: 'check-circle-fill', error: 'exclamation-octagon-fill', info: 'info-circle-fill' }[type] || 'info-circle-fill';
    el.innerHTML = `<i class="bi bi-${icon}"></i><div class="flex-grow-1"></div><button type="button" class="btn-close btn-close-white btn-sm" aria-label="Close"></button>`;
    el.querySelector('div').textContent = message;
    el.querySelector('button').onclick = () => el.remove();
    host.appendChild(el);
    setTimeout(() => el.remove(), type === 'error' ? 8000 : 4500);
};

// ---------- Money helpers (display only; the server recalculates everything) ----------
window.ksMoney = (paisa) => {
    const neg = paisa < 0; paisa = Math.abs(Math.round(paisa));
    return (neg ? '-' : '') + 'Rs ' + Math.floor(paisa / 100).toLocaleString('en-PK') + '.' + String(paisa % 100).padStart(2, '0');
};

document.addEventListener('DOMContentLoaded', () => {
    // Sidebar
    const body = document.body;
    try { if (localStorage.getItem('ks-sidebar') === 'collapsed') body.classList.add('sidebar-collapsed'); } catch (e) {}
    document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => btn.addEventListener('click', () => {
        if (window.matchMedia('(min-width: 992px)').matches) {
            body.classList.toggle('sidebar-collapsed');
            try { localStorage.setItem('ks-sidebar', body.classList.contains('sidebar-collapsed') ? 'collapsed' : 'open'); } catch (e) {}
        } else {
            body.classList.toggle('sidebar-open');
        }
    }));
    document.querySelector('.ks-backdrop')?.addEventListener('click', () => body.classList.remove('sidebar-open'));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') body.classList.remove('sidebar-open'); });

    // Confirmation dialogs: sign-out, DELETE/PUT/PATCH forms and data-confirm forms (see confirm.js)
    initConfirmations();

    // Prevent double submits on normal forms
    document.querySelectorAll('form:not([data-no-lock])').forEach(form => form.addEventListener('submit', e => {
        if (e.defaultPrevented) return;
        const btn = form.querySelector('[type=submit]:not([data-no-lock])');
        if (btn) { setTimeout(() => { btn.disabled = true; btn.dataset.originalText = btn.innerHTML; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Please wait…'; }, 0); }
    }));

    // Auto-submit filter selects
    document.querySelectorAll('[data-autosubmit]').forEach(el => el.addEventListener('change', () => el.form.requestSubmit()));

    // Flash messages
    document.querySelectorAll('[data-flash]').forEach(el => window.toast(el.dataset.flash, el.dataset.type));

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    initCharts();
});

Alpine.data('pos', pos);
window.Alpine = Alpine;
Alpine.start();
