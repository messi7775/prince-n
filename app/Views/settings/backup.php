<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>النسخ الاحتياطي</h2>
            <p>إنشاء نسخة احتياطية من قاعدة البيانات</p>
        </div>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>نسخ احتياطي</h3><span>◫</span></div>
        <p>قم بإنشاء نسخة SQL كاملة من قاعدة البيانات وتنزيلها.</p>
        <form method="post" action="/backup/create">
            <?= csrf_field() ?>
            <button class="btn primary" type="submit">إنشاء وتنزيل نسخة احتياطية</button>
        </form>
    </section>
</div>
