<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>التقارير</h2>
            <p>تقارير مبنية على العمليات الفعلية المسجلة في النظام</p>
        </div>
    </section>

    <section class="dashboard-panel report-tabs-panel">
        <div class="report-tabs">
            <?php foreach ($types as $t): ?>
                <a class="report-tab <?= $type === $t ? 'active' : '' ?>"
                   href="/reports?type=<?= e($t) ?><?= $t !== 'inventory' && $t !== 'distributors' ? '&period=day' : '' ?>"><?= e($titles[$t]) ?></a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>الفلاتر</h3><span>⌕</span></div>
        <form method="get" action="/reports" class="entity-form">
            <input type="hidden" name="type" value="<?= e($type) ?>">
            <div class="form-grid">
                <?php if ($type === 'sales' || $type === 'payments' || $type === 'expenses' || $type === 'cash' || $type === 'profit'): ?>
                    <label>الفترة
                        <select name="period">
                            <option value="day" <?= $filters['period'] === 'day' ? 'selected' : '' ?>>اليوم</option>
                            <option value="week" <?= $filters['period'] === 'week' ? 'selected' : '' ?>>آخر 7 أيام</option>
                            <option value="month" <?= $filters['period'] === 'month' ? 'selected' : '' ?>>هذا الشهر</option>
                            <option value="custom" <?= $filters['period'] === 'custom' ? 'selected' : '' ?>>فترة مخصصة</option>
                        </select>
                    </label>
                    <label>من تاريخ<input type="date" name="date_from" value="<?= e($filters['date_from']) ?>"></label>
                    <label>إلى تاريخ<input type="date" name="date_to" value="<?= e($filters['date_to']) ?>"></label>
                <?php endif; ?>
                <?php if ($type === 'sales' || $type === 'payments'): ?>
                    <label>الموزع
                        <select name="distributor_id">
                            <option value="">الكل</option>
                            <?php foreach ($distributors as $d): ?>
                                <option value="<?= (int)$d['id'] ?>" <?= $filters['distributor_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <?php if ($type === 'sales'): ?>
                    <label>الباقة
                        <select name="package_id">
                            <option value="">الكل</option>
                            <?php foreach ($packages as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= $filters['package_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>نوع البيع
                        <select name="payment_type">
                            <option value="">الكل</option>
                            <option value="cash" <?= $filters['payment_type'] === 'cash' ? 'selected' : '' ?>>نقدي</option>
                            <option value="credit" <?= $filters['payment_type'] === 'credit' ? 'selected' : '' ?>>آجل</option>
                        </select>
                    </label>
                <?php endif; ?>
                <?php if ($type === 'expenses'): ?>
                    <label>التصنيف
                        <select name="category">
                            <option value="">الكل</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= e($c['category']) ?>" <?= $filters['category'] === $c['category'] ? 'selected' : '' ?>><?= e($c['category']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <?php if ($type === 'cash'): ?>
                    <label>نوع الحركة
                        <select name="direction">
                            <option value="">الكل</option>
                            <option value="in" <?= $filters['direction'] === 'in' ? 'selected' : '' ?>>دخول</option>
                            <option value="out" <?= $filters['direction'] === 'out' ? 'selected' : '' ?>>خروج</option>
                        </select>
                    </label>
                <?php endif; ?>
                <?php if ($type === 'sales' || $type === 'payments' || $type === 'expenses' || $type === 'cash'): ?>
                    <label>بحث<input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="نص حر"></label>
                <?php endif; ?>
            </div>
            <button class="btn primary" type="submit">تطبيق الفلاتر</button>
            <a class="btn" href="/reports?type=<?= e($type) ?>">مسح الفلاتر</a>
        </form>
    </section>

    <?php $q = e(http_build_query(array_filter([
        'type' => $type, 'period' => $filters['period'],
        'date_from' => $filters['date_from'], 'date_to' => $filters['date_to'],
        'distributor_id' => $filters['distributor_id'], 'package_id' => $filters['package_id'],
        'payment_type' => $filters['payment_type'], 'category' => $filters['category'],
        'direction' => $filters['direction'], 'q' => $filters['q'],
    ], static fn ($v) => $v !== '' && $v !== 0))); ?>

    <section class="dashboard-panel report-print-panel">
        <div class="section-title"><h3><?= e($title) ?></h3><span>▤</span></div>
        <button class="btn primary" type="button" onclick="window.print()">🖨 طباعة التقرير</button>
    </section>

    <?php if ($type === 'sales'): ?>
        <section class="kpi-grid cols-4">
            <article class="kpi-card blue"><div class="kpi-icon">▦</div><div class="kpi-content"><span>عدد العمليات</span><strong><?= int_num($data['totals']['count']) ?></strong></div></article>
            <article class="kpi-card violet"><div class="kpi-icon">♧</div><div class="kpi-content"><span>الشدات المباعة</span><strong><?= int_num($data['totals']['bundles']) ?></strong></div></article>
            <article class="kpi-card green"><div class="kpi-icon">↗</div><div class="kpi-content"><span>نقدي</span><strong><?= money($data['totals']['cash_total']) ?></strong></div></article>
            <article class="kpi-card purple"><div class="kpi-icon">♣</div><div class="kpi-content"><span>آجل</span><strong><?= money($data['totals']['credit_total']) ?></strong></div></article>
        </section>
        <section class="dashboard-panel">
            <div class="section-title"><h3>الإجمالي: <?= money($data['totals']['total']) ?></h3><span>▤</span></div>
            <?php if (empty($data['rows'])): ?>
                <div class="empty-state">لا توجد مبيعات مطابقة</div>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>التاريخ</th><th>الموزع</th><th>الباقات</th><th>الشدات</th><th>سعر(ش)</th><th>الإجمالي</th><th>النوع</th><th>ملاحظة</th></tr></thead>
                    <tbody>
                        <?php foreach ($data['rows'] as $s): ?>
                        <tr>
                            <td><?= ar_date($s['created_at']) ?></td>
                            <td><?= e($s['distributor_name'] ?? 'نقدي') ?></td>
                            <td><?= e($s['package_name'] ?? '') ?></td>
                            <td><?= int_num($s['bundles_count']) ?></td>
                            <td><?= (int)($s['items_count'] ?? 1) === 1 ? money($s['item_price'] ?? $s['bundle_price']) : 'متنوعة' ?></td>
                            <td><?= money($s['total']) ?></td>
                            <td><?= $s['payment_type'] === 'cash' ? 'نقدي' : 'آجل' ?></td>
                            <td><?= e($s['note'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    <?php elseif ($type === 'payments'): ?>
        <section class="kpi-grid cols-2">
            <article class="kpi-card green"><div class="kpi-icon">♣</div><div class="kpi-content"><span>إجمالي التحصيلات</span><strong><?= money($data['total']) ?></strong></div></article>
            <article class="kpi-card blue"><div class="kpi-icon">▦</div><div class="kpi-content"><span>عدد العمليات</span><strong><?= int_num($data['count']) ?></strong></div></article>
        </section>
        <section class="dashboard-panel">
            <?php if (empty($data['rows'])): ?>
                <div class="empty-state">لا توجد تحصيلات مطابقة</div>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>التاريخ</th><th>الموزع</th><th>المبلغ</th><th>ملاحظة</th></tr></thead>
                    <tbody>
                        <?php foreach ($data['rows'] as $p): ?>
                        <tr>
                            <td><?= ar_date($p['created_at']) ?></td>
                            <td><?= e($p['distributor_name'] ?? '') ?></td>
                            <td><?= money($p['amount']) ?></td>
                            <td><?= e($p['note'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    <?php elseif ($type === 'expenses'): ?>
        <section class="kpi-grid cols-2">
            <article class="kpi-card pink"><div class="kpi-icon">▣</div><div class="kpi-content"><span>إجمالي المصروفات</span><strong><?= money($data['total']) ?></strong></div></article>
            <article class="kpi-card blue"><div class="kpi-icon">▦</div><div class="kpi-content"><span>عدد العمليات</span><strong><?= int_num($data['count']) ?></strong></div></article>
        </section>
        <?php if (!empty($data['byCategory'])): ?>
        <section class="dashboard-panel">
            <div class="section-title"><h3>حسب التصنيف</h3><span>▣</span></div>
            <table class="data-table">
                <thead><tr><th>التصنيف</th><th>الإجمالي</th><th>النسبة</th></tr></thead>
                <tbody>
                    <?php foreach ($data['byCategory'] as $cat => $amount): ?>
                    <tr>
                        <td><?= e($cat) ?></td>
                        <td><?= money($amount) ?></td>
                        <td><?= $data['total'] > 0 ? int_num(round($amount * 100 / $data['total'])) : 0 ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php endif; ?>
        <section class="dashboard-panel">
            <?php if (empty($data['rows'])): ?>
                <div class="empty-state">لا توجد مصروفات مطابقة</div>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>التاريخ</th><th>التصنيف</th><th>المبلغ</th><th>ملاحظة</th></tr></thead>
                    <tbody>
                        <?php foreach ($data['rows'] as $ex): ?>
                        <tr>
                            <td><?= ar_date($ex['created_at']) ?></td>
                            <td><?= e($ex['category']) ?></td>
                            <td><?= money($ex['amount']) ?></td>
                            <td><?= e($ex['note'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    <?php elseif ($type === 'cash'): ?>
        <section class="kpi-grid cols-3">
            <article class="kpi-card green"><div class="kpi-icon">↗</div><div class="kpi-content"><span>إجمالي الدخول</span><strong><?= money($data['total_in']) ?></strong></div></article>
            <article class="kpi-card pink"><div class="kpi-icon">↓</div><div class="kpi-content"><span>إجمالي الخروج</span><strong><?= money($data['total_out']) ?></strong></div></article>
            <article class="kpi-card teal"><div class="kpi-icon">▥</div><div class="kpi-content"><span>الصافي</span><strong><?= money($data['balance']) ?></strong></div></article>
        </section>
        <section class="dashboard-panel">
            <?php if (empty($data['rows'])): ?>
                <div class="empty-state">لا توجد حركات نقدية مطابقة</div>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>التاريخ</th><th>النوع</th><th>البيان</th><th>المبلغ</th><th>الرصيد بعد الحركة</th></tr></thead>
                    <tbody>
                        <?php foreach ($data['rows'] as $m): ?>
                        <tr>
                            <td><?= ar_date($m['created_at']) ?></td>
                            <td><?= $m['direction'] === 'in' ? 'دخول' : 'خروج' ?></td>
                            <td><?= e($m['reason']) ?></td>
                            <td style="color:<?= $m['direction'] === 'in' ? '#18a078' : '#e6466a' ?>"><?= money($m['amount']) ?></td>
                            <td><strong><?= money($m['running']) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    <?php elseif ($type === 'inventory'): ?>
        <section class="kpi-grid cols-4">
            <article class="kpi-card blue"><div class="kpi-icon">♧</div><div class="kpi-content"><span>الداخل</span><strong><?= int_num($data['totals']['stock_in']) ?></strong></div></article>
            <article class="kpi-card violet"><div class="kpi-icon">↓</div><div class="kpi-content"><span>المباع</span><strong><?= int_num($data['totals']['sold']) ?></strong></div></article>
            <article class="kpi-card green"><div class="kpi-icon">▦</div><div class="kpi-content"><span>المتبقي</span><strong><?= int_num($data['totals']['bundles']) ?></strong></div></article>
            <article class="kpi-card amber"><div class="kpi-icon">▤</div><div class="kpi-content"><span>قيمة المخزون</span><strong><?= money($data['totals']['value']) ?></strong></div></article>
        </section>
        <section class="dashboard-panel">
            <table class="data-table">
                <thead><tr><th>الباقة</th><th>سعر الشدة</th><th>الداخل</th><th>المباع</th><th>المتبقي</th><th>قيمة المتبقي</th></tr></thead>
                <tbody>
                    <?php foreach ($data['rows'] as $r): ?>
                    <tr>
                        <td><?= e($r['name']) ?></td>
                        <td><?= money($r['bundle_price']) ?></td>
                        <td><?= int_num($r['stock_in']) ?></td>
                        <td><?= int_num($r['sold']) ?></td>
                        <td><span class="badge <?= (int)$r['bundles'] <= (int)$r['low_stock_threshold'] ? 'zero' : 'ok' ?>"><?= int_num($r['bundles']) ?></span></td>
                        <td><?= money($r['value']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

    <?php elseif ($type === 'distributors'): ?>
        <section class="kpi-grid cols-4">
            <article class="kpi-card blue"><div class="kpi-icon">▦</div><div class="kpi-content"><span>إجمالي المبيعات</span><strong><?= money($data['totals']['total_sales']) ?></strong></div></article>
            <article class="kpi-card purple"><div class="kpi-icon">♣</div><div class="kpi-content"><span>إجمالي التحصيلات</span><strong><?= money($data['totals']['paid_total']) ?></strong></div></article>
            <article class="kpi-card pink"><div class="kpi-icon">⚠</div><div class="kpi-content"><span>إجمالي الديون</span><strong><?= money($data['totals']['debt']) ?></strong></div></article>
            <article class="kpi-card violet"><div class="kpi-icon">♙</div><div class="kpi-content"><span>عدد الموزعين</span><strong><?= int_num(count($data['rows'])) ?></strong></div></article>
        </section>
        <section class="dashboard-panel">
            <?php if (empty($data['rows'])): ?>
                <div class="empty-state">لا يوجد موزعون</div>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>الموزع</th><th>الهاتف</th><th>عدد العمليات</th><th>إجمالي المبيعات</th><th>مبيعات آجل</th><th>التحصيلات</th><th>الدين المتبقي</th></tr></thead>
                    <tbody>
                        <?php foreach ($data['rows'] as $d): ?>
                        <tr>
                            <td><?= e($d['name']) ?></td>
                            <td><?= e($d['phone'] ?? '') ?></td>
                            <td><?= int_num($d['sales_count']) ?></td>
                            <td><?= money($d['total_sales']) ?></td>
                            <td><?= money($d['credit_sales']) ?></td>
                            <td><?= money($d['paid_total']) ?></td>
                            <td><span class="badge <?= (int)$d['debt'] > 0 ? 'zero' : 'ok' ?>"><?= money($d['debt']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    <?php elseif ($type === 'profit'): ?>
        <section class="kpi-grid cols-3">
            <article class="kpi-card green"><div class="kpi-icon">↗</div><div class="kpi-content"><span>الإيراد (المبيعات)</span><strong><?= money($data['revenue']) ?></strong></div></article>
            <article class="kpi-card pink"><div class="kpi-icon">▣</div><div class="kpi-content"><span>المصروفات</span><strong><?= money($data['expenses']) ?></strong></div></article>
            <article class="kpi-card amber"><div class="kpi-icon">▤</div><div class="kpi-content"><span>تكلفة الشراء المسجلة</span><strong><?= $data['available'] ? money($data['cost']) : 'غير مسجلة' ?></strong></div></article>
        </section>
        <section class="dashboard-panel">
            <?php if ($data['available']): ?>
                <div class="section-title"><h3>صافي الربح: <?= money($data['profit']) ?></h3><span>▤</span></div>
                <table class="data-table">
                    <thead><tr><th>البند</th><th>القيمة</th></tr></thead>
                    <tbody>
                        <tr><td>الإيراد</td><td><?= money($data['revenue']) ?></td></tr>
                        <tr><td>تكلفة الشراء الحقيقية</td><td>- <?= money($data['cost']) ?></td></tr>
                        <tr><td>المصروفات</td><td>- <?= money($data['expenses']) ?></td></tr>
                        <tr><td><strong>صافي الربح</strong></td><td><strong><?= money($data['profit']) ?></strong></td></tr>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    صافي الربح غير متاح — لا توجد تكلفة شراء حقيقية مسجلة للشدات المباعة
                    (<?= int_num($data['unpriced']) ?> شدة بدون تكلفة).
                    <br><small>عند تسجيل تكلفة الشراء الفعلية للدفعات، سيُحسب الربح تلقائيًا: الإيراد − التكلفة − المصروفات.</small>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
