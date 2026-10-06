<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>الخطوط</h2>
            <p>إدارة الخطوط وأرصدتها</p>
        </div>
    </section>

    <section class="dashboard-panel">
        <details class="form-collapse" id="line-form-collapse">
            <summary class="btn primary" id="line-form-summary">+ إضافة خط جديد</summary>
            <form method="post" action="/lines/store" class="entity-form" id="line-form">
                <input type="hidden" name="id" id="line-id">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label>اسم الخط<input name="name" id="line-name" required></label>
                    <label>المزود<input name="provider" id="line-provider" placeholder="اختياري"></label>
                    <label>ملاحظة<input name="note" id="line-note" placeholder="اختياري"></label>
                </div>
                <button class="btn primary" type="submit" id="line-submit-btn">حفظ</button>
            </form>
        </details>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>البحث والفلترة</h3><span>⌕</span></div>
        <form method="get" action="/lines" class="entity-form">
            <div class="form-grid">
                <label>بحث<input type="text" name="q" value="<?= e($q ?? '') ?>" placeholder="اسم الخط / المزود"></label>
            </div>
            <button class="btn primary" type="submit">بحث</button>
            <a class="btn" href="/lines">مسح</a>
        </form>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>قائمة الخطوط</h3><span>⌁</span></div>
        <?php if (empty($lines)): ?>
            <div class="empty-state">لا توجد خطوط حتى الآن</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>الاسم</th><th>المزود</th><th>الرصيد</th><th>إجراءات</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $l): ?>
                    <tr>
                        <td><?= e($l['name']) ?></td>
                        <td><?= e($l['provider'] ?? '') ?></td>
                        <td><span class="badge <?= (int)$l['balance'] >= 0 ? 'ok' : 'zero' ?>"><?= money($l['balance']) ?></span></td>
                        <td class="actions-cell">
                            <button class="btn sm primary line-edit-btn" type="button" data-id="<?= (int)$l['id'] ?>" data-name="<?= e($l['name']) ?>" data-provider="<?= e($l['provider'] ?? '') ?>" data-note="<?= e($l['note'] ?? '') ?>">تعديل</button>
                            <form method="post" action="/lines/delete" class="inline-form" onsubmit="return confirm('حذف هذا الخط؟')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
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

<script>
document.querySelectorAll('.line-edit-btn').forEach(function (button) {
    button.addEventListener('click', function () {
        const form = document.getElementById('line-form');
        document.getElementById('line-id').value = button.dataset.id;
        document.getElementById('line-name').value = button.dataset.name || '';
        document.getElementById('line-provider').value = button.dataset.provider || '';
        document.getElementById('line-note').value = button.dataset.note || '';
        form.action = '/lines/update';
        document.getElementById('line-submit-btn').textContent = 'حفظ التعديل';
        document.getElementById('line-form-summary').textContent = '✎ تعديل خط #' + button.dataset.id;
        document.getElementById('line-form-collapse').open = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
</script>
