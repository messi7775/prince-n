<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>كشف حساب: <?= e($distributor['name']) ?></h2>
            <p>ملف الموزع وحركاته بالتسلسل الزمني</p>
        </div>
        <a class="btn" href="/distributors">← رجوع للموزعين</a>
    </section>

    <section class="kpi-grid" style="grid-template-columns:repeat(4,1fr)">
        <article class="kpi-card blue"><div class="kpi-icon">♙</div><div class="kpi-content"><span>الموزع</span><strong><?= e($distributor['name']) ?></strong></div></article>
        <article class="kpi-card violet"><div class="kpi-icon">☎</div><div class="kpi-content"><span>الهاتف</span><strong><?= e($distributor['phone'] ?? '—') ?></strong></div></article>
        <article class="kpi-card amber"><div class="kpi-icon">⌁</div><div class="kpi-content"><span>آخر عملية</span><strong><?= $lastActivity ? ar_date($lastActivity) : '—' ?></strong></div></article>
        <?php $trueBalance = (int)$creditTotal - (int)$paidTotal; ?>
        <article class="kpi-card <?= $trueBalance > 0 ? 'pink' : ($trueBalance < 0 ? 'teal' : 'green') ?>"><div class="kpi-icon">▣</div><div class="kpi-content"><span>حالة الحساب</span><strong><?= $trueBalance > 0 ? 'مستحق عليه' : ($trueBalance < 0 ? 'رصيد للموزع' : 'متعادل') ?></strong></div></article>
    </section>

    <?php if ($distributor['note']): ?>
    <section class="dashboard-panel">
        <div class="alert-message" style="padding:8px 16px;">ملاحظات: <?= e($distributor['note']) ?></div>
    </section>
    <?php endif; ?>

    <section class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
        <article class="kpi-card violet"><div class="kpi-content"><span>إجمالي المبيعات (آجل)</span><strong><?= money($salesTotal) ?></strong></div></article>
        <article class="kpi-card green"><div class="kpi-content"><span>إجمالي التحصيلات</span><strong><?= money($paymentsTotal) ?></strong></div></article>
        <article class="kpi-card <?= $finalBalance > 0 ? 'pink' : 'teal' ?>"><div class="kpi-content"><span>الرصيد النهائي</span><strong><?= money($finalBalance) ?></strong></div></article>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>البحث والفلترة</h3><span>⌕</span></div>
        <form method="get" action="/distributors/show" class="entity-form">
            <input type="hidden" name="id" value="<?= (int)$distributor['id'] ?>">
            <div class="form-grid">
                <label>من تاريخ<input type="date" name="date_from" value="<?= e($filters['date_from']) ?>"></label>
                <label>إلى تاريخ<input type="date" name="date_to" value="<?= e($filters['date_to']) ?>"></label>
                <label>نوع الحركة
                    <select name="type">
                        <option value="">الكل</option>
                        <option value="sale" <?= $filters['type'] === 'sale' ? 'selected' : '' ?>>بيع آجل</option>
                        <option value="payment" <?= $filters['type'] === 'payment' ? 'selected' : '' ?>>تحصيل</option>
                    </select>
                </label>
            </div>
            <button class="btn primary" type="submit">تطبيق</button>
            <a class="btn" href="/distributors/show?id=<?= (int)$distributor['id'] ?>">مسح الفلاتر</a>
        </form>
    </section>

    <section class="dashboard-panel">
        <div class="section-title">
            <h3>كشف الحساب</h3>
            <button class="btn sm" type="button" onclick="window.print()">🖨 طباعة</button>
        </div>
        <?php if (empty($ledger)): ?>
            <div class="empty-state">لا توجد حركات مطابقة</div>
        <?php else: ?>
            <?php if ($opening !== 0 && $filters['type'] === ''): ?>
            <div class="alert-message" style="padding:8px 16px;">رصيد افتتاحي قبل <?= ar_date($filters['date_from']) ?>: <strong><?= money($opening) ?></strong></div>
            <?php endif; ?>
            <table class="data-table" id="statement-table">
                <thead>
                    <tr><th>رقم العملية</th><th>التاريخ</th><th>نوع الحركة</th><th>البيان</th><th>مدين</th><th>دائن</th><th>الرصيد بعد الحركة</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($ledger as $row): ?>
                    <tr>
                        <td><?= $row['kind'] === 'sale' ? 'ب' : 'ت' ?>-<?= (int)$row['ref_id'] ?></td>
                        <td><?= ar_date($row['created_at']) ?></td>
                        <td><span class="badge <?= $row['kind'] === 'sale' ? 'zero' : 'ok' ?>"><?= $row['kind'] === 'sale' ? 'بيع آجل' : 'تحصيل' ?></span></td>
                        <td><?= e($row['description']) ?></td>
                        <td><?= (int)$row['debit'] > 0 ? money($row['debit']) : '—' ?></td>
                        <td><?= (int)$row['credit'] > 0 ? money($row['credit']) : '—' ?></td>
                        <td><strong><?= money($row['running']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"><strong>الإجمالي</strong></td>
                        <td><strong><?= money($salesTotal) ?></strong></td>
                        <td><strong><?= money($paymentsTotal) ?></strong></td>
                        <td><strong><?= money($finalBalance) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </section>
</div>
