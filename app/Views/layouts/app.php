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
    <link rel="stylesheet" href="/assets/css/app.css?v=2">
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
<script src="/assets/js/app.js?v=2"></script>
</body>
</html>
