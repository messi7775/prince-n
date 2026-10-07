<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>التحصيلات</h2>
            <p>تحصيل دفعات الموزعين</p>
        </div>
    </section>

    <section class="dashboard-panel">
        <details class="form-collapse" id="payment-form-collapse">
            <summary class="btn primary" id="payment-form-summary">+ تحصيل جديد</summary>
            <form method="post" action="/payments/store" class="entity-form" id="payment-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="payment-id" value="">
                <div class="form-grid">
                    <label>الموزع
                        <select name="distributor_id" id="payment-distributor" required>
                            <option value="">— اختر —</option>
                            <?php foreach ($distributors as $d): ?>
                                <?php $bal = (int)$d['credit_total'] - (int)$d['paid_total']; ?>
                                <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?> — الرصيد: <?= money($bal) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>مبلغ التحصيل (ريال)<input name="amount" id="payment-amount" type="number" min="1" required></label>
                    <label>ملاحظة<input name="note" id="payment-note" placeholder="اختياري"></label>
                </div>
                <button class="btn primary" type="submit" id="payment-submit-btn">تسجيل التحصيل</button>
            </form>
        </details>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>البحث والفلترة</h3><span>⌕</span></div>
        <form method="get" action="/payments" class="entity-form">
            <div class="form-grid">
                <label>بحث<input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="موزع / ملاحظة"></label>
                <label>الموزع
                    <select name="distributor_id">
                        <option value="">الكل</option>
                        <?php foreach ($distributors as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= $filters['distributor_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>من تاريخ<input type="date" name="date_from" value="<?= e($filters['date_from']) ?>"></label>
                <label>إلى تاريخ<input type="date" name="date_to" value="<?= e($filters['date_to']) ?>"></label>
            </div>
            <button class="btn primary" type="submit">تطبيق</button>
            <a class="btn" href="/payments">مسح الفلاتر</a>
        </form>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>سجل التحصيلات (<?= money($total) ?>)</h3><span>♣</span></div>
        <?php if (empty($payments)): ?>
            <div class="empty-state">لا توجد تحصيلات مطابقة</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>التاريخ</th><th>الموزع</th><th>مبلغ التحصيل</th><th>الملاحظة</th><th>إجراءات</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= ar_date($p['created_at']) ?></td>
                        <td><?= e($p['distributor_name'] ?? '') ?></td>
                        <td><?= money($p['amount']) ?></td>
                        <td><?= e($p['note'] ?? '') ?></td>
                        <td class="actions-cell">
                            <div class="kebab">
                                <button class="kebab-btn" type="button" aria-label="إجراءات">⋮</button>
                                <div class="kebab-dropdown">
                                    <a class="kebab-item" href="/payments/receipt?id=<?= (int)$p['id'] ?>" target="_blank">🧾 وصل</a>
                                    <button class="kebab-item payment-edit-btn" type="button"
                                        data-id="<?= (int)$p['id'] ?>"
                                        data-distributor="<?= (int)$p['distributor_id'] ?>"
                                        data-amount="<?= (int)$p['amount'] ?>"
                                        data-note="<?= e($p['note'] ?? '') ?>">✎ تعديل</button>
                                    <form method="post" action="/payments/delete" class="inline-form" onsubmit="return confirm('حذف هذا التحصيل؟\n\nسيتم أيضًا إزالة أثره من الصندوق والرصيد.')">
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
(function() {
    var form          = document.getElementById('payment-form');
    var collapse      = document.getElementById('payment-form-collapse');
    var summary       = document.getElementById('payment-form-summary');
    var idField       = document.getElementById('payment-id');
    var distributor   = document.getElementById('payment-distributor');
    var amount        = document.getElementById('payment-amount');
    var note          = document.getElementById('payment-note');
    var submitBtn     = document.getElementById('payment-submit-btn');

    document.querySelectorAll('.payment-edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            idField.value = btn.dataset.id;
            distributor.value = btn.dataset.distributor || '';
            amount.value = btn.dataset.amount || '';
            note.value = btn.dataset.note || '';

            form.action = '/payments/update';
            submitBtn.textContent = 'حفظ التعديل';
            summary.textContent = '✎ تعديل تحصيل #' + btn.dataset.id;

            collapse.open = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
})();
</script>
