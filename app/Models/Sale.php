<?php
declare(strict_types=1);

namespace Models;

use Model;

final class Sale extends Model
{
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT s.*, d.name AS distributor_name, p.name AS package_name
               FROM sales s
          LEFT JOIN distributors d ON d.id = s.distributor_id
          LEFT JOIN packages p ON p.id = s.package_id
              ORDER BY s.created_at DESC'
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM sales WHERE id = ?', [$id]);
    }

    public function create(array $data): int
    {
        return $this->insert('sales', $data);
    }

    public function update(int $id, array $data): int
    {
        return $this->updateRow('sales', $id, $data);
    }

    public function delete(int $id): int
    {
        return $this->deleteRow('sales', $id);
    }

    public function totalToday(): int
    {
        return $this->fetchInt(
            "SELECT COALESCE(SUM(total), 0) FROM sales WHERE DATE(created_at) = CURDATE() AND payment_type = 'cash'"
        );
    }

    public function totalThisMonth(): int
    {
        return $this->fetchInt(
            'SELECT COALESCE(SUM(total), 0) FROM sales WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())'
        );
    }

    public function totalCredit(): int
    {
        return $this->fetchInt("SELECT COALESCE(SUM(total), 0) FROM sales WHERE payment_type = 'credit'");
    }

    public function totalCash(): int
    {
        return $this->fetchInt("SELECT COALESCE(SUM(total), 0) FROM sales WHERE payment_type = 'cash'");
    }

    public function recent(int $limit = 10): array
    {
        return $this->fetchAll(
            'SELECT s.*, d.name AS distributor_name, p.name AS package_name
               FROM sales s
          LEFT JOIN distributors d ON d.id = s.distributor_id
          LEFT JOIN packages p ON p.id = s.package_id
              ORDER BY s.created_at DESC
              LIMIT ' . (int)$limit
        );
    }

    public function totalBundles(): int
    {
        return $this->fetchInt('SELECT COALESCE(SUM(bundles_count), 0) FROM sales');
    }
}
