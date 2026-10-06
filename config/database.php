<?php
declare(strict_types=1);

/*
 * External hosting configuration
 * Domain: prince-n.kesug.com
 * MySQL: InfinityFree
 *
 * IMPORTANT:
 * The password supplied in the request was masked as XXXXXXXXXXX.
 * Replace DB_PASS with the real MySQL password before uploading.
 */

// Environment-driven: falls back to the external hosting credentials
// when no local DB_* environment variables are provided (e.g. on the
// production host). In the Base44 dev environment these are set by
// docker-compose to point at the local MariaDB service.
define('DB_HOST', getenv('DB_HOST') ?: 'sql302.infinityfree.com');
define('DB_NAME', getenv('DB_NAME') ?: 'if0_43097781_prince');
define('DB_USER', getenv('DB_USER') ?: 'if0_43097781');
define('DB_PASS', getenv('DB_PASS') ?: '7CF3Sf3xDxbU5L');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));

$dsn = 'mysql:host=' . DB_HOST .
       ';port=' . DB_PORT .
       ';dbname=' . DB_NAME .
       ';charset=utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 8,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('تعذر الاتصال بقاعدة البيانات الخارجية.');
}
