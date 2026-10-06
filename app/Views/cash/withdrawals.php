<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>سحوبات المالك</h2>
            <p>سجل سحوبات المالك من الصندوق</p>
        </div>
    </section>

    <section class="dashboard-panel">
        <details class="form-collapse">
            <summary class="btn primary">+ سحب جديد</summary>
            <form method="post" action="/owner-withdrawals/store" class="entity-form">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label>المبلغ (ريال)<input name="amount" type="number" min="1" required></label>
                    <label>ملاحظة<input name="note" placeholder="اختياري"></label>
                </div>
                <button class="btn primary" type="submit">تسجيل السحب</button>
            </form>
        </details>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>إجمالي السحوبات: <?= money($total) ?></h3><span>▱</span></div>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>سجل السحوبات</h3><span>▱</span></div>
        <?php if (empty($withdrawals)): ?>
            <div class="empty-state">لا توجد سحوبات حتى الآن</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>التاريخ</th><th>المبلغ</th><th>ملاحظة</th><th>إجراءات</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawals as $w): ?>
                    <tr>
                        <td><?= ar_date($w['created_at']) ?></td>
                        <td style="color:#e6466a"><?= money($w['amount']) ?></td>
                        <td><?= e($w['note'] ?? '') ?></td>
                        <td class="actions-cell">
                            <form method="post" action="/owner-withdrawals/delete" class="inline-form" onsubmit="return confirm('حذف هذا السحب؟')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
                                <button class="btn sm danger" type="submit">حذف</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
