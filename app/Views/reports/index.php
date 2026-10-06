<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>التقارير</h2>
            <p>تقارير النظام المالية والتشغيلية</p>
        </div>
    </section>

    <section class="dashboard-panel">
        <div class="report-tabs">
            <a href="/reports?type=sales" class="<?= ($type ?? 'sales') === 'sales' ? 'active' : '' ?>">المبيعات</a>
            <a href="/reports?type=payments" class="<?= ($type ?? '') === 'payments' ? 'active' : '' ?>">التحصيلات</a>
            <a href="/reports?type=expenses" class="<?= ($type ?? '') === 'expenses' ? 'active' : '' ?>">المصروفات</a>
            <a href="/reports?type=cash" class="<?= ($type ?? '') === 'cash' ? 'active' : '' ?>">الحركة النقدية</a>
            <a href="/reports?type=inventory" class="<?= ($type ?? '') === 'inventory' ? 'active' : '' ?>">المخزون</a>
        </div>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3><?= e($title) ?></h3><span>▤</span></div>
        <?php if (empty($data)): ?>
            <div class="empty-state">لا توجد بيانات</div>
        <?php elseif (($type ?? 'sales') === 'sales'): ?>
            <table class="data-table">
                <thead><tr><th>التاريخ</th><th>الموزع</th><th>الباقة</th><th>عدد الشدات</th><th>سعر الشدة</th><th>الإجمالي</th><th>النوع</th></tr></thead>
                <tbody>
                    <?php foreach ($data as $r): ?>
                    <tr>
                        <td><?= ar_date($r['created_at']) ?></td>
                        <td><?= e($r['distributor_name'] ?? 'نقدي') ?></td>
                        <td><?= e($r['package_name'] ?? '') ?></td>
                        <td><?= int_num($r['bundles_count']) ?></td>
                        <td><?= money($r['bundle_price']) ?></td>
                        <td><?= money($r['total']) ?></td>
                        <td><?= $r['payment_type'] === 'cash' ? 'نقدي' : 'آجل' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type === 'payments'): ?>
            <table class="data-table">
                <thead><tr><th>التاريخ</th><th>الموزع</th><th>المبلغ</th><th>ملاحظة</th></tr></thead>
                <tbody>
                    <?php foreach ($data as $r): ?>
                    <tr><td><?= ar_date($r['created_at']) ?></td><td><?= e($r['distributor_name'] ?? '') ?></td><td><?= money($r['amount']) ?></td><td><?= e($r['note'] ?? '') ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type === 'expenses'): ?>
            <table class="data-table">
                <thead><tr><th>التاريخ</th><th>التصنيف</th><th>المبلغ</th><th>ملاحظة</th></tr></thead>
                <tbody>
                    <?php foreach ($data as $r): ?>
                    <tr><td><?= ar_date($r['created_at']) ?></td><td><?= e($r['category']) ?></td><td><?= money($r['amount']) ?></td><td><?= e($r['note'] ?? '') ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type === 'cash'): ?>
            <table class="data-table">
                <thead><tr><th>التاريخ</th><th>الاتجاه</th><th>المبلغ</th><th>السبب</th><th>المرجع</th></tr></thead>
                <tbody>
                    <?php foreach ($data as $r): ?>
                    <tr><td><?= ar_date($r['created_at']) ?></td><td><?= $r['direction'] === 'in' ? 'وارد' : 'صادر' ?></td><td><?= money($r['amount']) ?></td><td><?= e($r['reason']) ?></td><td><?= e($r['reference_type'] ?? '') ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type === 'inventory'): ?>
            <table class="data-table">
                <thead><tr><th>الباقة</th><th>الشدات</th><th>سعر الشدة</th><th>القيمة</th></tr></thead>
                <tbody>
                    <?php foreach ($data as $r): ?>
                    <tr><td><?= e($r['name']) ?></td><td><?= int_num($r['bundles']) ?></td><td><?= money($r['bundle_price']) ?></td><td><?= money($r['value']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
