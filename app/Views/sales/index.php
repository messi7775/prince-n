<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>المبيعات</h2>
            <p>بيع البطاقات بالشدة</p>
        </div>
    </section>

    <?php $flashError = \Session::flashGet('error'); ?>
    <?php if ($flashError): ?>
    <div class="dashboard-panel alert-panel">
        <div class="alert-message" style="color:#e11d48; font-weight:bold; padding:12px 16px;">⚠ <?= e($flashError) ?></div>
    </div>
    <?php endif; ?>

    <section class="dashboard-panel">
        <details class="form-collapse" id="sale-form-collapse">
            <summary class="btn primary" id="sale-form-summary">+ عملية بيع جديدة</summary>
            <form method="post" action="/sales/store" class="entity-form" id="sale-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="sale-id" value="">
                <div class="form-grid">
                    <label>الموزع
                        <select name="distributor_id" id="sale-distributor">
                            <option value="">— اختر موزع —</option>
                            <?php foreach ($distributors as $d): ?>
                                <?php $bal = (int)$d['credit_total'] - (int)$d['paid_total']; ?>
                                <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?> — رصيد: <?= money($bal) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>نوع البيع
                        <select name="payment_type" id="sale-type">
                            <option value="cash">نقدي</option>
                            <option value="credit">آجل</option>
                        </select>
                    </label>
                    <label>ملاحظة<input name="note" id="sale-note" placeholder="اختياري"></label>
                </div>
                <div class="sale-items-block">
                    <div class="sale-items-head"><strong>الباقات المبيعة</strong><button class="btn sm" type="button" id="sale-add-item">+ إضافة باقة</button></div>
                    <div id="sale-items-rows"></div>
                    <template id="sale-item-tpl">
                        <div class="sale-item-row">
                            <select name="package_id[]" class="item-package" required>
                                <option value="">— اختر —</option>
                                <?php foreach ($packages as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>" data-bundle-price="<?= (int)$p['bundle_price'] ?>"><?= e($p['name']) ?> — شدة <?= money($p['bundle_price']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input name="bundles_count[]" class="item-bundles" type="number" min="1" value="1" required title="عدد الشدات">
                            <input class="item-price" type="number" min="0" readonly tabindex="-1" title="سعر الشدة">
                            <button type="button" class="btn sm sale-remove-item" title="إزالة الباقة">✕</button>
                        </div>
                    </template>
                </div>
                <div class="sale-total">الإجمالي: <strong id="sale-total-display">0</strong> ريال</div>
                <button class="btn primary" type="submit" id="sale-submit-btn">تسجيل البيع</button>
            </form>
        </details>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>البحث والفلترة</h3><span>⌕</span></div>
        <form method="get" action="/sales" class="filter-bar">
            <div class="filter-field grow">
                <label for="f-q">بحث</label>
                <input id="f-q" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="موزع / باقة / ملاحظة">
            </div>
            <div class="filter-field">
                <label for="f-from">من تاريخ</label>
                <input id="f-from" type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="filter-field">
                <label for="f-to">إلى تاريخ</label>
                <input id="f-to" type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="filter-field">
                <label for="f-dist">الموزع</label>
                <select id="f-dist" name="distributor_id">
                    <option value="">الكل</option>
                    <?php foreach ($distributors as $d): ?>
                        <option value="<?= (int)$d['id'] ?>" <?= $filters['distributor_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label for="f-pkg">الباقة</label>
                <select id="f-pkg" name="package_id">
                    <option value="">الكل</option>
                    <?php foreach ($packages as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= $filters['package_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label for="f-type">نوع البيع</label>
                <select id="f-type" name="payment_type">
                    <option value="">الكل</option>
                    <option value="cash" <?= $filters['payment_type'] === 'cash' ? 'selected' : '' ?>>نقدي</option>
                    <option value="credit" <?= $filters['payment_type'] === 'credit' ? 'selected' : '' ?>>آجل</option>
                </select>
            </div>
            <div class="filter-field">
                <label for="f-sort">ترتيب حسب</label>
                <select id="f-sort" name="sort">
                    <option value="date" <?= $filters['sort'] === 'date' ? 'selected' : '' ?>>التاريخ</option>
                    <option value="total" <?= $filters['sort'] === 'total' ? 'selected' : '' ?>>الإجمالي</option>
                </select>
            </div>
            <div class="filter-field">
                <label for="f-dir">الاتجاه</label>
                <select id="f-dir" name="dir">
                    <option value="desc" <?= $filters['dir'] === 'desc' ? 'selected' : '' ?>>تنازلي</option>
                    <option value="asc" <?= $filters['dir'] === 'asc' ? 'selected' : '' ?>>تصاعدي</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn primary" type="submit">تطبيق</button>
                <a class="btn" href="/sales">مسح الفلاتر</a>
            </div>
        </form>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>العمليات (<?= int_num($total) ?>)</h3><span>🛒</span></div>
        <?php if (empty($sales)): ?>
            <div class="empty-state">لا توجد عمليات بيع مطابقة</div>
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
                        <td><?= (int)($s['items_count'] ?? 1) === 1 ? money($s['item_price'] ?? $s['bundle_price']) : 'متنوعة' ?></td>
                        <td><?= money($s['total']) ?></td>
                        <td><span class="badge <?= $typeBadge ?>"><?= $typeLabel ?></span></td>
                        <td class="actions-cell">
                            <div class="kebab">
                                <button class="kebab-btn" type="button" aria-label="إجراءات">⋮</button>
                                <div class="kebab-dropdown">
                                    <a class="kebab-item" href="/sales/receipt?id=<?= (int)$s['id'] ?>" target="_blank">🧾 وصل</a>
                                    <button class="kebab-item sale-edit-btn" type="button"
                                        data-id="<?= (int)$s['id'] ?>"
                                        data-package="<?= (int)$s['package_id'] ?>"
                                        data-distributor="<?= (int)$s['distributor_id'] ?>"
                                        data-bundles="<?= (int)$s['bundles_count'] ?>"
                                        data-items="<?= e(json_encode($itemsBySale[(int)$s['id']] ?? [], JSON_UNESCAPED_UNICODE)) ?>"
                                        data-type="<?= e($s['payment_type']) ?>"
                                        data-note="<?= e($s['note'] ?? '') ?>">✎ تعديل</button>
                                    <form method="post" action="/sales/delete" class="inline-form" onsubmit="return confirm('حذف هذه العملية؟')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                        <button class="kebab-item danger" type="submit">🗑 حذف</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($pages > 1): ?>
            <nav class="pagination" style="display:flex;gap:6px;justify-content:center;padding:14px 0;flex-wrap:wrap">
                <?php
                    $qs = $_GET;
                    for ($i = 1; $i <= $pages; $i++):
                        $qs['page'] = $i;
                        $url = '/sales?' . http_build_query($qs);
                ?>
                    <a class="btn sm <?= $i === $page ? 'primary' : '' ?>" href="<?= e($url) ?>"><?= int_num($i) ?></a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<script>
(function() {
    var form      = document.getElementById('sale-form');
    var collapse  = document.getElementById('sale-form-collapse');
    var summary   = document.getElementById('sale-form-summary');
    var idField   = document.getElementById('sale-id');
    var note      = document.getElementById('sale-note');
    var type      = document.getElementById('sale-type');
    var distributor = document.getElementById('sale-distributor');
    var display   = document.getElementById('sale-total-display');
    var submitBtn = document.getElementById('sale-submit-btn');
    var rows      = document.getElementById('sale-items-rows');
    var tpl       = document.getElementById('sale-item-tpl');
    var addBtn    = document.getElementById('sale-add-item');

    function calc() {
        var total = 0;
        rows.querySelectorAll('.sale-item-row').forEach(function(row) {
            var b = parseInt(row.querySelector('.item-bundles').value) || 0;
            var p = parseInt(row.querySelector('.item-price').value) || 0;
            total += b * p;
        });
        display.textContent = total.toLocaleString();
    }

    function bindRow(row) {
        var pkg = row.querySelector('.item-package');
        pkg.addEventListener('change', function() {
            var opt = pkg.options[pkg.selectedIndex];
            row.querySelector('.item-price').value = opt.getAttribute('data-bundle-price') || '';
            calc();
        });
        row.querySelector('.item-bundles').addEventListener('input', calc);
        row.querySelector('.sale-remove-item').addEventListener('click', function() {
            row.remove();
            if (rows.querySelectorAll('.sale-item-row').length === 0) addRow();
            calc();
        });
    }

    function addRow(pre) {
        pre = pre || {};
        var row = tpl.content.firstElementChild.cloneNode(true);
        if (pre.package_id) row.querySelector('.item-package').value = String(pre.package_id);
        if (pre.bundles_count) row.querySelector('.item-bundles').value = pre.bundles_count;
        if (pre.package_id) {
            var opt = row.querySelector('.item-package').selectedOptions[0];
            row.querySelector('.item-price').value = (opt && opt.getAttribute('data-bundle-price')) || '';
        }
        bindRow(row);
        rows.appendChild(row);
        return row;
    }

    addBtn.addEventListener('click', function() { addRow(); });
    type.addEventListener('change', function() {
        distributor.required = type.value === 'credit';
    });
    distributor.required = type.value === 'credit';
    addRow();

    function resetForm() {
        idField.value = '';
        form.action = '/sales/store';
        submitBtn.textContent = 'تسجيل البيع';
        summary.textContent = '+ عملية بيع جديدة';
        rows.innerHTML = '';
        addRow();
        calc();
    }

    // Edit button handler
    document.querySelectorAll('.sale-edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            idField.value       = btn.dataset.id;
            distributor.value   = btn.dataset.distributor || '';
            distributor.required = btn.dataset.type === 'credit';
            type.value          = btn.dataset.type;
            note.value          = btn.dataset.note;

            rows.innerHTML = '';
            var items;
            try { items = JSON.parse(btn.dataset.items || '[]'); } catch (e) { items = []; }
            if (items.length > 0) {
                items.forEach(function(it) { addRow(it); });
            } else {
                addRow({ package_id: btn.dataset.package, bundles_count: btn.dataset.bundles });
            }

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
