<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>الصندوق</h2>
            <p>الحركة النقدية</p>
        </div>
    </section>

    <section class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
        <article class="kpi-card teal"><div class="kpi-icon">▣</div><div class="kpi-content"><span>رصيد الصندوق</span><strong><?= money($balance) ?></strong></div></article>
        <article class="kpi-card green"><div class="kpi-icon">↗</div><div class="kpi-content"><span>إجمالي المقبوضات</span><strong><?= money($totalIn) ?></strong></div></article>
        <article class="kpi-card pink"><div class="kpi-icon">↓</div><div class="kpi-content"><span>إجمالي المدفوعات</span><strong><?= money($totalOut) ?></strong></div></article>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>الحركات النقدية</h3><span>▥</span></div>
        <?php if (empty($movements)): ?>
            <div class="empty-state">لا توجد حركات نقدية حتى الآن</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>التاريخ</th><th>نوع الحركة</th><th>البيان</th><th>المبلغ</th><th>الرصيد بعد الحركة</th><th>المرجع</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $m): ?>
                    <tr>
                        <td><?= ar_date($m['created_at']) ?></td>
                        <td><span class="badge <?= $m['direction'] === 'in' ? 'ok' : 'zero' ?>"><?= $m['direction'] === 'in' ? 'دخول' : 'خروج' ?></span></td>
                        <td><?= e($m['reason']) ?></td>
                        <td style="color:<?= $m['direction'] === 'in' ? '#18a078' : '#e6466a' ?>"><?= money($m['amount']) ?></td>
                        <td><strong><?= money($m['running']) ?></strong></td>
                        <td><?= e($m['reference_type'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
