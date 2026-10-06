<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>المخزون</h2>
            <p>إدارة مخزون الباقات — كل باقة لها سجل واحد تلقائيًا</p>
        </div>
    </section>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= e($error) ?></div>
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
        <div class="section-title"><h3>المخزون</h3><span>♧</span></div>
        <?php if (empty($items)): ?>
            <div class="empty-state">لا يوجد مخزون — أضف باقات جديدة من صفحة الباقات</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>الباقة</th><th>عدد الشدات</th><th>سعر الشدة</th><th>القيمة</th><th>إجراءات</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row): ?>
                    <tr>
                        <td><?= e($row['package_name']) ?></td>
                        <td><?= int_num($row['quantity']) ?><?= (int)$row['quantity'] < 0 ? ' (عجز)' : '' ?></td>
                        <td><?= money($row['bundle_price']) ?></td>
                        <td><?= money($row['quantity'] * $row['bundle_price']) ?></td>
                        <td class="actions-cell">
                            <div class="action-buttons">
                                <button class="btn sm primary" type="button"
                                    onclick="openInvForm('add-<?= (int)$row['id'] ?>')">إضافة</button>
                                <button class="btn sm" type="button"
                                    onclick="openInvForm('edit-<?= (int)$row['id'] ?>')">تعديل</button>
                                <form method="post" action="/inventory/delete" class="inline-form"
                                    onsubmit="return confirm('حذف سجل المخزون لهذه الباقة؟')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <button class="btn sm danger" type="submit">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr class="inv-form-row" id="add-<?= (int)$row['id'] ?>" style="display:none">
                        <td colspan="5">
                            <form method="post" action="/inventory/add" class="inline-inv-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <label>عدد الشدات المضافة: <input name="quantity" type="number" min="1" required placeholder="عدد الشدات"></label>
                                <button class="btn sm primary" type="submit">تأكيد الإضافة</button>
                                <button class="btn sm" type="button" onclick="closeInvForm('add-<?= (int)$row['id'] ?>')">إلغاء</button>
                            </form>
                        </td>
                    </tr>
                    <tr class="inv-form-row" id="edit-<?= (int)$row['id'] ?>" style="display:none">
                        <td colspan="5">
                            <form method="post" action="/inventory/edit" class="inline-inv-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <label>العدد الجديد: <input name="quantity" type="number" min="0" required value="<?= (int)$row['quantity'] ?>"></label>
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
                        <th>التاريخ والوقت</th>
                        <th>الباقة</th>
                        <th>نوع الحركة</th>
                        <th>العدد السابق</th>
                        <th>العدد المضاف/الجديد</th>
                        <th>العدد بعد الحركة</th>
                        <th>سعر الشدة</th>
                        <th>القيمة</th>
                        <th>الملاحظة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $m): ?>
                    <tr>
                        <td><?= e($m['created_at']) ?></td>
                        <td><?= e($m['package_name']) ?></td>
                        <td>
                            <?php
                                $labels  = ['add' => 'إضافة', 'edit' => 'تعديل', 'delete' => 'حذف'];
                                $classes = ['add' => 'ok', 'edit' => '', 'delete' => 'zero'];
                            ?>
                            <span class="badge <?= $classes[$m['action']] ?? '' ?>"><?= $labels[$m['action']] ?? $m['action'] ?></span>
                        </td>
                        <td><?= int_num($m['old_quantity']) ?></td>
                        <td>
                            <?php
                                $diff = (int)$m['new_quantity'] - (int)$m['old_quantity'];
                                if ($m['action'] === 'edit') {
                                    echo int_num($m['new_quantity']);
                                } else {
                                    echo ($diff >= 0 ? '+' : '') . int_num(abs($diff)) . ($diff >= 0 ? '' : ' (حذف)');
                                }
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
</script>
