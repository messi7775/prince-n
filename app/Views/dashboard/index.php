<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>لوحة التحكم</h2>
            <p>مرحباً بك في شبكة البرنس</p>
        </div>
    </section>

    <section class="kpi-grid">
        <article class="kpi-card blue"><div class="kpi-icon">↗</div><div class="kpi-content"><span>مبيعات اليوم (نقدي)</span><strong><?= money($kpis['sales_today']) ?></strong></div></article>
        <article class="kpi-card green"><div class="kpi-icon">▦</div><div class="kpi-content"><span>مبيعات الشهر</span><strong><?= money($kpis['sales_month']) ?></strong></div></article>
        <article class="kpi-card purple"><div class="kpi-icon">♣</div><div class="kpi-content"><span>تحصيلات اليوم</span><strong><?= money($kpis['collections_today']) ?></strong></div></article>
        <article class="kpi-card pink"><div class="kpi-icon">⚠</div><div class="kpi-content"><span>ديون الموزعين</span><strong><?= money($kpis['distributor_debt']) ?></strong></div></article>
        <article class="kpi-card teal"><div class="kpi-icon">▣</div><div class="kpi-content"><span>رصيد الصندوق</span><strong><?= money($kpis['cash_balance']) ?></strong></div></article>
        <article class="kpi-card amber"><div class="kpi-icon">▦</div><div class="kpi-content"><span>قيمة المخزون</span><strong><?= money($kpis['inventory_value']) ?></strong></div></article>
        <article class="kpi-card violet"><div class="kpi-icon">♙</div><div class="kpi-content"><span>عدد الموزعين</span><strong><?= int_num($kpis['distributors_count']) ?></strong></div></article>
        <article class="kpi-card blue"><div class="kpi-icon">◇</div><div class="kpi-content"><span>عدد الباقات</span><strong><?= int_num($kpis['packages_count']) ?> <small>(<?= int_num($kpis['packages_active']) ?> نشطة)</small></strong></div></article>
        <article class="kpi-card amber"><div class="kpi-icon">↓</div><div class="kpi-content"><span>سحوبات المالك</span><strong><?= money($kpis['owner_withdrawals']) ?></strong></div></article>
        <article class="kpi-card pink"><div class="kpi-icon">▣</div><div class="kpi-content"><span>إجمالي المصروفات</span><strong><?= money($kpis['expenses_total']) ?></strong></div></article>
        <article class="kpi-card violet"><div class="kpi-icon">⌁</div><div class="kpi-content"><span>عدد الخطوط</span><strong><?= int_num($kpis['lines_count']) ?></strong></div></article>
        <article class="kpi-card green"><div class="kpi-icon">▦</div><div class="kpi-content"><span>إجمالي الشدات المباعة</span><strong><?= int_num($kpis['total_bundles_sold']) ?></strong></div></article>
    </section>

    <?php if (!empty($lowStock)): ?>
    <section class="dashboard-panel alert-panel">
        <div class="section-title"><h3>⚠ تنبيهات المخزون المنخفض</h3><span style="color:#e11d48">⚠</span></div>
        <div class="low-stock-alerts">
            <?php foreach ($lowStock as $row): ?>
            <div class="low-stock-item">
                <span class="low-stock-name"><?= e($row['name']) ?></span>
                <span class="low-stock-count <?= (int)$row['bundles'] === 0 ? 'out' : 'low' ?>">
                    <?= (int)$row['bundles'] === 0 ? 'نفذ المخزون' : 'باقي ' . int_num($row['bundles']) . ' شدة' ?>
                </span>
                <span class="low-stock-threshold">الحد: <?= int_num($row['low_stock_threshold']) ?> شدة</span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="dashboard-panel">
        <div class="section-title"><h3>العمليات الأخيرة</h3><span>⌁</span></div>
        <?php if (empty($operations)): ?>
            <div class="empty-state">لا توجد عمليات حتى الآن</div>
        <?php else: ?>
            <?php foreach ($operations as $op): ?>
                <div class="operation">
                    <div>
                        <strong><?= e($op['distributor_name'] ?? 'نقدي') ?> — <?= e($op['package_name'] ?? '') ?></strong>
                        <small><?= ar_date($op['created_at'] ?? '') ?> • <?= int_num($op['bundles_count'] ?? 0) ?> شدة</small>
                    </div>
                    <span class="positive"><?= money($op['total'] ?? 0) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>حالة المخزون</h3><span>◇</span></div>
        <?php if (empty($inventory)): ?>
            <div class="empty-state">لا يوجد مخزون</div>
        <?php else: ?>
            <?php foreach ($inventory as $row): ?>
                <div class="inventory-row">
                    <span><?= e($row['name']) ?> — شدة <?= money($row['bundle_price']) ?></span>
                    <span class="stock-count <?= ((int)$row['bundles']) <= (int)($row['low_stock_threshold'] ?? 0) ? 'zero' : 'ok' ?>"><?= int_num($row['bundles']) ?> شدة</span>
                    <em><?= money($row['value']) ?></em>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>
