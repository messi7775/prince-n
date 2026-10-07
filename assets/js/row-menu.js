/* قائمة الإجراءات ⋮ لكل سجل */
(function () {
    var open = null;

    function closeAll() {
        document.querySelectorAll('.kebab-dropdown.open').forEach(function (d) {
            d.classList.remove('open');
            d.style.top = '';
            d.style.left = '';
            d.style.position = '';
        });
        open = null;
    }

    function toggle(btn) {
        var dd = btn.closest('.kebab').querySelector('.kebab-dropdown');
        var wasOpen = dd.classList.contains('open');
        closeAll();
        if (wasOpen) return;
        dd.classList.add('open');
        dd.style.position = 'fixed'; // خارج حاوية الجدول حتى لا تُقصّ
        var r = btn.getBoundingClientRect();
        var w = dd.offsetWidth, h = dd.offsetHeight;
        var left = Math.min(Math.max(8, r.left + r.width - w), window.innerWidth - w - 8);
        var top = r.bottom + 4;
        if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 4);
        dd.style.top = top + 'px';
        dd.style.left = left + 'px';
        open = dd;
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.kebab-btn');
        if (btn) { toggle(btn); return; }
        if (open && !open.contains(e.target)) { closeAll(); return; }
        if (open && e.target.closest('.kebab-item')) closeAll();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAll();
    });
    window.addEventListener('scroll', closeAll, true);
    window.addEventListener('resize', closeAll);
})();
