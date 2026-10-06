<?php
declare(strict_types=1);

namespace Models;

use Model;
use Session;

final class AuditLog extends Model
{
    public function all(int $limit = 100): array
    {
        return $this->fetchAll(
            'SELECT a.*, adm.email AS admin_email FROM audit_logs a LEFT JOIN admins adm ON adm.id = a.admin_id ORDER BY a.created_at DESC LIMIT ' . (int)$limit
        );
    }

    public function log(string $action, string $description = '', array $context = []): void
    {
        $this->insert('audit_logs', [
            'admin_id'    => Session::adminId(),
            'action'      => $action,
            'description' => $description,
            'context'     => json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
