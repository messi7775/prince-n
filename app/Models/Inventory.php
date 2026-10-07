<?php
declare(strict_types=1);

namespace Models;

use Model;

/**
 * Inventory — stock BATCHES.
 * A package can have several batches. Each batch tracks:
 *   quantity (received), sold (consumed by sales), remaining = quantity - sold.
 * Remaining/sold are always derived from the batch rows and sale_allocations,
 * never stored separately.
 */
final class Inventory extends Model
{
    /** All batches (newest first) with package info and derived remaining. */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT i.*, p.name AS package_name, (i.quantity - i.sold) AS remaining
               FROM inventory i
               JOIN packages p ON p.id = i.package_id
              ORDER BY i.created_at DESC, i.id DESC'
        );
    }

    /** Batches with search + filters (package, text, remaining state). */
    public function searchBatches(array $filters = []): array
    {
        $where  = [];
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(p.name LIKE :q_name OR i.note LIKE :q_note)';
            $params['q_name'] = '%' . $q . '%';
            $params['q_note'] = '%' . $q . '%';
        }

        $packageId = (int)($filters['package_id'] ?? 0);
        if ($packageId > 0) {
            $where[] = 'i.package_id = :package_id';
            $params['package_id'] = $packageId;
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->fetchAll(
            "SELECT i.*, p.name AS package_name, (i.quantity - i.sold) AS remaining
               FROM inventory i
               JOIN packages p ON p.id = i.package_id
              $whereSql
              ORDER BY i.created_at DESC, i.id DESC",
            $params
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM inventory WHERE id = ?', [$id]);
    }

    public function create(array $data): int
    {
        return $this->insert('inventory', $data);
    }

    public function update(int $id, array $data): int
    {
        return $this->updateRow('inventory', $id, $data);
    }

    public function delete(int $id): int
    {
        return $this->deleteRow('inventory', $id);
    }

    public function logMovement(array $data): int
    {
        return $this->insert('inventory_movements', $data);
    }

    public function movements(int $limit = 100): array
    {
        return $this->fetchAll(
            'SELECT m.*, p.name AS package_name
               FROM inventory_movements m
               JOIN packages p ON p.id = m.package_id
              ORDER BY m.id DESC
              LIMIT ' . (int)$limit
        );
    }

    /** Total bundles still in stock for one package (sum of batch remaining). */
    public function availableForPackage(int $packageId): int
    {
        return $this->fetchInt(
            'SELECT COALESCE(SUM(quantity - sold), 0) FROM inventory WHERE package_id = ?',
            [$packageId]
        );
    }

    /** Batches with remaining stock, oldest first (FIFO consumption order). */
    public function openBatches(int $packageId): array
    {
        return $this->fetchAll(
            'SELECT id, quantity, sold, (quantity - sold) AS remaining, bundle_price
               FROM inventory
              WHERE package_id = ? AND quantity - sold > 0
              ORDER BY id ASC',
            [$packageId]
        );
    }

    /** Track N bundles sold from one batch and keep its status correct. */
    public function addSold(int $inventoryId, int $bundles): bool
    {
        return $this->execute(
            "UPDATE inventory
                SET sold = sold + ?,
                    status = IF(quantity - sold - ? > 0, 'active', 'closed')
              WHERE id = ?",
            [$bundles, $bundles, $inventoryId]
        ) > 0;
    }

    /** Return N bundles back to one batch (edit/delete of a sale). */
    public function releaseSold(int $inventoryId, int $bundles): bool
    {
        return $this->execute(
            "UPDATE inventory
                SET sold = sold - ?,
                    status = IF(quantity - sold + ? > 0, 'active', 'closed')
              WHERE id = ?",
            [$bundles, $bundles, $inventoryId]
        ) > 0;
    }

    // ------------------------------------------------------------------
    // Sale allocations (which batches each sale consumed)
    // ------------------------------------------------------------------

    public function addAllocation(int $saleId, int $inventoryId, int $bundles): int
    {
        return $this->insert('sale_allocations', [
            'sale_id'      => $saleId,
            'inventory_id' => $inventoryId,
            'bundles'      => $bundles,
        ]);
    }

    /** Batches a sale consumed, with batch remaining before/after release. */
    public function allocationsForSale(int $saleId): array
    {
        return $this->fetchAll(
            'SELECT a.*, i.quantity, i.sold, i.bundle_price
               FROM sale_allocations a
               JOIN inventory i ON i.id = a.inventory_id
              WHERE a.sale_id = ?
              ORDER BY a.id ASC',
            [$saleId]
        );
    }

    public function deleteAllocations(int $saleId): void
    {
        $this->execute('DELETE FROM sale_allocations WHERE sale_id = ?', [$saleId]);
    }

    /** A batch can only be deleted when nothing was sold from it and it is empty. */
    public function soldFromBatch(int $inventoryId): int
    {
        return $this->fetchInt('SELECT sold FROM inventory WHERE id = ?', [$inventoryId]);
    }

    // ------------------------------------------------------------------
    // Aggregates for dashboard / reports
    // ------------------------------------------------------------------

    /** Total remaining bundles across all packages (الكروت المتبقية). */
    public function remainingTotal(): int
    {
        return $this->fetchInt('SELECT COALESCE(SUM(quantity - sold), 0) FROM inventory');
    }

    public function totalBundles(): int
    {
        return $this->fetchInt("SELECT COALESCE(SUM(quantity - sold), 0) FROM inventory WHERE status = 'active'");
    }

    /** Per package: remaining bundles (sum over batches) and stock value. */
    public function stockByPackage(): array
    {
        return $this->fetchAll(
            "SELECT p.id, p.name, p.bundle_price, p.low_stock_threshold,
                    COALESCE(SUM(i.quantity), 0) AS stock_in,
                    COALESCE(SUM(i.sold), 0) AS sold,
                    COALESCE(SUM(i.quantity - i.sold), 0) AS bundles,
                    COALESCE(SUM((i.quantity - i.sold) * i.bundle_price), 0) AS value
               FROM packages p
          LEFT JOIN inventory i ON i.package_id = p.id
           GROUP BY p.id, p.name, p.bundle_price, p.low_stock_threshold
           ORDER BY p.bundle_price DESC"
        );
    }

    public function lowStock(): array
    {
        return $this->fetchAll(
            "SELECT p.id, p.name, p.bundle_price, p.low_stock_threshold,
                    COALESCE(SUM(i.quantity - i.sold), 0) AS bundles
               FROM packages p
          LEFT JOIN inventory i ON i.package_id = p.id
              WHERE p.status = 'active'
           GROUP BY p.id, p.name, p.bundle_price, p.low_stock_threshold
             HAVING bundles <= p.low_stock_threshold
           ORDER BY bundles ASC"
        );
    }
}
