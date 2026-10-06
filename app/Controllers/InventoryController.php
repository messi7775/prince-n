<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Models\Inventory;
use Session;

final class InventoryController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $inventory = new Inventory();
        $this->view('inventory/index', [
            'pageTitle' => 'المخزون',
            'active'    => 'inventory',
            'items'     => $inventory->all(),
            'lowStock'  => $inventory->lowStock(),
            'movements' => $inventory->movements(),
            'error'     => Session::get('inventory_error'),
        ]);
        Session::forget('inventory_error');
    }

    public function add(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        $quantity = (int)$request->input('quantity', 0);
        if ($id <= 0 || $quantity <= 0) {
            Session::set('inventory_error', 'الكمية المضافة يجب أن تكون أكبر من صفر');
            $this->redirect('/inventory');
        }

        $inventory = new Inventory();
        $row = $inventory->find($id);
        if ($row === null) {
            Session::set('inventory_error', 'سجل المخزون غير موجود');
            $this->redirect('/inventory');
        }

        $oldQty = (int)$row['quantity'];
        $newQty = $oldQty + $quantity;
        $price = (int)$row['bundle_price'];
        $db = \Database::connection();

        try {
            $db->beginTransaction();
            $inventory->addQuantity($id, $quantity);
            $inventory->logMovement([
                'package_id'   => (int)$row['package_id'],
                'action'       => 'add',
                'old_quantity' => $oldQty,
                'new_quantity' => $newQty,
                'bundle_price' => $price,
                'old_value'    => $oldQty * $price,
                'new_value'    => $newQty * $price,
                'note'         => 'إضافة ' . $quantity . ' شدة',
            ]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }

        $this->logAudit('inventory_add', 'إضافة ' . $quantity . ' شدة للباقة #' . $row['package_id']);
        $this->redirect('/inventory');
    }

    public function edit(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        $quantity = (int)$request->input('quantity', 0);
        if ($id <= 0 || $quantity < 0) {
            Session::set('inventory_error', 'الكمية غير صحيحة');
            $this->redirect('/inventory');
        }

        $inventory = new Inventory();
        $row = $inventory->find($id);
        if ($row === null) {
            Session::set('inventory_error', 'سجل المخزون غير موجود');
            $this->redirect('/inventory');
        }

        $oldQty = (int)$row['quantity'];
        $price = (int)$row['bundle_price'];
        $db = \Database::connection();

        try {
            $db->beginTransaction();
            $inventory->setQuantity($id, $quantity);
            $inventory->logMovement([
                'package_id'   => (int)$row['package_id'],
                'action'       => 'edit',
                'old_quantity' => $oldQty,
                'new_quantity' => $quantity,
                'bundle_price' => $price,
                'old_value'    => $oldQty * $price,
                'new_value'    => $quantity * $price,
                'note'         => 'تعديل العدد من ' . $oldQty . ' إلى ' . $quantity,
            ]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }

        $this->logAudit('inventory_edit', 'تعديل مخزون الباقة #' . $row['package_id'] . ' من ' . $oldQty . ' إلى ' . $quantity);
        $this->redirect('/inventory');
    }

    public function delete(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        if ($id <= 0) $this->redirect('/inventory');

        $inventory = new Inventory();
        $row = $inventory->find($id);
        if ($row === null) $this->redirect('/inventory');

        // Do not delete physical stock that is still positive.
        if ((int)$row['quantity'] > 0) {
            Session::set('inventory_error', 'لا يمكن حذف سجل مخزون يحتوي على كمية موجبة. عدّل الكمية إلى صفر أولًا.');
            $this->redirect('/inventory');
        }

        $oldQty = (int)$row['quantity'];
        $price = (int)$row['bundle_price'];
        $db = \Database::connection();

        try {
            $db->beginTransaction();
            $inventory->logMovement([
                'package_id'   => (int)$row['package_id'],
                'action'       => 'delete',
                'old_quantity' => $oldQty,
                'new_quantity' => 0,
                'bundle_price' => $price,
                'old_value'    => $oldQty * $price,
                'new_value'    => 0,
                'note'         => 'حذف سجل المخزون',
            ]);
            $inventory->delete($id);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }

        $this->logAudit('inventory_delete', 'حذف سجل مخزون الباقة #' . $row['package_id']);
        $this->redirect('/inventory');
    }
}
