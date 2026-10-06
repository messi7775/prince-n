<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>البحث</h2>
            <p>ابحث في النظام حسب الموزع أو الباقة أو الملاحظة</p>
        </div>
    </section>

    <section class="dashboard-panel">
        <form method="get" action="/search" class="search-bar">
            <input name="q" value="<?= e($query) ?>" placeholder="اكتب كلمة البحث..." autofocus>
            <button class="btn primary" type="submit">بحث</button>
        </form>
    </section>

    <?php if (!empty($results['distributors'])): ?>
    <section class="dashboard-panel">
        <div class="section-title"><h3>الموزعون</h3><span>♙</span></div>
        <table class="data-table">
            <thead><tr><th>الاسم</th><th>الهاتف</th></tr></thead>
            <tbody>
                <?php foreach ($results['distributors'] as $d): ?>
                <tr><td><?= e($d['name']) ?></td><td><?= e($d['phone'] ?? '') ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if (!empty($results['sales'])): ?>
    <section class="dashboard-panel">
        <div class="section-title"><h3>المبيعات</h3><span>🛒</span></div>
        <table class="data-table">
            <thead><tr><th>التاريخ</th><th>الموزع</th><th>الباقة</th><th>عدد الشدات</th><th>الإجمالي</th></tr></thead>
            <tbody>
                <?php foreach ($results['sales'] as $s): ?>
                <tr><td><?= ar_date($s['created_at']) ?></td><td><?= e($s['distributor_name'] ?? 'نقدي') ?></td><td><?= e($s['package_name'] ?? '') ?></td><td><?= int_num($s['bundles_count']) ?></td><td><?= money($s['total']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if (!empty($query) && empty($results)): ?>
    <section class="dashboard-panel">
        <div class="empty-state">لا توجد نتائج</div>
    </section>
    <?php endif; ?>

    <?php if (empty($query)): ?>
    <section class="dashboard-panel">
        <div class="empty-state">اكتب كلمة للبحث</div>
    </section>
    <?php endif; ?>
</div>
