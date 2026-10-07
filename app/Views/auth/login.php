<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d1528">
    <title>تسجيل الدخول | شبكة البرنس</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(dirname(__DIR__, 3) . "/assets/css/app.css") ?>">
</head>
<body class="login-page">
    <div class="login-glow glow-a" aria-hidden="true"></div>
    <div class="login-glow glow-b" aria-hidden="true"></div>

    <div class="login-card">
        <aside class="login-side">
            <div class="login-logo">PN</div>
            <h2>شبكة البرنس</h2>
            <p>نظام إدارة مبيعات كروت الإنترنت<br>المخزون · الموزعون · التحصيلات · التقارير</p>
        </aside>

        <div class="login-main">
            <div class="login-heading">
                <h1>تسجيل الدخول</h1>
                <p>أدخل بيانات حساب المدير للمتابعة.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert error">⚠️ <?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="on">
                <?= csrf_field() ?>
                <label for="email">البريد الإلكتروني</label>
                <div class="login-input">
                    <span aria-hidden="true">✉</span>
                    <input id="email" name="email" type="email" autocomplete="username" required>
                </div>

                <label for="password">كلمة المرور</label>
                <div class="login-input">
                    <span aria-hidden="true">🔒</span>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>

                <button class="btn primary full login-submit" type="submit">دخول</button>
            </form>
        </div>
    </div>
</body>
</html>
