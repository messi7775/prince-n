<?php
declare(strict_types=1);

namespace Models;

use Model;

final class Package extends Model
{
    public function all(): array
    {
        return $this->fetchAll('SELECT * FROM packages ORDER BY bundle_price DESC');
    }

    public function active(): array
    {
        return $this->fetchAll("SELECT * FROM packages WHERE status = 'active' ORDER BY bundle_price DESC");
    }

    public function count(): int
    {
        return $this->fetchInt('SELECT COUNT(*) FROM packages');
    }

    public function countActive(): int
    {
        return $this->fetchInt("SELECT COUNT(*) FROM packages WHERE status = 'active'");
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM packages WHERE id = ?', [$id]);
    }

    public function findByName(string $name): ?array
    {
        return $this->fetchOne('SELECT * FROM packages WHERE name = ? LIMIT 1', [$name]);
    }

    public function create(array $data): int
    {
        return $this->insert('packages', $data);
    }

    public function update(int $id, array $data): int
    {
        return $this->updateRow('packages', $id, $data);
    }

    public function delete(int $id): int
    {
        return $this->deleteRow('packages', $id);
    }

    public function inventoryValue(): int
    {
        return $this->fetchInt(
            'SELECT COALESCE(SUM(i.quantity * i.bundle_price), 0) FROM inventory i WHERE i.status = \'active\''
        );
    }
}
