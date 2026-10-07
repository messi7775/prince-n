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

    /** One sale with distributor/package names (receipts). */
    public function findWithNames(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT s.*, d.name AS distributor_name, p.name AS package_name
               FROM sales s
          LEFT JOIN distributors d ON d.id = s.distributor_id
          LEFT JOIN packages p ON p.id = s.package_id
              WHERE s.id = ?',
            [$id]
        );
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
     * Real purchase-cost basis for matching sales.
     * Returns the priced cost plus how many sold bundles have no recorded cost,
     * so profit can be declared exact only when unpriced_bundles = 0.
     */
    public function costBasis(array $filters = []): array
    {
        [$whereSql, $params] = $this->searchWhere($filters);

        return $this->fetchOne(
            "SELECT COALESCE(SUM(sa.bundles * i.purchase_cost), 0) AS cost,
                    COALESCE(SUM(CASE WHEN i.purchase_cost IS NULL THEN sa.bundles ELSE 0 END), 0) AS unpriced_bundles
               FROM sale_allocations sa
               JOIN sales s ON s.id = sa.sale_id
               JOIN inventory i ON i.id = sa.inventory_id
          LEFT JOIN distributors d ON d.id = s.distributor_id
          LEFT JOIN packages p ON p.id = s.package_id
              $whereSql",
            $params
        ) ?? ['cost' => 0, 'unpriced_bundles' => 0];
    }

    /** Daily sales totals for the last N days. */
    public function dailyTotals(int $days = 7): array
    {
        return $this->fetchAll(
            "SELECT DATE(created_at) AS day,
                    COALESCE(SUM(total), 0) AS total
               FROM sales
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)
              GROUP BY DATE(created_at)"
        );
    }

    /** Best-selling packages by revenue. */
    public function topPackages(int $limit = 5): array
    {
        return $this->fetchAll(
            "SELECT p.name,
                    COUNT(*) AS sales_count,
                    COALESCE(SUM(s.bundles_count), 0) AS bundles,
                    COALESCE(SUM(s.total), 0) AS total
               FROM sales s
          LEFT JOIN packages p ON p.id = s.package_id
              GROUP BY p.name
              ORDER BY total DESC
              LIMIT " . (int)$limit
        );
    }

    /** Shared WHERE builder for sales filters (used by search() and report()). */
    private function searchWhere(array $filters): array
    {
        $where  = [];
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(d.name LIKE :q_name OR p.name LIKE :q_pkg OR s.note LIKE :q_note)';
            $params['q_name'] = '%' . $q . '%';
            $params['q_pkg']  = '%' . $q . '%';
            $params['q_note'] = '%' . $q . '%';
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

        return [$whereSql, $params];
    }

    /**
     * Sales list with search, filters, sorting and pagination.
     * $filters: q, date_from, date_to, distributor_id, package_id, payment_type,
     *           sort (date|total), dir (asc|desc), page, per_page
     */
    public function search(array $filters = []): array
    {
        [$whereSql, $params] = $this->searchWhere($filters);

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

    /**
     * Sales report (no pagination): all matching rows + accurate SQL totals.
     * $filters: same as search() minus sort/dir/page.
     */
    public function report(array $filters = []): array
    {
        [$whereSql, $params] = $this->searchWhere($filters);

        $rows = $this->fetchAll(
            "SELECT s.*, d.name AS distributor_name, p.name AS package_name
               FROM sales s
          LEFT JOIN distributors d ON d.id = s.distributor_id
          LEFT JOIN packages p ON p.id = s.package_id
              $whereSql
              ORDER BY s.created_at DESC, s.id DESC",
            $params
        );

        $totals = $this->fetchOne(
            "SELECT COUNT(*) AS count,
                    COALESCE(SUM(s.bundles_count), 0) AS bundles,
                    COALESCE(SUM(s.total), 0) AS total,
                    COALESCE(SUM(CASE WHEN s.payment_type = 'cash' THEN s.total ELSE 0 END), 0) AS cash_total,
                    COALESCE(SUM(CASE WHEN s.payment_type = 'credit' THEN s.total ELSE 0 END), 0) AS credit_total
               FROM sales s
          LEFT JOIN distributors d ON d.id = s.distributor_id
          LEFT JOIN packages p ON p.id = s.package_id
              $whereSql",
            $params
        ) ?? ['count' => 0, 'bundles' => 0, 'total' => 0, 'cash_total' => 0, 'credit_total' => 0];

        return ['rows' => $rows, 'totals' => $totals];
    }
}
