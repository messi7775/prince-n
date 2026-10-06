<header class="topbar">
    <div class="topbar-side">
        <button class="icon-btn" type="button" aria-label="القائمة" data-menu-toggle>☰</button>
    </div>

    <div class="topbar-brand">
        <strong>شبكة البرنس</strong>
        <span>⌘</span>
    </div>

    <div class="topbar-side">
        <form method="post" action="/logout">
            <?= csrf_field() ?>
            <button class="icon-btn" type="submit" aria-label="تسجيل الخروج">↪</button>
        </form>
    </div>
</header>
