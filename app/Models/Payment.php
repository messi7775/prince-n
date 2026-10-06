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

    /** One payment with distributor name (receipts). */
    public function findWithNames(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT p.*, d.name AS distributor_name
               FROM payments p
          LEFT JOIN distributors d ON d.id = p.distributor_id
              WHERE p.id = ?',
            [$id]
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

    /** Daily collections totals for the last N days. */
    public function dailyTotals(int $days = 7): array
    {
        return $this->fetchAll(
            "SELECT DATE(created_at) AS day,
                    COALESCE(SUM(amount), 0) AS total
               FROM payments
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)
              GROUP BY DATE(created_at)"
        );
    }

    /** Collections with search + filters (distributor, date range). */
    public function search(array $filters = []): array
    {
        $where  = [];
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(d.name LIKE :q_name OR p.note LIKE :q_note)';
            $params['q_name'] = '%' . $q . '%';
            $params['q_note'] = '%' . $q . '%';
        }

        $distributorId = (int)($filters['distributor_id'] ?? 0);
        if ($distributorId > 0) {
            $where[] = 'p.distributor_id = :distributor_id';
            $params['distributor_id'] = $distributorId;
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = 'p.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = 'p.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->fetchAll(
            "SELECT p.*, d.name AS distributor_name
               FROM payments p
          LEFT JOIN distributors d ON d.id = p.distributor_id
              $whereSql
              ORDER BY p.created_at DESC, p.id DESC",
            $params
        );
    }
}
