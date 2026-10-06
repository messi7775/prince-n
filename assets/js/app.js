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
})();
