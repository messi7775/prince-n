(function () {
    'use strict';

    /* ---------- Mobile menu (off-canvas) ---------- */
    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-menu-overlay]');
    const menuToggles = document.querySelectorAll('[data-menu-toggle]');

    const closeMenu = () => {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('open');
    };

    menuToggles.forEach(t => t.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
        overlay?.classList.toggle('open');
    }));

    overlay?.addEventListener('click', closeMenu);

    /* ---------- Collapsible sidebar (desktop) ---------- */
    const collapseBtns = document.querySelectorAll('[data-sidebar-collapse]');
    const applyCollapse = (collapsed) => {
        document.body.classList.toggle('sidebar-collapsed', collapsed);
    };

    collapseBtns.forEach(btn => btn.addEventListener('click', () => {
        const collapsed = !document.body.classList.contains('sidebar-collapsed');
        applyCollapse(collapsed);
        try { localStorage.setItem('pn-sidebar', collapsed ? 'collapsed' : 'expanded'); } catch (e) {}
    }));

    /* ---------- Light / Dark theme toggle ---------- */
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const dark = document.documentElement.getAttribute('data-theme') === 'dark';
            const next = dark ? 'light' : 'dark';
            if (dark) {
                document.documentElement.removeAttribute('data-theme');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
            try { localStorage.setItem('pn-theme', next); } catch (e) {}
        });
    }

    /* ---------- Logout confirmation dialog ---------- */
    const logoutForms = document.querySelectorAll('form.logout-form');
    const confirmBox = document.getElementById('confirm-logout');
    if (logoutForms.length && confirmBox) {
        let pending = null;
        logoutForms.forEach(f => f.addEventListener('submit', e => {
            if (f.dataset.confirmed === '1') return;
            e.preventDefault();
            pending = f;
            confirmBox.classList.add('open');
        }));
        const closeConfirm = () => { confirmBox.classList.remove('open'); pending = null; };
        confirmBox.querySelector('[data-confirm-cancel]').addEventListener('click', closeConfirm);
        confirmBox.querySelector('[data-confirm-ok]').addEventListener('click', () => {
            if (!pending) return;
            pending.dataset.confirmed = '1';
            pending.submit();
        });
        confirmBox.addEventListener('click', e => { if (e.target === confirmBox) closeConfirm(); });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && confirmBox.classList.contains('open')) closeConfirm();
        });
    }
})();
