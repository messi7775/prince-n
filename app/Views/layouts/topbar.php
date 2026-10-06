<header class="topbar">
    <div class="topbar-side">
        <button class="icon-btn mobile-only" type="button" aria-label="القائمة" data-menu-toggle>☰</button>
        <button class="icon-btn desktop-only" type="button" aria-label="طي القائمة" data-sidebar-collapse>⇤</button>
    </div>

    <div class="topbar-brand">
        <strong>شبكة البرنس</strong>
        <span>PN</span>
    </div>

    <div class="topbar-side topbar-actions">
        <button class="icon-btn" type="button" id="theme-toggle" aria-label="تبديل الوضع الليلي/النهاري">
            <span class="sun">☀</span><span class="moon">☾</span>
        </button>
        <form method="post" action="/logout" class="logout-form">
            <?= csrf_field() ?>
            <button class="icon-btn logout-btn" type="submit" aria-label="تسجيل الخروج"><span>↪</span><small>خروج</small></button>
        </form>
    </div>
</header>
