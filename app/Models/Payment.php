<?php
declare(strict_types=1);

namespace Models;

use Model;

final class Payment extends Model
{
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT p.*, d.name AS distributor_name
               FROM payments p
          LEFT JOIN distributors d ON d.id = p.distributor_id
              ORDER BY p.created_at DESC'
        );
    }

    public function create(array $data): int
    {
        return $this->insert('payments', $data);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM payments WHERE id = ?', [$id]);
    }

    public function update(int $id, array $data): int
    {
        return $this->updateRow('payments', $id, $data);
    }

    public function delete(int $id): int
    {
        return $this->deleteRow('payments', $id);
    }

    public function totalToday(): int
    {
        return $this->fetchInt('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE(created_at) = CURDATE()');
    }

    public function total(): int
    {
        return $this->fetchInt('SELECT COALESCE(SUM(amount), 0) FROM payments');
    }
}
