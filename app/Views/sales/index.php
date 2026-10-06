<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>المبيعات</h2>
            <p>بيع البطاقات بالشدة</p>
        </div>
    </section>

    <section class="dashboard-panel">
        <details class="form-collapse" id="sale-form-collapse">
            <summary class="btn primary" id="sale-form-summary">+ عملية بيع جديدة</summary>
            <form method="post" action="/sales/store" class="entity-form" id="sale-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="sale-id" value="">
                <div class="form-grid">
                    <label>الباقة
                        <select name="package_id" id="sale-package" required>
                            <option value="">— اختر —</option>
                            <?php foreach ($packages as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" data-bundle-price="<?= (int)$p['bundle_price'] ?>"><?= e($p['name']) ?> — شدة <?= money($p['bundle_price']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>الموزع <span class="muted">(اختياري للنقدي، مطلوب للآجل)</span>
                        <select name="distributor_id" id="sale-distributor">
                            <option value="">— اختر موزع —</option>
                            <?php foreach ($distributors as $d): ?>
                                <?php $bal = (int)$d['credit_total'] - (int)$d['paid_total']; ?>
                                <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?> — رصيد: <?= money($bal) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>عدد الشدات<input name="bundles_count" id="sale-bundles" type="number" min="1" required value="1"></label>
                    <label>سعر الشدة (ريال)<input id="sale-price" type="number" min="0" readonly></label>
                    <label>نوع البيع
                        <select name="payment_type" id="sale-type">
                            <option value="cash">نقدي</option>
                            <option value="credit">آجل</option>
                        </select>
                    </label>
                    <label>ملاحظة<input name="note" id="sale-note" placeholder="اختياري"></label>
                </div>
                <div class="sale-total">الإجمالي: <strong id="sale-total-display">0</strong> ريال</div>
                <button class="btn primary" type="submit" id="sale-submit-btn">تسجيل البيع</button>
            </form>
        </details>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>العمليات</h3><span>🛒</span></div>
        <?php if (empty($sales)): ?>
            <div class="empty-state">لا توجد عمليات بيع حتى الآن</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>التاريخ</th><th>الموزع</th><th>الباقة</th><th>الشدات</th><th>سعر(ش)</th><th>الإجمالي</th><th>نوع البيع</th><th>إجراءات</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $s): ?>
                        <?php
                            $typeLabel = $s['payment_type'] === 'cash' ? 'نقدي' : 'آجل';
                            $typeBadge = $s['payment_type'] === 'cash' ? 'ok' : 'zero';
                        ?>
                    <tr>
                        <td><?= ar_date($s['created_at']) ?></td>
                        <td><?= e($s['distributor_name'] ?? '') ?></td>
                        <td><?= e($s['package_name'] ?? '') ?></td>
                        <td><?= int_num($s['bundles_count']) ?></td>
                        <td><?= money($s['bundle_price']) ?></td>
                        <td><?= money($s['total']) ?></td>
                        <td><span class="badge <?= $typeBadge ?>"><?= $typeLabel ?></span></td>
                        <td class="actions-cell">
                            <button class="btn sm primary sale-edit-btn" type="button"
                                data-id="<?= (int)$s['id'] ?>"
                                data-package="<?= (int)$s['package_id'] ?>"
                                data-distributor="<?= (int)$s['distributor_id'] ?>"
                                data-bundles="<?= (int)$s['bundles_count'] ?>"
                                data-type="<?= e($s['payment_type']) ?>"
                                data-note="<?= e($s['note'] ?? '') ?>">تعديل</button>
                            <form method="post" action="/sales/delete" class="inline-form" onsubmit="return confirm('حذف هذه العملية؟')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
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
(function() {
    var form      = document.getElementById('sale-form');
    var collapse  = document.getElementById('sale-form-collapse');
    var summary   = document.getElementById('sale-form-summary');
    var idField   = document.getElementById('sale-id');
    var pkg       = document.getElementById('sale-package');
    var bundles   = document.getElementById('sale-bundles');
    var price     = document.getElementById('sale-price');
    var note      = document.getElementById('sale-note');
    var type      = document.getElementById('sale-type');
    var distributor = document.getElementById('sale-distributor');
    var display   = document.getElementById('sale-total-display');
    var submitBtn = document.getElementById('sale-submit-btn');

    function calc() {
        var b = parseInt(bundles.value) || 0;
        var p = parseInt(price.value) || 0;
        var total = b * p;
        display.textContent = total.toLocaleString();
    }

    pkg.addEventListener('change', function() {
        var opt = pkg.options[pkg.selectedIndex];
        var bp = opt.getAttribute('data-bundle-price');
        if (bp) price.value = bp;
        calc();
    });
    bundles.addEventListener('input', calc);
    type.addEventListener('change', function() {
        distributor.required = type.value === 'credit';
        calc();
    });
    distributor.required = type.value === 'credit';
    calc();

    // Edit button handler
    document.querySelectorAll('.sale-edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            idField.value      = btn.dataset.id;
            pkg.value           = btn.dataset.package;
            distributor.value = btn.dataset.distributor || '';
            distributor.required = btn.dataset.type === 'credit';
            bundles.value       = btn.dataset.bundles;
            var selectedOption = pkg.options[pkg.selectedIndex];
            price.value         = selectedOption ? (selectedOption.getAttribute('data-bundle-price') || '') : '';
            type.value          = btn.dataset.type;
            note.value          = btn.dataset.note;

            form.action         = '/sales/update';
            submitBtn.textContent = 'حفظ التعديل';
            summary.textContent  = '✎ تعديل عملية بيع #' + btn.dataset.id;

            calc();
            collapse.open = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
})();
</script>
