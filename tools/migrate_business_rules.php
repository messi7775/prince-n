<?php
declare(strict_types=1);

/**
 * One-time migration for existing installations created from older schema.sql.
 * Run from the project root with: php tools/migrate_business_rules.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/config/database.php';

/** @var PDO $pdo */
$pdo->beginTransaction();

try {
    // Old data using the obsolete third payment type becomes normal credit.
    $pdo->exec("UPDATE sales SET payment_type = 'credit' WHERE payment_type = 'installment'");

    $columns = $pdo->query("SHOW COLUMNS FROM sales")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('paid_amount', $columns, true)) {
        $pdo->exec("ALTER TABLE sales DROP COLUMN paid_amount");
    }

    $pdo->exec("ALTER TABLE sales MODIFY payment_type ENUM('cash','credit') NOT NULL DEFAULT 'cash'");

    $pdo->commit();
    echo "Migration completed successfully.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Migration failed: {$e->getMessage()}\n");
    exit(1);
}
