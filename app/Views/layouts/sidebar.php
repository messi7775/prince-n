<aside class="sidebar" data-sidebar>
    <div class="sidebar-top">
        <div class="brand">
            <div class="brand-mark">⌘</div>
            <div><strong>شبكة البرنس</strong><span>Prince Network</span></div>
        </div>
        <button class="sidebar-close" type="button" data-menu-toggle aria-label="إغلاق القائمة">×</button>
    </div>

    <nav class="nav" aria-label="القائمة الرئيسية">
        <a class="nav-item <?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="/dashboard"><span>▦</span><b>الرئيسية</b></a>
        <a class="nav-item <?= ($active ?? '') === 'packages' ? 'active' : '' ?>" href="/packages"><span>◇</span><b>الباقات</b></a>
        <a class="nav-item <?= ($active ?? '') === 'inventory' ? 'active' : '' ?>" href="/inventory"><span>♧</span><b>المخزون</b></a>
        <a class="nav-item <?= ($active ?? '') === 'distributors' ? 'active' : '' ?>" href="/distributors"><span>♙</span><b>الموزعون</b></a>
        <a class="nav-item <?= ($active ?? '') === 'sales' ? 'active' : '' ?>" href="/sales"><span>🛒</span><b>المبيعات</b></a>
        <a class="nav-item <?= ($active ?? '') === 'payments' ? 'active' : '' ?>" href="/payments"><span>♣</span><b>التحصيلات</b></a>
        <a class="nav-item <?= ($active ?? '') === 'lines' ? 'active' : '' ?>" href="/lines"><span>⌁</span><b>الخطوط</b></a>
        <a class="nav-item <?= ($active ?? '') === 'line-payments' ? 'active' : '' ?>" href="/line-payments"><span>▭</span><b>تسديد الخطوط</b></a>
        <a class="nav-item <?= ($active ?? '') === 'expenses' ? 'active' : '' ?>" href="/expenses"><span>▣</span><b>المصروفات</b></a>
        <a class="nav-item <?= ($active ?? '') === 'owner-withdrawals' ? 'active' : '' ?>" href="/owner-withdrawals"><span>▱</span><b>سحوبات المالك</b></a>
        <a class="nav-item <?= ($active ?? '') === 'cash' ? 'active' : '' ?>" href="/cash"><span>▥</span><b>الصندوق</b></a>
        <a class="nav-item <?= ($active ?? '') === 'reports' ? 'active' : '' ?>" href="/reports"><span>▤</span><b>التقارير</b></a>
        <a class="nav-item <?= ($active ?? '') === 'search' ? 'active' : '' ?>" href="/search"><span>⌕</span><b>البحث</b></a>
        <a class="nav-item <?= ($active ?? '') === 'audit' ? 'active' : '' ?>" href="/audit"><span>◴</span><b>سجل التدقيق</b></a>
        <a class="nav-item <?= ($active ?? '') === 'settings' ? 'active' : '' ?>" href="/settings"><span>⚙</span><b>الإعدادات</b></a>
        <a class="nav-item <?= ($active ?? '') === 'backup' ? 'active' : '' ?>" href="/backup"><span>◫</span><b>النسخ الاحتياطي</b></a>
        <form method="post" action="/logout" class="logout-form">
            <?= csrf_field() ?>
            <button class="nav-item logout" type="submit"><span>↪</span><b>تسجيل الخروج</b></button>
        </form>
    </nav>
</aside>
<div class="sidebar-overlay" data-menu-overlay></div>
