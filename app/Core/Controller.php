<?php
declare(strict_types=1);

abstract class Controller
{
    protected function view(string $view, array $viewData = [], ?string $layout = 'app'): void
    {
        extract($viewData, EXTR_SKIP);

        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            exit('العرض المطلوب غير موجود: ' . htmlspecialchars($view));
        }

        if ($layout === null) {
            require $viewFile;
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = __DIR__ . '/../Views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            http_response_code(500);
            exit('القالب المطلوب غير موجود: ' . htmlspecialchars($layout));
        }
        require $layoutFile;
    }

    protected function redirect(string $path): void
    {
        Response::redirect($path);
    }

    protected function notFound(): void
    {
        http_response_code(404);
        exit('الصفحة المطلوبة غير موجودة.');
    }

    protected function requireAuth(): void
    {
        if (!Session::isAuthenticated()) {
            $this->redirect('/login');
        }
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!is_string($token) || !hash_equals(Session::get('_csrf', ''), $token)) {
            http_response_code(419);
            exit('انتهت صلاحية الجلسة، أعد المحاولة.');
        }
    }

    protected function logAudit(string $action, string $description = '', array $context = []): void
    {
        (new \Models\AuditLog())->log($action, $description, $context);
    }
}
