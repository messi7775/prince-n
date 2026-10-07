<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>المخزون</h2>
            <p>إدارة دفعات المخزون — كل باقة يمكن أن يكون لها عدة دفعات</p>
        </div>
    </section>

    <?php $flashError = \Session::flashGet('error'); ?>
    <?php if ($flashError): ?>
        <div class="dashboard-panel alert-panel">
            <div class="alert-message" style="color:#e11d48; font-weight:bold; padding:12px 16px;">⚠ <?= e($flashError) ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($lowStock)): ?>
    <section class="dashboard-panel alert-panel">
        <div class="section-title"><h3>⚠ تنبيهات المخزون المنخفض</h3><span style="color:#e11d48">⚠</span></div>
        <div class="low-stock-alerts">
            <?php foreach ($lowStock as $row): ?>
            <div class="low-stock-item">
                <span class="low-stock-name"><?= e($row['name']) ?></span>
                <span class="low-stock-count <?= (int)$row['bundles'] <= 0 ? 'out' : 'low' ?>">
                    <?= (int)$row['bundles'] <= 0 ? 'نفذ المخزون' : 'باقي ' . int_num($row['bundles']) . ' شدة' ?>
                </span>
                <span class="low-stock-threshold">الحد: <?= int_num($row['low_stock_threshold']) ?> شدة</span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="dashboard-panel">
        <details class="form-collapse">
            <summary class="btn primary">+ دفعة مخزون جديدة</summary>
            <form method="post" action="/inventory/store" class="entity-form">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label>الباقة
                        <select name="package_id" id="batch-package" required>
                            <option value="">— اختر —</option>
                            <?php foreach ($packages as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" data-bundle-price="<?= (int)$p['bundle_price'] ?>"><?= e($p['name']) ?> — شدة <?= money($p['bundle_price']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>سعر البيع للشدة (ريال)<input name="bundle_price" id="batch-price" type="number" min="0" placeholder="افتراضي سعر الباقة"></label>
                    <label>عدد الشدات<input name="quantity" type="number" min="1" required></label>
                    <label>ملاحظة<input name="note" placeholder="اختياري"></label>
                </div>
                <button class="btn primary" type="submit">إضافة الدفعة</button>
            </form>
        </details>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>البحث والفلترة</h3><span>⌕</span></div>
        <form method="get" action="/inventory" class="entity-form">
            <div class="form-grid">
                <label>بحث<input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="باقة / ملاحظة"></label>
                <label>الباقة
                    <select name="package_id">
                        <option value="">الكل</option>
                        <?php foreach ($packages as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= $filters['package_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <button class="btn primary" type="submit">تطبيق</button>
            <a class="btn" href="/inventory">مسح الفلاتر</a>
        </form>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>دفعات المخزون</h3><span>♧</span></div>
        <?php if (empty($items)): ?>
            <div class="empty-state">لا يوجد مخزون — أضف دفعة جديدة</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>التاريخ</th><th>الباقة</th><th>سعر البيع</th><th>الكمية</th><th>المباع</th><th>المتبقي</th><th>ملاحظة</th><th>إجراءات</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row): ?>
                    <?php $remaining = (int)$row['remaining']; ?>
                    <tr>
                        <td><?= ar_date($row['created_at']) ?></td>
                        <td><?= e($row['package_name']) ?></td>
                        <td><?= money($row['bundle_price']) ?></td>
                        <td><?= int_num($row['quantity']) ?></td>
                        <td><?= int_num($row['sold']) ?></td>
                        <td><span class="badge <?= $remaining <= 0 ? 'zero' : 'ok' ?>"><?= int_num($remaining) ?><?= $remaining < 0 ? ' (عجز)' : '' ?></span></td>
                        <td><?= e($row['note'] ?? '') ?></td>
                        <td class="actions-cell">
                            <div class="kebab">
                                <button class="kebab-btn" type="button" aria-label="إجراءات">⋮</button>
                                <div class="kebab-dropdown">
                                    <button class="kebab-item" type="button"
                                        onclick="openInvForm('edit-<?= (int)$row['id'] ?>')">✎ تعديل</button>
                                    <form method="post" action="/inventory/delete" class="inline-form"
                                        onsubmit="return confirm('حذف هذه الدفعة؟')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button class="kebab-item danger" type="submit">🗑 حذف</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr class="inv-form-row" id="edit-<?= (int)$row['id'] ?>" style="display:none">
                        <td colspan="8">
                            <form method="post" action="/inventory/edit" class="inline-inv-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <label>الكمية: <input name="quantity" type="number" min="0" required value="<?= (int)$row['quantity'] ?>"></label>
                                <label>ملاحظة: <input name="note" value="<?= e($row['note'] ?? '') ?>"></label>
                                <button class="btn sm primary" type="submit">تأكيد التعديل</button>
                                <button class="btn sm" type="button" onclick="closeInvForm('edit-<?= (int)$row['id'] ?>')">إلغاء</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>حركة المخزون</h3><span>↻</span></div>
        <?php if (empty($movements)): ?>
            <div class="empty-state">لا توجد حركات مخزون حتى الآن</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الباقة</th>
                        <th>الدفعة</th>
                        <th>نوع الحركة</th>
                        <th>السابق</th>
                        <th>الحركة</th>
                        <th>بعد الحركة</th>
                        <th>سعر الشدة</th>
                        <th>القيمة</th>
                        <th>الملاحظة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $m): ?>
                    <tr>
                        <td><?= ar_date($m['created_at']) ?></td>
                        <td><?= e($m['package_name']) ?></td>
                        <td><?= $m['inventory_id'] !== null ? '#' . (int)$m['inventory_id'] : '—' ?></td>
                        <td>
                            <?php
                                $labels  = [
                                    'add'         => 'إضافة مخزون',
                                    'edit'        => 'تعديل',
                                    'delete'      => 'حذف',
                                    'sale'        => 'بيع',
                                    'sale_delete' => 'حذف بيع',
                                    'return'      => 'إرجاع/تصحيح',
                                ];
                                $classes = [
                                    'add'         => 'ok',
                                    'edit'        => '',
                                    'delete'      => 'zero',
                                    'sale'        => 'low',
                                    'sale_delete' => 'ok',
                                    'return'      => '',
                                ];
                            ?>
                            <span class="badge <?= $classes[$m['action']] ?? '' ?>"><?= $labels[$m['action']] ?? e($m['action']) ?></span>
                        </td>
                        <td><?= int_num($m['old_quantity']) ?></td>
                        <td>
                            <?php
                                $diff = (int)$m['new_quantity'] - (int)$m['old_quantity'];
                                echo ($diff >= 0 ? '+' : '') . int_num($diff);
                            ?>
                        </td>
                        <td><?= int_num($m['new_quantity']) ?></td>
                        <td><?= money($m['bundle_price']) ?></td>
                        <td><?= money($m['new_value']) ?></td>
                        <td><?= e($m['note'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>

<script>
function openInvForm(id) {
    document.querySelectorAll('.inv-form-row').forEach(function(el) { el.style.display = 'none'; });
    var el = document.getElementById(id);
    if (el) el.style.display = '';
}
function closeInvForm(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = 'none';
}
(function() {
    var pkg = document.getElementById('batch-package');
    var price = document.getElementById('batch-price');
    if (pkg && price) {
        pkg.addEventListener('change', function() {
            var opt = pkg.options[pkg.selectedIndex];
            var bp = opt.getAttribute('data-bundle-price');
            if (bp) price.value = bp;
        });
    }
})();
</script>
