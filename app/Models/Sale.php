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

    /**
     * Sales list with search, filters, sorting and pagination.
     * $filters: q, date_from, date_to, distributor_id, package_id, payment_type,
     *           sort (date|total), dir (asc|desc), page, per_page
     */
    public function search(array $filters = []): array
    {
        $where  = [];
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(d.name LIKE :q OR p.name LIKE :q OR s.note LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = 's.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = 's.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $distributorId = (int)($filters['distributor_id'] ?? 0);
        if ($distributorId > 0) {
            $where[] = 's.distributor_id = :distributor_id';
            $params['distributor_id'] = $distributorId;
        }

        $packageId = (int)($filters['package_id'] ?? 0);
        if ($packageId > 0) {
            $where[] = 's.package_id = :package_id';
            $params['package_id'] = $packageId;
        }

        $type = (string)($filters['payment_type'] ?? '');
        if (in_array($type, ['cash', 'credit'], true)) {
            $where[] = 's.payment_type = :payment_type';
            $params['payment_type'] = $type;
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $sortMap  = ['date' => 's.created_at', 'total' => 's.total'];
        $sort     = $sortMap[$filters['sort'] ?? 'date'] ?? 's.created_at';
        $dir      = strtolower((string)($filters['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $perPage  = max(1, (int)($filters['per_page'] ?? 20));
        $page     = max(1, (int)($filters['page'] ?? 1));

        $total = (int)$this->fetchScalar(
            "SELECT COUNT(*)
               FROM sales s
          LEFT JOIN distributors d ON d.id = s.distributor_id
          LEFT JOIN packages p ON p.id = s.package_id
              $whereSql",
            $params
        );

        $pages = max(1, (int)ceil($total / $perPage));
        $page  = min($page, $pages);

        $offset = ($page - 1) * $perPage;
        $rows = $this->fetchAll(
            "SELECT s.*, d.name AS distributor_name, p.name AS package_name
               FROM sales s
          LEFT JOIN distributors d ON d.id = s.distributor_id
          LEFT JOIN packages p ON p.id = s.package_id
              $whereSql
              ORDER BY $sort $dir, s.id $dir
              LIMIT $perPage OFFSET $offset",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }
}
