<?php
declare(strict_types=1);

namespace Models;

use Model;

final class Line extends Model
{
    public function all(array $filters = []): array
    {
        $where  = [];
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(l.name LIKE :q OR l.provider LIKE :q OR l.note LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->fetchAll(
            "SELECT l.*,
                    COALESCE((SELECT -SUM(lp.amount) FROM line_payments lp WHERE lp.line_id = l.id), 0) AS balance
               FROM `lines` l
              $whereSql
              ORDER BY l.created_at DESC",
            $params
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `lines` WHERE id = ?', [$id]);
    }

    public function create(array $data): int
    {
        return $this->insert('`lines`', $data);
    }

    public function update(int $id, array $data): int
    {
        return $this->updateRow('`lines`', $id, $data);
    }

    public function delete(int $id): int
    {
        return $this->deleteRow('`lines`', $id);
    }

    public function count(): int
    {
        return $this->fetchInt('SELECT COUNT(*) FROM `lines`');
    }

    public function payments(int $lineId): array
    {
        return $this->fetchAll(
            'SELECT * FROM line_payments WHERE line_id = ? ORDER BY created_at DESC',
            [$lineId]
        );
    }

    public function allPayments(array $filters = []): array
    {
        $where  = [];
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(l.name LIKE :q OR lp.note LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $lineId = (int)($filters['line_id'] ?? 0);
        if ($lineId > 0) {
            $where[] = 'lp.line_id = :line_id';
            $params['line_id'] = $lineId;
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = 'lp.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = 'lp.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->fetchAll(
            "SELECT lp.*, l.name AS line_name
               FROM line_payments lp
               JOIN `lines` l ON l.id = lp.line_id
              $whereSql
              ORDER BY lp.created_at DESC, lp.id DESC",
            $params
        );
    }

    public function findPayment(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM line_payments WHERE id = ?', [$id]);
    }

    public function createPayment(array $data): int
    {
        return $this->insert('line_payments', $data);
    }

    public function updatePayment(int $id, array $data): int
    {
        return $this->updateRow('line_payments', $id, $data);
    }

    public function deletePayment(int $id): int
    {
        return $this->deleteRow('line_payments', $id);
    }

    public function totalPaymentsOut(): int
    {
        return $this->fetchInt("SELECT COALESCE(SUM(amount), 0) FROM line_payments");
    }

    public function totalPaymentsIn(): int
    {
        return 0;
    }
}
