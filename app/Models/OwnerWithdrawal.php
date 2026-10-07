<?php
declare(strict_types=1);

namespace Models;

use Model;

final class OwnerWithdrawal extends Model
{
    public function all(): array
    {
        return $this->fetchAll('SELECT * FROM owner_withdrawals ORDER BY created_at DESC');
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM owner_withdrawals WHERE id = ?', [$id]);
    }

    public function create(array $data): int
    {
        return $this->insert('owner_withdrawals', $data);
    }

    public function update(int $id, array $data): int
    {
        return $this->updateRow('owner_withdrawals', $id, $data);
    }

    public function delete(int $id): int
    {
        return $this->deleteRow('owner_withdrawals', $id);
    }

    public function total(): int
    {
        return $this->fetchInt('SELECT COALESCE(SUM(amount), 0) FROM owner_withdrawals');
    }
}
