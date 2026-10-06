document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const toggles = document.querySelectorAll('[data-menu-toggle]');
    const overlay = document.querySelector('[data-menu-overlay]');

    const closeMenu = () => {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('open');
    };

    toggles.forEach(t => t.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
        overlay?.classList.toggle('open');
    }));

    overlay?.addEventListener('click', closeMenu);
});
