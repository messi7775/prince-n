<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>النسخ الاحتياطي</h2>
            <p>إنشاء نسخة احتياطية أو استعادة نسخة سابقة</p>
        </div>
    </section>

    <?php if (!empty($error)): ?>
    <section class="dashboard-panel">
        <div class="alert error"><?= e($error) ?></div>
    </section>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
    <section class="dashboard-panel">
        <div class="alert success">✔ <?= e($success) ?></div>
    </section>
    <?php endif; ?>

    <section class="dashboard-panel">
        <div class="section-title"><h3>إنشاء نسخة احتياطية</h3><span>◫</span></div>
        <p>قم بإنشاء نسخة SQL كاملة من قاعدة البيانات وتنزيلها.</p>
        <form method="post" action="/backup/create">
            <?= csrf_field() ?>
            <button class="btn primary" type="submit">إنشاء وتنزيل نسخة احتياطية</button>
        </form>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>استعادة نسخة احتياطية</h3><span>↺</span></div>
        <div class="alert error">
            تحذير: الاستعادة تستبدل <strong>جميع</strong> البيانات الحالية بمحتوى الملف المرفوع. لا يمكن التراجع.
        </div>
        <p>اختر ملف نسخة احتياطية بصيغة .sql (من النسخ التي تم تنزيلها من هذه الصفحة) لاستعادة قاعدة البيانات بالكامل.</p>
        <form method="post" action="/backup/restore" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="file" name="backup_file" accept=".sql" required>
            <button class="btn danger" type="submit">استعادة النسخة الاحتياطية</button>
        </form>
    </section>
</div>
