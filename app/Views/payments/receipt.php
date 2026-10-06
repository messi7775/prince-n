<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title><?= e($pageTitle) ?> | شبكة البرنس</title>
<link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(dirname(__DIR__, 3) . "/assets/css/app.css") ?>">
<style>
    body { background: #fff; }
    .receipt-wrap { max-width: 640px; margin: 24px auto; }
    .receipt-actions { text-align: center; margin-bottom: 16px; }
    .receipt-box {
        background: #fff; border: 1px solid #d7dde8; border-radius: 12px;
        padding: 24px 28px; font-size: 15px;
    }
    .receipt-box h2 { margin: 0 0 4px; text-align: center; }
    .receipt-sub { text-align: center; color: #667; font-size: 13px; margin-bottom: 16px; }
    .receipt-meta { display: flex; justify-content: space-between; font-size: 13px; color: #334; margin-bottom: 12px; }
    .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .receipt-table th, .receipt-table td {
        border: 1px solid #e2e7ef; padding: 7px 10px; text-align: right; font-size: 13px;
    }
    .receipt-table th { background: #f4f6fa; width: 130px; font-weight: 600; }
    .receipt-total {
        display: flex; justify-content: space-between; align-items: center;
        border-top: 2px solid #2f6fde; padding-top: 10px; font-weight: 700; font-size: 16px;
    }
    .receipt-footer { margin-top: 14px; font-size: 12px; color: #889; text-align: center; }
    @media print {
        .receipt-actions { display: none !important; }
        .receipt-wrap { margin: 0; max-width: none; }
        .receipt-box { border: none; padding: 0; }
    }
</style>
</head>
<body>
<div class="receipt-wrap">
    <div class="receipt-actions">
        <button class="btn primary" type="button" onclick="window.print()">🖨 طباعة الوصل</button>
        <a class="btn" href="/payments">رجوع للتحصيلات</a>
    </div>

    <div class="receipt-box">
        <h2>وصل تحصيل</h2>
        <div class="receipt-sub">شبكة البرنس لتوزيع كروت الإنترنت</div>

        <div class="receipt-meta">
            <span>رقم الوصل: <strong>#<?= int_num($payment['id']) ?></strong></span>
            <span>التاريخ: <?= ar_date($payment['created_at']) ?></span>
        </div>

        <table class="receipt-table">
            <tr><th>الموزع</th><td><?= e($payment['distributor_name'] ?? '') ?></td></tr>
            <?php if (!empty($payment['note'])): ?>
            <tr><th>ملاحظات</th><td><?= e($payment['note']) ?></td></tr>
            <?php endif; ?>
        </table>

        <div class="receipt-total">
            <span>المبلغ المحصّل</span>
            <span><?= money($payment['amount']) ?></span>
        </div>

        <div class="receipt-footer">شكراً لتعاملكم معنا — وصل آلي صادر من نظام شبكة البرنس</div>
    </div>
</div>
</body>
</html>
