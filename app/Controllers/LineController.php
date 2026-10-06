<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Models\Line;
use Models\CashMovement;

final class LineController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();
        $line = new Line();
        $lines = $line->all();
        $this->view('lines/index', [
            'pageTitle' => 'الخطوط',
            'active'    => 'lines',
            'lines'     => $lines,
        ]);
    }

    public function store(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        $data = $this->validateLine($request);
        if ($data === null) { $this->redirect('/lines'); }
        $id = (new Line())->create($data);
        $this->logAudit('line_create', 'إضافة خط: ' . $data['name'], $data + ['id' => $id]);
        $this->redirect('/lines');
    }

    public function update(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        $id = (int)$request->input('id', 0);
        $line = new Line();
        $old = $id > 0 ? $line->find($id) : null;
        $data = $this->validateLine($request);
        if (!$old || $data === null) { $this->redirect('/lines'); }
        $line->update($id, $data);
        $this->logAudit('line_update', 'تعديل خط #' . $id, ['old' => $old, 'new' => $data]);
        $this->redirect('/lines');
    }

    public function delete(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        $id = (int)$request->input('id', 0);
        if ($id > 0) {
            $line = new Line();
            $old = $line->find($id);
            if ($old) {
                $db = \Database::connection();
                try {
                    $db->beginTransaction();
                    (new CashMovement())->deleteByLineId($id);
                    $line->delete($id);
                    // line_payments are removed by the FK ON DELETE CASCADE.
                    $db->commit();
                } catch (\Throwable $e) {
                    if ($db->inTransaction()) { $db->rollBack(); }
                    throw $e;
                }
                $this->logAudit('line_delete', 'حذف خط #' . $id, $old);
            }
        }
        $this->redirect('/lines');
    }

    public function payments(Request $request): void
    {
        $this->requireAuth();
        $line = new Line();
        $lines = $line->all();

        // تسديد الخطوط هو دائمًا مبلغ خارج من الصندوق.
        // تصحيح السجلات القديمة أيضًا حتى لا تبقى عمليات "دخل" مرتبطة بالخطوط.
        $db = \Database::connection();
        $db->exec("UPDATE line_payments SET direction = 'out' WHERE direction <> 'out'");
        $db->exec("UPDATE cash_movements SET direction = 'out', reason = 'دفع خط' WHERE reference_type = 'line_payment' AND direction <> 'out'");

        $payments = $line->allPayments();
        $this->view('lines/payments', [
            'pageTitle' => 'تسديد الخطوط',
            'active'    => 'line-payments',
            'lines'     => $lines,
            'payments'  => $payments,
        ]);
    }

    public function storePayment(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        $data = $this->validatePayment($request);
        if ($data === null) { $this->redirect('/line-payments'); }

        $db = \Database::connection();
        try {
            $db->beginTransaction();
            $payId = (new Line())->createPayment($data + ['direction' => CashMovement::OUT]);
            (new CashMovement())->create([
                'direction'      => CashMovement::OUT,
                'amount'         => $data['amount'],
                'reason'         => 'دفع خط',
                'reference_type' => 'line_payment',
                'reference_id'   => $payId,
            ]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $e;
        }
        $this->logAudit('line_payment', 'تسديد خط: ' . $data['amount'], $data + ['id' => $payId, 'direction' => 'out']);
        $this->redirect('/line-payments');
    }

    public function updatePayment(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        $id = (int)$request->input('id', 0);
        $line = new Line();
        $old = $id > 0 ? $line->findPayment($id) : null;
        $data = $this->validatePayment($request);
        if (!$old || $data === null) { $this->redirect('/line-payments'); }

        $db = \Database::connection();
        try {
            $db->beginTransaction();
            $line->updatePayment($id, $data + ['direction' => CashMovement::OUT]);
            $cash = new CashMovement();
            $updated = $cash->updateAmountByReference('line_payment', $id, $data['amount']);
            if ($updated === 0) {
                $cash->create([
                    'direction'      => CashMovement::OUT,
                    'amount'         => $data['amount'],
                    'reason'         => 'دفع خط',
                    'reference_type' => 'line_payment',
                    'reference_id'   => $id,
                ]);
            } else {
                $cash->updateByReference('line_payment', $id, $data['amount'], CashMovement::OUT, 'دفع خط');
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $e;
        }
        $this->logAudit('line_payment_update', 'تعديل تسديد خط #' . $id, ['old' => $old, 'new' => $data + ['direction' => 'out']]);
        $this->redirect('/line-payments');
    }

    public function deletePayment(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        $id = (int)$request->input('id', 0);
        if ($id > 0) {
            $line = new Line();
            $old = $line->findPayment($id);
            if ($old) {
                $db = \Database::connection();
                try {
                    $db->beginTransaction();
                    (new CashMovement())->deleteByLineId($id);
                    $line->deletePayment($id);
                    $db->commit();
                } catch (\Throwable $e) {
                    if ($db->inTransaction()) { $db->rollBack(); }
                    throw $e;
                }
                $this->logAudit('line_payment_delete', 'حذف تسديد خط #' . $id, $old);
            }
        }
        $this->redirect('/line-payments');
    }

    private function validateLine(Request $request): ?array
    {
        $name = trim((string)$request->input('name', ''));
        if ($name === '') { return null; }
        $provider = trim((string)$request->input('provider', ''));
        $note = trim((string)$request->input('note', ''));
        return [
            'name' => $name,
            'provider' => $provider !== '' ? $provider : null,
            'note' => $note !== '' ? $note : null,
        ];
    }

    private function validatePayment(Request $request): ?array
    {
        $lineId = (int)$request->input('line_id', 0);
        $amount = (int)$request->input('amount', 0);
        if ($lineId <= 0 || $amount <= 0 || !(new Line())->find($lineId)) { return null; }
        $note = trim((string)$request->input('note', ''));
        return [
            'line_id' => $lineId,
            'amount' => $amount,
            'note' => $note !== '' ? $note : null,
        ];
    }
}
