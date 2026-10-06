<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>المصروفات</h2>
            <p>مصروفات الشبكة</p>
        </div>
    </section>

    <section class="dashboard-panel">
        <details class="form-collapse">
            <summary class="btn primary">+ مصروف جديد</summary>
            <form method="post" action="/expenses/store" class="entity-form">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label>التصنيف<input name="category" required placeholder="مثال: كهرباء"></label>
                    <label>المبلغ (ريال)<input name="amount" type="number" min="1" required></label>
                    <label>ملاحظة<input name="note" placeholder="اختياري"></label>
                </div>
                <button class="btn primary" type="submit">تسجيل المصروف</button>
            </form>
        </details>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>إجمالي المصروفات: <?= money($total) ?></h3><span>▣</span></div>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>سجل المصروفات</h3><span>▣</span></div>
        <?php if (empty($expenses)): ?>
            <div class="empty-state">لا توجد مصروفات حتى الآن</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>التاريخ</th><th>التصنيف</th><th>المبلغ</th><th>ملاحظة</th><th>إجراءات</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $ex): ?>
                    <tr>
                        <td><?= ar_date($ex['created_at']) ?></td>
                        <td><?= e($ex['category']) ?></td>
                        <td style="color:#e6466a"><?= money($ex['amount']) ?></td>
                        <td><?= e($ex['note'] ?? '') ?></td>
                        <td class="actions-cell">
                            <form method="post" action="/expenses/delete" class="inline-form" onsubmit="return confirm('حذف هذا المصروف؟')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$ex['id'] ?>">
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
