<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>الموزعون</h2>
            <p>إدارة الموزعين وأرصدتهم</p>
        </div>
    </section>

    <?php $flashError = \Session::flashGet('error'); ?>
    <?php if ($flashError): ?>
    <div class="dashboard-panel alert-panel">
        <div class="alert-message" style="color:#e11d48; font-weight:bold; padding:12px 16px;">⚠ <?= e($flashError) ?></div>
    </div>
    <?php endif; ?>

    <section class="dashboard-panel">
        <details class="form-collapse">
            <summary class="btn primary">+ إضافة موزع جديد</summary>
            <form method="post" action="/distributors/store" class="entity-form">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label>الاسم<input name="name" required></label>
                    <label>الهاتف<input name="phone" placeholder="اختياري"></label>
                    <label>ملاحظة<input name="note" placeholder="اختياري"></label>
                </div>
                <button class="btn primary" type="submit">حفظ</button>
            </form>
        </details>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>البحث والفلترة</h3><span>⌕</span></div>
        <form method="get" action="/distributors" class="entity-form">
            <div class="form-grid">
                <label>بحث<input type="text" name="q" value="<?= e($q) ?>" placeholder="اسم / هاتف / ملاحظة"></label>
            </div>
            <button class="btn primary" type="submit">تطبيق</button>
            <a class="btn" href="/distributors">مسح الفلاتر</a>
        </form>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>قائمة الموزعين</h3><span>♙</span></div>
        <?php if (empty($distributors)): ?>
            <div class="empty-state">لا يوجد موزعون حتى الآن</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>الاسم</th><th>الهاتف</th><th>الآجل</th><th>التحصيل</th><th>الرصيد</th><th>إجراءات</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($distributors as $d): ?>
                    <?php $balance = (int)$d['credit_total'] - (int)$d['paid_total']; ?>
                    <tr>
                        <td><?= e($d['name']) ?></td>
                        <td><?= e($d['phone'] ?? '') ?></td>
                        <td><?= money($d['credit_total']) ?></td>
                        <td><?= money($d['paid_total']) ?></td>
                        <td><span class="badge <?= $balance > 0 ? 'zero' : 'ok' ?>"><?= money($balance) ?><?php if ($balance < 0): ?> — رصيد للموزع<?php elseif ($balance > 0): ?> — مستحق علينا<?php else: ?> — متعادل<?php endif; ?></span></td>
                        <td class="actions-cell">
                            <a class="btn sm" href="/distributors/show?id=<?= (int)$d['id'] ?>">كشف الحساب</a>
                            <form method="post" action="/distributors/delete" class="inline-form" onsubmit="return confirm('حذف هذا الموزع؟')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
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
