<?php
declare(strict_types=1);

/**
 * Request — value object around the current HTTP request.
 */
final class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly string $path;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path   = parse_url($this->uri, PHP_URL_PATH) ?: '/';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $source = $this->isPost() ? $_POST : $_GET;
        $value = $source[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public function all(): array
    {
        $source = $this->isPost() ? $_POST : $_GET;
        return array_map(static fn ($v) => is_string($v) ? trim($v) : $v, $source);
    }

    public function has(string $key): bool
    {
        $source = $this->isPost() ? $_POST : $_GET;
        return array_key_exists($key, $source);
    }
}
