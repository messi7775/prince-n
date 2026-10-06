<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0c1322">
    <title><?= e($pageTitle ?? 'لوحة التحكم') ?> | شبكة البرنس</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/css/app.css') ?>">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('pn-theme');
                if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
            } catch (e) {}
        })();
    </script>
</head>
<body>
<script>
    (function () {
        try {
            if (window.matchMedia('(min-width: 851px)').matches && localStorage.getItem('pn-sidebar') === 'collapsed') {
                document.body.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    })();
</script>
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
        <?php require __DIR__ . '/topbar.php'; ?>

        <?= $content ?? '' ?>

        <nav class="mobile-bottom-nav" aria-label="التنقل السريع">
            <a href="/search" class="<?= ($active ?? '') === 'search' ? 'active' : '' ?>"><span>⌕</span><small>البحث</small></a>
            <a href="/cash" class="<?= ($active ?? '') === 'cash' ? 'active' : '' ?>"><span>▥</span><small>الصندوق</small></a>
            <a href="/sales" class="<?= ($active ?? '') === 'sales' ? 'active' : '' ?>"><span>🛒</span><small>المبيعات</small></a>
            <a href="/packages" class="<?= ($active ?? '') === 'packages' ? 'active' : '' ?>"><span>◇</span><small>الباقات</small></a>
            <a href="/dashboard" class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>"><span>▦</span><small>الرئيسية</small></a>
        </nav>
    </main>
</div>
<div class="confirm-overlay" id="confirm-logout" role="dialog" aria-modal="true" aria-labelledby="confirm-logout-title">
    <div class="confirm-card">
        <div class="confirm-icon">↪</div>
        <h3 id="confirm-logout-title">تسجيل الخروج</h3>
        <p>هل أنت متأكد من رغبتك في تسجيل الخروج من النظام؟</p>
        <div class="confirm-actions">
            <button type="button" class="btn" data-confirm-cancel>إلغاء</button>
            <button type="button" class="btn danger" data-confirm-ok>تأكيد الخروج</button>
        </div>
    </div>
</div>
<script src="/assets/js/app.js?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/js/app.js') ?>"></script>
<script src="/assets/js/filters.js?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/js/filters.js') ?>"></script>
<script src="/assets/js/row-menu.js?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/js/row-menu.js') ?>"></script>
</body>
</html>
