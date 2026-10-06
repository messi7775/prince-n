<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d1528">
    <title>تسجيل الدخول | شبكة البرنس</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="brand login-brand">
            <div class="brand-mark">PN</div>
            <div>
                <strong>شبكة البرنس</strong>
                <span>Prince Network</span>
            </div>
        </div>

        <div class="login-heading">
            <h1>تسجيل الدخول</h1>
            <p>أدخل بيانات حساب المدير للمتابعة.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="on">
            <?= csrf_field() ?>
            <label for="email">البريد الإلكتروني</label>
            <input id="email" name="email" type="email" autocomplete="username" required>

            <label for="password">كلمة المرور</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <button class="btn primary full" type="submit">دخول</button>
        </form>
    </div>
</body>
</html>
