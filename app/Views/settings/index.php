<div class="dashboard">
    <section class="dashboard-title">
        <div>
            <h2>الإعدادات</h2>
            <p>إعدادات النظام وحساب المدير</p>
        </div>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>حساب المدير</h3><span>⚙</span></div>
        <div class="inventory-row">
            <span>البريد الإلكتروني</span>
            <em><?= e($adminEmail) ?></em>
        </div>
    </section>

    <section class="dashboard-panel">
        <div class="section-title"><h3>تغيير كلمة المرور</h3><span>⚙</span></div>
        <?php if (!empty($success)): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/settings/password" class="entity-form">
            <?= csrf_field() ?>
            <div class="form-grid">
                <label>كلمة المرور الحالية<input name="current_password" type="password" required></label>
                <label>كلمة المرور الجديدة<input name="new_password" type="password" required></label>
                <label>تأكيد كلمة المرور<input name="confirm_password" type="password" required></label>
            </div>
            <button class="btn primary" type="submit">تغيير كلمة المرور</button>
        </form>
    </section>
</div>
