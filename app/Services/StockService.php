<?php
declare(strict_types=1);

namespace Services;

use Models\Inventory;

/**
 * StockService — keeps inventory quantities exact.
 *
 * consume(): consumes bundles from a package's batches FIFO (oldest first),
 *   logging a 'sale' movement per batch and recording the allocation so the
 *   exact same batches can be restored later. Returns null when the package
 *   does not have enough stock (بيع فوق المخزون ممنوع).
 *
 * releaseSale(): returns every bundle a sale consumed back to its original
 *   batches (on sale edit/delete) and logs 'sale_delete'/'return' movements.
 */
final class StockService
{
    private Inventory $inventory;

    public function __construct()
    {
        $this->inventory = new Inventory();
    }

    /**
     * Consume $bundles from $packageId's batches, FIFO.
     * Returns the list of allocations [[inventory_id, bundles],...] or null
     * when stock is insufficient.
     */
    public function consume(int $packageId, int $bundles, ?int $saleId = null, string $saleNote = ''): ?array
    {
        $open = $this->inventory->openBatches($packageId);
        $available = array_sum(array_map(static fn ($b) => (int)$b['remaining'], $open));

        if ($available < $bundles) {
            return null;
        }

        $allocations = [];
        $need = $bundles;

        foreach ($open as $batch) {
            if ($need <= 0) {
                break;
            }

            $take = min($need, (int)$batch['remaining']);
            $oldRemaining = (int)$batch['remaining'];
            $newRemaining = $oldRemaining - $take;
            $price = (int)$batch['bundle_price'];

            $this->inventory->addSold((int)$batch['id'], $take);
            if ($saleId !== null) {
                $this->inventory->addAllocation($saleId, (int)$batch['id'], $take);
            }

            $this->inventory->logMovement([
                'package_id'   => $packageId,
                'inventory_id' => (int)$batch['id'],
                'action'       => 'sale',
                'old_quantity' => $oldRemaining,
                'new_quantity' => $newRemaining,
                'bundle_price' => $price,
                'old_value'    => $oldRemaining * $price,
                'new_value'    => $newRemaining * $price,
                'note'         => 'بيع ' . $take . ' شدة' . ($saleNote !== '' ? ' — ' . $saleNote : ''),
            ]);

            $allocations[] = ['inventory_id' => (int)$batch['id'], 'bundles' => $take];
            $need -= $take;
        }

        return $allocations;
    }

    /**
     * Return all bundles a sale consumed back to their original batches.
     * $action selects the movement label: 'sale_delete' or 'return' (edit).
     */
    public function releaseSale(int $saleId, string $action = 'sale_delete', string $note = ''): void
    {
        $allocations = $this->inventory->allocationsForSale($saleId);

        foreach ($allocations as $a) {
            $oldRemaining = (int)$a['quantity'] - (int)$a['sold'];
            $newRemaining = $oldRemaining + (int)$a['bundles'];
            $price = (int)$a['bundle_price'];

            $this->inventory->releaseSold((int)$a['inventory_id'], (int)$a['bundles']);

            $this->inventory->logMovement([
                'package_id'   => (int)$a['inventory_id'] ? $this->packageIdOf((int)$a['inventory_id']) : 0,
                'inventory_id' => (int)$a['inventory_id'],
                'action'       => $action,
                'old_quantity' => $oldRemaining,
                'new_quantity' => $newRemaining,
                'bundle_price' => $price,
                'old_value'    => $oldRemaining * $price,
                'new_value'    => $newRemaining * $price,
                'note'         => 'إرجاع ' . (int)$a['bundles'] . ' شدة' . ($note !== '' ? ' — ' . $note : ''),
            ]);
        }

        $this->inventory->deleteAllocations($saleId);
    }

    private function packageIdOf(int $inventoryId): int
    {
        $row = $this->inventory->find($inventoryId);
        return (int)($row['package_id'] ?? 0);
    }
}
