<?php
declare(strict_types=1);

/**
 * Database — PDO singleton wrapper.
 *
 * Reads connection settings from config/database.php (which defines the
 * DB_HOST / DB_NAME / DB_USER / DB_PASS / DB_PORT constants, environment
 * driven with production fallbacks) and exposes a shared PDO instance.
 */

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = 'mysql:host=' . DB_HOST
             . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME
             . ';charset=utf8mb4';

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 8,
        ];

        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('تعذر الاتصال بقاعدة البيانات.');
        }

        return self::$pdo;
    }
}
