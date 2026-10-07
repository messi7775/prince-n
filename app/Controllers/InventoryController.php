<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Session;
use Models\Inventory;
use Models\Package;

final class InventoryController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $inventory = new Inventory();

        $filters = [
            'q'          => (string)$request->input('q', ''),
            'package_id' => (int)$request->input('package_id', 0),
        ];

        $this->view('inventory/index', [
            'pageTitle' => 'المخزون',
            'active'    => 'inventory',
            'items'     => $inventory->searchBatches($filters),
            'lowStock'  => $inventory->lowStock(),
            'movements' => $inventory->movements(200),
            'packages'  => (new Package())->all(),
            'filters'   => $filters,
        ]);
    }

    /** Register a new stock batch (دفعة) for a package. */
    public function store(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $packageId    = (int)$request->input('package_id', 0);
        $quantity     = (int)$request->input('quantity', 0);
        $bundlePrice  = (int)$request->input('bundle_price', -1);
        $purchaseCost = (int)$request->input('purchase_cost', 0);
        $note         = (string)$request->input('note', '');

        $package = $packageId > 0 ? (new Package())->find($packageId) : null;
        if ($package === null) {
            Session::flash('error', 'الباقة غير موجودة');
            $this->redirect('/inventory');
        }

        if ($quantity <= 0) {
            Session::flash('error', 'الكمية المضافة يجب أن تكون أكبر من صفر');
            $this->redirect('/inventory');
        }

        if ($bundlePrice < 0) {
            $bundlePrice = (int)$package['bundle_price'];
        }

        if ($purchaseCost < 0) {
            Session::flash('error', 'تكلفة الشراء غير صحيحة');
            $this->redirect('/inventory');
        }

        $inventory = new Inventory();
        $db = \Database::connection();

        try {
            $db->beginTransaction();

            $batchId = $inventory->create([
                'package_id'    => $packageId,
                'quantity'      => $quantity,
                'sold'          => 0,
                'bundle_price'  => $bundlePrice,
                'purchase_cost' => $purchaseCost > 0 ? $purchaseCost : null,
                'status'        => 'active',
                'note'          => $note !== '' ? $note : null,
            ]);

            $inventory->logMovement([
                'package_id'   => $packageId,
                'inventory_id' => $batchId,
                'action'       => 'add',
                'old_quantity' => 0,
                'new_quantity' => $quantity,
                'bundle_price' => $bundlePrice,
                'old_value'    => 0,
                'new_value'    => $quantity * $bundlePrice,
                'note'         => 'إضافة دفعة جديدة: ' . $quantity . ' شدة',
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }

        $this->logAudit('inventory_add', 'إضافة دفعة مخزون: ' . $quantity . ' شدة للباقة ' . $package['name']);
        $this->redirect('/inventory');
    }

    /** Correct an existing batch (quantity / purchase cost / note). */
    public function edit(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id           = (int)$request->input('id', 0);
        $quantity     = (int)$request->input('quantity', -1);
        $purchaseCost = (int)$request->input('purchase_cost', -1);
        $note         = (string)$request->input('note', '');

        if ($id <= 0 || $quantity < 0) {
            Session::flash('error', 'الكمية غير صحيحة');
            $this->redirect('/inventory');
        }

        $inventory = new Inventory();
        $row = $inventory->find($id);
        if ($row === null) {
            Session::flash('error', 'الدفعة غير موجودة');
            $this->redirect('/inventory');
        }

        // A correction may never set the received quantity below what was sold.
        if ($quantity < (int)$row['sold']) {
            Session::flash('error', 'لا يمكن أن تكون كمية الدفعة أقل من الكمية المباعة منها (' . (int)$row['sold'] . ' شدة)');
            $this->redirect('/inventory');
        }

        if ($purchaseCost < 0) {
            $purchaseCost = (int)($row['purchase_cost'] ?? 0);
        }

        $oldQty = (int)$row['quantity'];
        $price  = (int)$row['bundle_price'];
        $db = \Database::connection();

        try {
            $db->beginTransaction();

            $inventory->update($id, [
                'quantity'      => $quantity,
                'purchase_cost' => $purchaseCost > 0 ? $purchaseCost : null,
                'note'          => $note !== '' ? $note : null,
                'status'        => $quantity - (int)$row['sold'] > 0 ? 'active' : 'closed',
            ]);

            $inventory->logMovement([
                'package_id'   => (int)$row['package_id'],
                'inventory_id' => $id,
                'action'       => 'edit',
                'old_quantity' => $oldQty,
                'new_quantity' => $quantity,
                'bundle_price' => $price,
                'old_value'    => $oldQty * $price,
                'new_value'    => $quantity * $price,
                'note'         => 'تصحيح الكمية من ' . $oldQty . ' إلى ' . $quantity . ' (المتبقي ' . ($quantity - (int)$row['sold']) . ')',
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }

        $this->logAudit('inventory_edit', 'تصحيح دفعة #' . $id . ' من ' . $oldQty . ' إلى ' . $quantity);
        $this->redirect('/inventory');
    }

    /** Delete an empty batch that never sold anything. */
    public function delete(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        if ($id <= 0) $this->redirect('/inventory');

        $inventory = new Inventory();
        $row = $inventory->find($id);
        if ($row === null) $this->redirect('/inventory');

        if ((int)$row['sold'] > 0) {
            Session::flash('error', 'لا يمكن حذف دفعة بيعت منها شدات — احذف أو عدّل عمليات البيع المرتبطة بها أولًا.');
            $this->redirect('/inventory');
        }

        if ((int)$row['quantity'] > 0) {
            Session::flash('error', 'لا يمكن حذف دفعة تحتوي على كمية موجبة. عدّل الكمية إلى صفر أولًا.');
            $this->redirect('/inventory');
        }

        $oldQty = (int)$row['quantity'];
        $price  = (int)$row['bundle_price'];
        $db = \Database::connection();

        try {
            $db->beginTransaction();
            $inventory->logMovement([
                'package_id'   => (int)$row['package_id'],
                'inventory_id' => $id,
                'action'       => 'delete',
                'old_quantity' => $oldQty,
                'new_quantity' => 0,
                'bundle_price' => $price,
                'old_value'    => $oldQty * $price,
                'new_value'    => 0,
                'note'         => 'حذف دفعة مخزون',
            ]);
            $inventory->delete($id);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }

        $this->logAudit('inventory_delete', 'حذف دفعة مخزون #' . $id);
        $this->redirect('/inventory');
    }
}
