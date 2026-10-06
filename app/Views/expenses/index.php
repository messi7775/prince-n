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
        <div class="section-title"><h3>البحث والفلترة</h3><span>⌕</span></div>
        <form method="get" action="/expenses" class="entity-form">
            <div class="form-grid">
                <label>بحث<input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="تصنيف / ملاحظة"></label>
                <label>التصنيف
                    <select name="category">
                        <option value="">الكل</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= e($c['category']) ?>" <?= $filters['category'] === $c['category'] ? 'selected' : '' ?>><?= e($c['category']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>من تاريخ<input type="date" name="date_from" value="<?= e($filters['date_from']) ?>"></label>
                <label>إلى تاريخ<input type="date" name="date_to" value="<?= e($filters['date_to']) ?>"></label>
            </div>
            <button class="btn primary" type="submit">تطبيق</button>
            <a class="btn" href="/expenses">مسح الفلاتر</a>
        </form>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>إجمالي المصروفات المطابقة: <?= money($total) ?></h3><span>▣</span></div>
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
                            <div class="action-buttons">
                                <button class="btn sm primary" type="button"
                                    onclick="openRowForm('exp-edit-<?= (int)$ex['id'] ?>')">تعديل</button>
                                <form method="post" action="/expenses/delete" class="inline-form" onsubmit="return confirm('حذف هذا المصروف؟')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$ex['id'] ?>">
                                    <button class="btn sm danger" type="submit">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr class="inv-form-row" id="exp-edit-<?= (int)$ex['id'] ?>" style="display:none">
                        <td colspan="5">
                            <form method="post" action="/expenses/update" class="inline-inv-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$ex['id'] ?>">
                                <label>التصنيف: <input name="category" required value="<?= e($ex['category']) ?>"></label>
                                <label>المبلغ: <input name="amount" type="number" min="1" required value="<?= (int)$ex['amount'] ?>"></label>
                                <label>ملاحظة: <input name="note" value="<?= e($ex['note'] ?? '') ?>"></label>
                                <button class="btn sm primary" type="submit">تأكيد التعديل</button>
                                <button class="btn sm" type="button" onclick="closeRowForm('exp-edit-<?= (int)$ex['id'] ?>')">إلغاء</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>

<script>
function openRowForm(id) {
    document.querySelectorAll('.inv-form-row').forEach(function(el) { el.style.display = 'none'; });
    var el = document.getElementById(id);
    if (el) el.style.display = '';
}
function closeRowForm(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = 'none';
}
</script>
