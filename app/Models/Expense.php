<?php
declare(strict_types=1);

namespace Models;

use Model;

final class Expense extends Model
{
    public function all(): array
    {
        return $this->fetchAll('SELECT * FROM expenses ORDER BY created_at DESC');
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM expenses WHERE id = ?', [$id]);
    }

    public function create(array $data): int
    {
        return $this->insert('expenses', $data);
    }

    public function update(int $id, array $data): int
    {
        return $this->updateRow('expenses', $id, $data);
    }

    public function delete(int $id): int
    {
        return $this->deleteRow('expenses', $id);
    }

    public function total(): int
    {
        return $this->fetchInt('SELECT COALESCE(SUM(amount), 0) FROM expenses');
    }

    public function totalToday(): int
    {
        return $this->fetchInt('SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE DATE(created_at) = CURDATE()');
    }
}
