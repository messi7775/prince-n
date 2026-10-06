<?php
declare(strict_types=1);

/**
 * Response — builds and emits an HTTP response.
 */
final class Response
{
    public static function redirect(string $path, int $code = 302): void
    {
        http_response_code($code);
        header('Location: ' . $path);
        exit;
    }

    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
