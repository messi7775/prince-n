<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Session;
use Models\Sale;
use Models\Package;
use Models\Distributor;
use Models\Inventory;
use Models\CashMovement;
use Services\StockService;

final class SaleController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $filters = [
            'q'              => (string)$request->input('q', ''),
            'date_from'      => (string)$request->input('date_from', ''),
            'date_to'        => (string)$request->input('date_to', ''),
            'distributor_id' => (int)$request->input('distributor_id', 0),
            'package_id'     => (int)$request->input('package_id', 0),
            'payment_type'   => (string)$request->input('payment_type', ''),
            'sort'           => (string)$request->input('sort', 'date'),
            'dir'            => (string)$request->input('dir', 'desc'),
            'page'           => (int)$request->input('page', 1),
            'per_page'       => 20,
        ];

        $result = (new Sale())->search($filters);

        $this->view('sales/index', [
            'pageTitle'    => 'المبيعات',
            'active'       => 'sales',
            'sales'        => $result['rows'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'pages'        => $result['pages'],
            'filters'      => $filters,
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

        // Business rule: a sale may never exceed the available stock.
        if ((new Inventory())->availableForPackage((int)$data['package_id']) < (int)$data['bundles_count']) {
            Session::flash('error', 'الكمية المطلوبة غير متوفرة في المخزون — لا يمكن البيع فوق المخزون.');
            $this->redirect('/sales');
        }

        $stock = new StockService();

        $db = \Database::connection();

        try {
            $db->beginTransaction();

            $saleId = (new Sale())->create($data);

            // Allocate stock exactly to the batches (FIFO) and log movements.
            if ($stock->consume((int)$data['package_id'], (int)$data['bundles_count'], $saleId) === null) {
                throw new \RuntimeException('insufficient_stock');
            }

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

        $stock = new StockService();

        // The old sale's bundles go back to stock before the new sale is
        // applied, so a same-package edit only needs the difference to fit.
        $available = (new Inventory())->availableForPackage((int)$new['package_id']);
        if ((int)$old['package_id'] === (int)$new['package_id']) {
            $available += (int)$old['bundles_count'];
        }
        if ($available < (int)$new['bundles_count']) {
            Session::flash('error', 'الكمية المطلوبة غير متوفرة في المخزون — لا يمكن البيع فوق المخزون.');
            $this->redirect('/sales');
        }

        $db = \Database::connection();

        try {
            $db->beginTransaction();

            // Reverse the old sale: return its bundles to the original batches.
            $stock->releaseSale($id, 'return', 'تعديل البيع #' . $id);
            (new CashMovement())->deleteByReference('sale', $id);

            (new Sale())->update($id, $new);

            if ($stock->consume((int)$new['package_id'], (int)$new['bundles_count'], $id) === null) {
                throw new \RuntimeException('insufficient_stock');
            }

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

                // Deleting a sale returns its bundles to the original batches
                // and removes its cash effect (when it was a cash sale).
                (new StockService())->releaseSale($id, 'sale_delete', 'حذف البيع #' . $id);
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
}
