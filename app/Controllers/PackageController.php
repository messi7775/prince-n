<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Models\Package;
use Models\Inventory;
use Session;

final class PackageController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $this->view('packages/index', [
            'pageTitle' => 'الباقات',
            'active'    => 'packages',
            'packages'  => (new Package())->all(),
            'error'     => Session::get('package_error'),
        ]);

        Session::forget('package_error');
    }

    public function store(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $name        = trim((string)$request->input('name', ''));
        $bundlePrice = (int)$request->input('bundle_price', 0);
        $status      = (string)$request->input('status', 'active');
        $threshold   = (int)$request->input('low_stock_threshold', 5);

        if ($name === '' || $bundlePrice <= 0 || !in_array($status, ['active', 'inactive'], true) || $threshold < 0) {
            Session::set('package_error', 'بيانات الباقة غير صحيحة');
            $this->redirect('/packages');
        }

        if ((new Package())->findByName($name) !== null) {
            Session::set('package_error', 'يوجد باقة بنفس الاسم بالفعل: ' . $name);
            $this->redirect('/packages');
        }

        $db = \Database::connection();
        try {
            $db->beginTransaction();

            $data = [
                'name'                => $name,
                'bundle_price'        => $bundlePrice,
                'status'              => $status,
                'low_stock_threshold' => $threshold,
            ];

            $packageId = (new Package())->create($data);

            // Business rule: a new package starts with 5 bundles automatically.
            (new Inventory())->create([
                'package_id'   => $packageId,
                'quantity'     => 5,
                'sold'         => 0,
                'bundle_price' => $bundlePrice,
                'status'       => 'active',
                'note'         => 'الدفعة الافتتاحية',
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        $this->logAudit('package_create', 'إضافة باقة: ' . $name, $data);
        $this->redirect('/packages');
    }

    public function update(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id          = (int)$request->input('id', 0);
        $name        = trim((string)$request->input('name', ''));
        $bundlePrice = (int)$request->input('bundle_price', 0);
        $status      = (string)$request->input('status', 'active');
        $threshold   = (int)$request->input('low_stock_threshold', 5);

        if ($id <= 0 || $name === '' || $bundlePrice <= 0 || !in_array($status, ['active', 'inactive'], true) || $threshold < 0) {
            Session::set('package_error', 'بيانات الباقة غير صحيحة');
            $this->redirect('/packages');
        }

        $package = new Package();
        if ($package->find($id) === null) {
            Session::set('package_error', 'الباقة غير موجودة');
            $this->redirect('/packages');
        }

        $existing = $package->findByName($name);
        if ($existing !== null && (int)$existing['id'] !== $id) {
            Session::set('package_error', 'يوجد باقة بنفس الاسم بالفعل: ' . $name);
            $this->redirect('/packages');
        }

        $data = [
            'name'                => $name,
            'bundle_price'        => $bundlePrice,
            'status'              => $status,
            'low_stock_threshold' => $threshold,
        ];

        $package->update($id, $data);
        $this->logAudit('package_update', 'تعديل باقة: ' . $name, $data);
        $this->redirect('/packages');
    }

    public function delete(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            $this->redirect('/packages');
        }

        $package = new Package();
        $pkg = $package->find($id);
        if (!$pkg) {
            $this->redirect('/packages');
        }

        // Block deletion when any batch still holds stock or had sales recorded.
        $inventory = new Inventory();
        $stillStocked = $inventory->fetchInt(
            'SELECT COUNT(*) FROM inventory WHERE package_id = ? AND (quantity - sold > 0 OR sold > 0)',
            [$id]
        ) > 0;
        if ($stillStocked) {
            Session::set('package_error', 'لا يمكن حذف الباقة لأن عليها مخزون حالي. صفّر المخزون أولًا.');
            $this->logAudit('package_delete_blocked', 'منع حذف باقة عليها مخزون: ' . ($pkg['name'] ?? ''));
            $this->redirect('/packages');
        }

        $package->delete($id);
        $this->logAudit('package_delete', 'حذف باقة: ' . ($pkg['name'] ?? ''));
        $this->redirect('/packages');
    }
}
