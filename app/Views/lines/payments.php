<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>تسديد الخطوط</h2>
            <p>سجل تسديدات الخطوط — جميع التسديدات تخرج من الصندوق</p>
        </div>
    </section>

    <section class="dashboard-panel">
        <details class="form-collapse">
            <summary class="btn primary">+ تسديد خط جديد</summary>
            <form method="post" action="/line-payments/store" class="entity-form" id="line-payment-form">
                <input type="hidden" name="id" id="line-payment-id">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label>الخط
                        <select name="line_id" id="line-payment-line" required>
                            <option value="">— اختر —</option>
                            <?php foreach ($lines as $l): ?>
                                <option value="<?= (int)$l['id'] ?>"><?= e($l['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>المبلغ (ريال)<input name="amount" id="line-payment-amount" type="number" min="1" required></label>
                    <label>النوع
                        <input value="خرج — تسديد من الصندوق" disabled>
                    </label>
                    <label>ملاحظة<input name="note" id="line-payment-note" placeholder="اختياري"></label>
                </div>
                <button class="btn primary" id="line-payment-submit" type="submit">تسجيل التسديد</button>
            </form>
        </details>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>البحث والفلترة</h3><span>⌕</span></div>
        <form method="get" action="/line-payments" class="entity-form">
            <div class="form-grid">
                <label>بحث<input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="خط / ملاحظة"></label>
                <label>الخط
                    <select name="line_id">
                        <option value="">الكل</option>
                        <?php foreach ($lines as $l): ?>
                            <option value="<?= (int)$l['id'] ?>" <?= $filters['line_id'] === (int)$l['id'] ? 'selected' : '' ?>><?= e($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>من تاريخ<input type="date" name="date_from" value="<?= e($filters['date_from']) ?>"></label>
                <label>إلى تاريخ<input type="date" name="date_to" value="<?= e($filters['date_to']) ?>"></label>
            </div>
            <button class="btn primary" type="submit">تطبيق</button>
            <a class="btn" href="/line-payments">مسح الفلاتر</a>
        </form>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>سجل تسديدات الخطوط (<?= money($total) ?>)</h3><span>▭</span></div>
        <?php if (empty($payments)): ?>
            <div class="empty-state">لا توجد تسديدات مطابقة</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>التاريخ</th><th>الخط</th><th>المبلغ</th><th>النوع</th><th>ملاحظة</th><th>إجراءات</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= ar_date($p['created_at']) ?></td>
                        <td><?= e($p['line_name'] ?? '') ?></td>
                        <td><?= money($p['amount']) ?></td>
                        <td><span class="badge zero">خرج</span></td>
                        <td><?= e($p['note'] ?? '') ?></td>
                        <td class="actions-cell">
                            <div class="kebab">
                                <button class="kebab-btn" type="button" aria-label="إجراءات">⋮</button>
                                <div class="kebab-dropdown">
                                    <button class="kebab-item line-payment-edit-btn" type="button" data-id="<?= (int)$p['id'] ?>" data-line-id="<?= (int)$p['line_id'] ?>" data-amount="<?= (int)$p['amount'] ?>" data-note="<?= e($p['note'] ?? '') ?>">✎ تعديل</button>
                                    <form method="post" action="/line-payments/delete" class="inline-form" onsubmit="return confirm('حذف هذا التسديد؟ سيتم أيضًا إزالة أثره من الصندوق.')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        <button class="kebab-item danger" type="submit">🗑 حذف</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>

<script>
document.querySelectorAll('.line-payment-edit-btn').forEach(function (button) {
    button.addEventListener('click', function () {
        const form = document.getElementById('line-payment-form');
        document.getElementById('line-payment-id').value = button.dataset.id;
        document.getElementById('line-payment-line').value = button.dataset.lineId;
        document.getElementById('line-payment-amount').value = button.dataset.amount;
        document.getElementById('line-payment-note').value = button.dataset.note || '';
        form.action = '/line-payments/update';
        document.getElementById('line-payment-submit').textContent = 'حفظ التعديل';
        document.querySelector('.form-collapse summary').textContent = '✎ تعديل تسديد #' + button.dataset.id;
        document.querySelector('.form-collapse').open = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
</script>
