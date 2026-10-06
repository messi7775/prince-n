<?php
declare(strict_types=1);

/**
 * PSR-4-ish class autoloader.
 *
 * Maps the app namespaces to their directories:
 *   Core\Router      -> app/Core/Router.php
 *   Models\Admin     -> app/Models/Admin.php
 *   Controllers\...  -> app/Controllers/...
 *
 * Also loads the helper files once.
 */
spl_autoload_register(static function (string $class): void {
    // Strip a leading backslash.
    $class = ltrim($class, '\\');

    $map = [
        'Core\\'        => APP_ROOT . '/app/Core/',
        'Models\\'       => APP_ROOT . '/app/Models/',
        'Controllers\\' => APP_ROOT . '/app/Controllers/',
        'Services\\'     => APP_ROOT . '/app/Services/',
    ];

    // Models in subdirectories: Models\OwnerWithdrawal etc.

    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $dir . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }

    // Fallback: a class with no namespace matching a file in app/Core.
    $fallback = APP_ROOT . '/app/Core/' . $class . '.php';
    if (is_file($fallback)) {
        require $fallback;
    }
});

// Helper functions (no namespacing — available globally).
require_once APP_ROOT . '/app/Helpers/auth.php';
require_once APP_ROOT . '/app/Helpers/csrf.php';
require_once APP_ROOT . '/app/Helpers/format.php';
require_once APP_ROOT . '/app/Helpers/redirect.php';
require_once APP_ROOT . '/app/Helpers/validation.php';
