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

        // Item rows per sale (used to prefill the multi-package edit form).
        $itemsBySale = [];
        foreach ($result['rows'] as $row) {
            $itemsBySale[(int)$row['id']] = (new Sale())->items((int)$row['id']);
        }

        $this->view('sales/index', [
            'pageTitle'    => 'المبيعات',
            'active'       => 'sales',
            'itemsBySale'  => $itemsBySale,
            'sales'        => $result['rows'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'pages'        => $result['pages'],
            'filters'      => $filters,
            'packages'     => (new Package())->active(),
            'distributors' => (new Distributor())->all(),
        ]);
    }

    /** Printable receipt (وصل) for one sale. */
    public function receipt(Request $request): void
    {
        $this->requireAuth();

        $id = (int)$request->input('id', 0);
        $sale = $id > 0 ? (new Sale())->findWithNames($id) : null;
        if (!$sale) {
            $this->redirect('/sales');
        }

        $this->view('sales/receipt', [
            'sale'     => $sale,
            'items'    => (new Sale())->items($id),
            'pageTitle' => 'وصل بيع #' . $id,
        ], null);
    }

    public function store(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        [$items, $meta] = $this->validateSaleItems($request);
        if ($items === null) {
            $this->redirect('/sales');
        }

        // Business rule: a sale may never exceed the available stock (per package).
        $inventory = new Inventory();
        foreach ($items as $item) {
            if ($inventory->availableForPackage((int)$item['package_id']) < (int)$item['bundles_count']) {
                Session::flash('error', 'الكمية المطلوبة من «' . $item['package_name'] . '» غير متوفرة في المخزون — لا يمكن البيع فوق المخزون.');
                $this->redirect('/sales');
            }
        }

        $stock = new StockService();
        $db = \Database::connection();

        try {
            $db->beginTransaction();

            // ONE sale operation: header total = sum of items; a single cash
            // movement and a single receipt/report row.
            $saleId = (new Sale())->create([
                'distributor_id' => $meta['distributor_id'],
                'package_id'     => null,
                'bundles_count'  => 0,
                'bundle_price'   => 0,
                'total'          => $meta['total'],
                'payment_type'   => $meta['payment_type'],
                'note'           => $meta['note'],
            ]);

            foreach ($items as $item) {
                if ($stock->consume((int)$item['package_id'], (int)$item['bundles_count'], $saleId) === null) {
                    throw new \RuntimeException('insufficient_stock');
                }
                (new Sale())->createItem([
                    'sale_id'       => $saleId,
                    'package_id'    => $item['package_id'],
                    'bundles_count' => $item['bundles_count'],
                    'bundle_price'  => $item['bundle_price'],
                    'total'         => $item['total'],
                ]);
            }

            if ($meta['payment_type'] === 'cash') {
                (new CashMovement())->create([
                    'direction'      => CashMovement::IN,
                    'amount'         => $meta['total'],
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

        $summary = implode(' + ', array_map(
            static fn ($i) => $i['bundles_count'] . '×' . $i['package_name'] . '@' . $i['bundle_price'],
            $items
        ));
        $this->logAudit('sale_create', "بيع {$meta['total']} ({$summary})", ['items' => $items, 'meta' => $meta]);
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

        [$items, $meta] = $this->validateSaleItems($request, true);
        if ($items === null) {
            $this->redirect('/sales');
        }

        $saleModel = new Sale();
        $inventory = new Inventory();

        // Bundles consumed by the OLD sale per package return to stock first,
        // so per-package availability only needs the difference to fit.
        $oldByPackage = [];
        foreach ($saleModel->items($id) as $oi) {
            $oldByPackage[(int)$oi['package_id']] = ($oldByPackage[(int)$oi['package_id']] ?? 0) + (int)$oi['bundles_count'];
        }

        $newByPackage = [];
        foreach ($items as $item) {
            $newByPackage[(int)$item['package_id']] = ($newByPackage[(int)$item['package_id']] ?? 0) + (int)$item['bundles_count'];
        }

        foreach ($newByPackage as $pid => $need) {
            $available = $inventory->availableForPackage($pid) + ($oldByPackage[$pid] ?? 0);
            if ($available < $need) {
                $pkg = (new Package())->find($pid);
                Session::flash('error', 'الكمية المطلوبة من «' . ($pkg['name'] ?? '#') . '» غير متوفرة في المخزون — لا يمكن البيع فوق المخزون.');
                $this->redirect('/sales');
            }
        }

        $stock = new StockService();
        $db = \Database::connection();

        try {
            $db->beginTransaction();

            // Reverse the old sale: return its bundles to the original batches.
            $stock->releaseSale($id, 'return', 'تعديل البيع #' . $id);
            (new CashMovement())->deleteByReference('sale', $id);

            $saleModel->deleteItems($id);
            $saleModel->update($id, [
                'distributor_id' => $meta['distributor_id'],
                'package_id'     => null,
                'bundles_count'  => 0,
                'bundle_price'   => 0,
                'total'          => $meta['total'],
                'payment_type'   => $meta['payment_type'],
                'note'           => $meta['note'],
            ]);

            foreach ($items as $item) {
                if ($stock->consume((int)$item['package_id'], (int)$item['bundles_count'], $id) === null) {
                    throw new \RuntimeException('insufficient_stock');
                }
                $saleModel->createItem([
                    'sale_id'       => $id,
                    'package_id'    => $item['package_id'],
                    'bundles_count' => $item['bundles_count'],
                    'bundle_price'  => $item['bundle_price'],
                    'total'         => $item['total'],
                ]);
            }

            if ($meta['payment_type'] === 'cash') {
                (new CashMovement())->create([
                    'direction'      => CashMovement::IN,
                    'amount'         => $meta['total'],
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
        $newType = $meta['payment_type'] === 'cash' ? 'نقدي' : 'آجل';
        $summary = implode(' + ', array_map(
            static fn ($i) => $i['bundles_count'] . '×' . $i['package_name'] . '@' . $i['bundle_price'],
            $items
        ));
        $this->logAudit(
            'sale_update',
            "تعديل بيع #{$id}: من {$old['total']} {$oldType} إلى {$meta['total']} {$newType} ({$summary})",
            ['old' => $old, 'items' => $items, 'meta' => $meta]
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
     * Validate a multi-package sale.
     * Reads package_id[] / bundles_count[] arrays — each item keeps its own
     * bundle price taken from the package record. Items of the SAME package
     * are merged into one. Cash sales may have no distributor; a distributor
     * on a cash sale is kept only as a reference and is NOT included in debt.
     * Returns [items|null, meta]; flashes an error message when invalid.
     */
    private function validateSaleItems(Request $request, bool $allowInactivePackage = false): array
    {
        $packageIds    = array_values(array_filter(array_map('intval', (array)$request->input('package_id', []))));
        $bundlesCounts = array_map('intval', (array)$request->input('bundles_count', []));
        $distributorId = (int)$request->input('distributor_id', 0);
        $paymentType   = (string)$request->input('payment_type', 'cash');
        $note          = (string)$request->input('note', '');

        $packageModel     = new Package();
        $distributorModel = new Distributor();

        $error = static function (string $message): array {
            Session::flash('error', $message);
            return [null, null];
        };

        if (count($packageIds) === 0 || !in_array($paymentType, ['cash', 'credit'], true)) {
            return $error('بيانات البيع غير صحيحة — اختر باقة واحدة على الأقل');
        }

        if ($paymentType === 'credit' && $distributorId <= 0) {
            return $error('البيع الآجل يتطلب اختيار الموزع');
        }

        if ($distributorId > 0 && $distributorModel->find($distributorId) === null) {
            return $error('الموزع غير موجود');
        }

        // Merge repeated packages and validate each item.
        $merged = [];
        foreach ($packageIds as $i => $packageId) {
            $bundles = $bundlesCounts[$i] ?? 0;
            if ($packageId <= 0) {
                continue;
            }

            $package = $packageModel->find($packageId);
            if ($package === null) {
                return $error('إحدى الباقات غير موجودة');
            }
            if (!$allowInactivePackage && $package['status'] !== 'active') {
                return $error('الباقة «' . $package['name'] . '» غير نشطة');
            }

            $bundlePrice = (int)$package['bundle_price'];
            if ($bundlePrice <= 0) {
                return $error('سعر الشدة غير صحيح للباقة «' . $package['name'] . '»');
            }

            $key = $packageId;
            if (isset($merged[$key])) {
                $merged[$key]['bundles_count'] += $bundles;
            } else {
                $merged[$key] = [
                    'package_id'    => $packageId,
                    'package_name'  => $package['name'],
                    'bundle_price'  => $bundlePrice,
                    'bundles_count' => $bundles,
                ];
            }
        }

        if (count($merged) === 0) {
            return $error('بيانات البيع غير صحيحة — اختر باقة واحدة على الأقل');
        }

        $items = [];
        $total = 0;
        foreach ($merged as $item) {
            if ($item['bundles_count'] <= 0) {
                return $error('عدد الشدات يجب أن يكون أكبر من صفر لكل باقة');
            }
            $item['total'] = (int)$item['bundles_count'] * (int)$item['bundle_price'];
            $total += $item['total'];
            $items[] = $item;
        }

        if ($total <= 0) {
            return $error('إجمالي البيع غير صحيح');
        }

        $meta = [
            'distributor_id' => $distributorId > 0 ? $distributorId : null,
            'total'          => $total,
            'payment_type'   => $paymentType,
            'note'           => $note !== '' ? $note : null,
        ];

        return [$items, $meta];
    }
}
