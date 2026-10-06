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

    /** Expenses list with search + filters (category, text, date range). */
    public function search(array $filters = []): array
    {
        $where  = [];
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(category LIKE :q_cat OR note LIKE :q_note)';
            $params['q_cat']  = '%' . $q . '%';
            $params['q_note'] = '%' . $q . '%';
        }

        $category = trim((string)($filters['category'] ?? ''));
        if ($category !== '') {
            $where[] = 'category = :category';
            $params['category'] = $category;
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = 'created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = 'created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->fetchAll(
            "SELECT * FROM expenses $whereSql ORDER BY created_at DESC, id DESC",
            $params
        );
    }

    public function categories(): array
    {
        return $this->fetchAll('SELECT DISTINCT category FROM expenses ORDER BY category');
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
