<?php
declare(strict_types=1);

/**
 * Secure session wrapper for the single-admin application.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_trans_sid', '0');

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return array_key_exists($key, $_SESSION);
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function flashGet(string $key): mixed
    {
        self::start();
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function login(int $adminId, string $email): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $adminId;
        $_SESSION['admin_email'] = $email;
        $_SESSION['login_at'] = time();
        $_SESSION['_login_attempts'] = [];

        // Rotate the CSRF token after authentication to separate the
        // pre-login and authenticated session state.
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    public static function isAuthenticated(): bool
    {
        return self::has('admin_id');
    }

    public static function adminId(): ?int
    {
        $id = self::get('admin_id');
        return $id === null ? null : (int)$id;
    }

    public static function adminEmail(): ?string
    {
        $email = self::get('admin_email');
        return $email === null ? null : (string)$email;
    }

    public static function loginAllowed(int $maxAttempts = 5, int $windowSeconds = 600): bool
    {
        self::start();
        $now = time();
        $attempts = array_values(array_filter(
            (array)($_SESSION['_login_attempts'] ?? []),
            static fn ($timestamp): bool => is_int($timestamp) && ($now - $timestamp) < $windowSeconds
        ));
        $_SESSION['_login_attempts'] = $attempts;
        return count($attempts) < $maxAttempts;
    }

    public static function recordLoginFailure(): void
    {
        self::start();
        $_SESSION['_login_attempts'][] = time();
    }
}
