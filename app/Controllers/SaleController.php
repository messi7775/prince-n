<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Models\Sale;
use Models\Package;
use Models\Distributor;
use Models\Inventory;
use Models\CashMovement;

final class SaleController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $this->view('sales/index', [
            'pageTitle'    => 'المبيعات',
            'active'       => 'sales',
            'sales'        => (new Sale())->all(),
            'packages'     => (new Package())->active(),
            'distributors' => (new Distributor())->all(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $data = $this->validateSaleInput($request);
        if ($data === null) {
            $this->redirect('/sales');
        }

        $db = \Database::connection();

        try {
            $db->beginTransaction();

            $this->deductInventory($data['package_id'], $data['bundles_count']);
            $saleId = (new Sale())->create($data);

            if ($data['payment_type'] === 'cash') {
                (new CashMovement())->create([
                    'direction'      => CashMovement::IN,
                    'amount'         => $data['total'],
                    'reason'         => 'بيع كروت (نقدي)',
                    'reference_type' => 'sale',
                    'reference_id'   => $saleId,
                ]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        $this->logAudit('sale_create', "بيع {$data['bundles_count']} شدة × {$data['bundle_price']} = {$data['total']}", $data);
        $this->redirect('/sales');
    }

    public function update(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        $old = $id > 0 ? (new Sale())->find($id) : null;
        if (!$old) {
            $this->redirect('/sales');
        }

        $new = $this->validateSaleInput($request, true);
        if ($new === null) {
            $this->redirect('/sales');
        }

        $db = \Database::connection();

        try {
            $db->beginTransaction();

            // Reverse the old sale first, then apply the new sale.
            $this->restoreInventory((int)$old['package_id'], (int)$old['bundles_count']);
            (new CashMovement())->deleteByReference('sale', $id);

            $this->deductInventory($new['package_id'], $new['bundles_count']);
            (new Sale())->update($id, $new);

            if ($new['payment_type'] === 'cash') {
                (new CashMovement())->create([
                    'direction'      => CashMovement::IN,
                    'amount'         => $new['total'],
                    'reason'         => 'بيع كروت (نقدي)',
                    'reference_type' => 'sale',
                    'reference_id'   => $id,
                ]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        $oldType = $old['payment_type'] === 'cash' ? 'نقدي' : 'آجل';
        $newType = $new['payment_type'] === 'cash' ? 'نقدي' : 'آجل';
        $this->logAudit(
            'sale_update',
            "تعديل بيع: من {$old['bundles_count']} شدة / {$old['total']} {$oldType} إلى {$new['bundles_count']} شدة / {$new['total']} {$newType}",
            ['old' => $old, 'new' => $new]
        );

        $this->redirect('/sales');
    }

    public function delete(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        if ($id > 0) {
            $sale = (new Sale())->find($id);
            if (!$sale) {
                $this->redirect('/sales');
            }

            $db = \Database::connection();

            try {
                $db->beginTransaction();

                // Deleting a sale reverses its inventory effect and cash effect.
                if ($sale['package_id'] !== null) {
                    $this->restoreInventory((int)$sale['package_id'], (int)$sale['bundles_count']);
                }
                (new Sale())->delete($id);
                (new CashMovement())->deleteByReference('sale', $id);

                $db->commit();
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }

            $type = $sale['payment_type'] === 'cash' ? 'نقدي' : 'آجل';
            $this->logAudit('sale_delete', "حذف عملية بيع #{$id}: {$sale['bundles_count']} شدة / {$sale['total']} {$type}");
        }

        $this->redirect('/sales');
    }

    /**
     * Validate sale input.
     * Cash sales may have no distributor. If a distributor is selected for a
     * cash sale, it is kept only as a reference and is NOT included in debt.
     * The authoritative bundle price always comes from the package record.
     */
    private function validateSaleInput(Request $request, bool $allowInactivePackage = false): ?array
    {
        $packageId     = (int)$request->input('package_id', 0);
        $distributorId = (int)$request->input('distributor_id', 0);
        $bundlesCount  = (int)$request->input('bundles_count', 0);
        $paymentType   = (string)$request->input('payment_type', 'cash');
        $note          = (string)$request->input('note', '');

        if ($packageId <= 0 || $bundlesCount <= 0 || !in_array($paymentType, ['cash', 'credit'], true)) {
            return null;
        }

        $package = (new Package())->find($packageId);
        if ($package === null) {
            return null;
        }
        if (!$allowInactivePackage && $package['status'] !== 'active') {
            return null;
        }

        if ($paymentType === 'credit' && $distributorId <= 0) {
            return null;
        }

        if ($distributorId > 0 && (new Distributor())->find($distributorId) === null) {
            return null;
        }

        $bundlePrice = (int)$package['bundle_price'];
        if ($bundlePrice <= 0) {
            return null;
        }

        $total = $bundlesCount * $bundlePrice;
        if ($total <= 0) {
            return null;
        }

        return [
            'distributor_id' => $distributorId > 0 ? $distributorId : null,
            'package_id'     => $packageId,
            'bundles_count'  => $bundlesCount,
            'bundle_price'   => $bundlePrice,
            'total'          => $total,
            'payment_type'   => $paymentType,
            'note'           => $note !== '' ? $note : null,
        ];
    }

    /**
     * Apply a sale to stock. Stock is allowed to go negative because the
     * business rule permits recording a sale above current physical stock.
     * This makes the later reversal exact when the sale is edited/deleted.
     */
    private function deductInventory(int $packageId, int $count): void
    {
        $inventory = new Inventory();
        $row = $inventory->findOrCreateByPackage($packageId, 0, (int)(new Package())->find($packageId)['bundle_price']);
        $oldQty = (int)$row['quantity'];
        $newQty = $oldQty - $count;
        $price = (int)$row['bundle_price'];

        $inventory->setQuantity($row['id'], $newQty);
        $inventory->logMovement([
            'package_id'   => $packageId,
            'action'       => 'edit',
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
            'bundle_price' => $price,
            'old_value'    => $oldQty * $price,
            'new_value'    => $newQty * $price,
            'note'         => 'خصم بسبب بيع ' . $count . ' شدة',
        ]);
    }

    /** Restore exactly the quantity consumed by a previous sale. */
    private function restoreInventory(int $packageId, int $count): void
    {
        if ($packageId <= 0 || $count <= 0) {
            return;
        }

        $inventory = new Inventory();
        $row = $inventory->findByPackageId($packageId);
        if ($row === null) {
            // The package may have been deleted after the sale. There is then
            // no valid stock row to restore without recreating deleted data.
            return;
        }

        $oldQty = (int)$row['quantity'];
        $newQty = $oldQty + $count;
        $price = (int)$row['bundle_price'];

        $inventory->setQuantity((int)$row['id'], $newQty);
        $inventory->logMovement([
            'package_id'   => $packageId,
            'action'       => 'edit',
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
            'bundle_price' => $price,
            'old_value'    => $oldQty * $price,
            'new_value'    => $newQty * $price,
            'note'         => 'إرجاع ' . $count . ' شدة بسبب تعديل/حذف بيع',
        ]);
    }
}
