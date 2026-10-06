<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("This tool must be run from the command line.\n");
}

$password = $argv[1] ?? null;

if ($password === null || $password === '') {
    exit("Usage: php tools/hash_password.php \"YourPassword\"\n");
}

echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
