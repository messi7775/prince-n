<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>سجل التدقيق</h2>
            <p>سجل العمليات المهمة في النظام</p>
        </div>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>السجلات</h3><span>◴</span></div>
        <?php if (empty($entries)): ?>
            <div class="empty-state">لا توجد سجلات حتى الآن</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>التاريخ</th><th>المستخدم</th><th>العملية</th><th>الوصف</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $row): ?>
                    <tr>
                        <td><?= ar_date($row['created_at']) ?></td>
                        <td><?= e($row['admin_email'] ?? '') ?></td>
                        <td><?= e($row['action']) ?></td>
                        <td><?= e($row['description'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
