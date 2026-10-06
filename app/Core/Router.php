<?php
declare(strict_types=1);

final class Router
{
    private array $routes = [];

    public function get(string $path, string $controller, string $action): void
    {
        $this->routes['GET'][$path] = ['controller' => $controller, 'action' => $action];
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->routes['POST'][$path] = ['controller' => $controller, 'action' => $action];
    }

    public function add(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[strtoupper($method)][$path] = ['controller' => $controller, 'action' => $action];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method;
        $path   = $request->path;

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        if (!isset($this->routes[$method][$path])) {
            $this->notFound();
        }

        $route  = $this->routes[$method][$path];
        $class  = $route['controller'];
        $action = $route['action'];

        if (!class_exists($class) || !method_exists($class, $action)) {
            http_response_code(500);
            exit('المسار غير مهيأ بشكل صحيح.');
        }

        $controller = new $class();
        $controller->{$action}($request);
    }

    private function notFound(): void
    {
        http_response_code(404);
        exit('الصفحة المطلوبة غير موجودة.');
    }
}
