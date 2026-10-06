<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Models\Payment;
use Models\Distributor;
use Models\CashMovement;

final class PaymentController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $filters = [
            'q'              => (string)$request->input('q', ''),
            'distributor_id' => (int)$request->input('distributor_id', 0),
            'date_from'      => (string)$request->input('date_from', ''),
            'date_to'        => (string)$request->input('date_to', ''),
        ];

        $distributors = (new Distributor())->all();
        $payments = (new Payment())->search($filters);

        $this->view('payments/index', [
            'pageTitle'    => 'التحصيلات',
            'active'       => 'payments',
            'payments'     => $payments,
            'total'        => array_sum(array_map(static fn ($p) => (int)$p['amount'], $payments)),
            'filters'      => $filters,
            'distributors' => $distributors,
        ]);
    }

    public function store(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $data = $this->validateInput($request);
        if ($data === null) {
            $this->redirect('/payments');
        }

        $db = \Database::connection();

        try {
            $db->beginTransaction();

            $payId = (new Payment())->create($data);

            (new CashMovement())->create([
                'direction'      => CashMovement::IN,
                'amount'         => $data['amount'],
                'reason'         => 'تحصيل من موزع',
                'reference_type' => 'payment',
                'reference_id'   => $payId,
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        $this->logAudit('payment_create', 'تحصيل ' . $data['amount'], $data);
        $this->redirect('/payments');
    }

    /**
     * Edit a collection using the same pattern as sales: load the old row,
     * validate the new values, then update the payment and its linked cash
     * movement atomically. Distributor balances are derived from payments,
     * so changing the payment automatically changes the distributor balance.
     */
    public function update(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        $old = $id > 0 ? (new Payment())->find($id) : null;
        if (!$old) {
            $this->redirect('/payments');
        }

        $new = $this->validateInput($request);
        if ($new === null) {
            $this->redirect('/payments');
        }

        $db = \Database::connection();

        try {
            $db->beginTransaction();

            // Keep the payment and its cash movement in sync.
            (new Payment())->update($id, $new);

            (new CashMovement())->updateAmountByReference('payment', $id, $new['amount']);

            // Repair a legacy/missing cash movement if the payment exists but
            // its linked cash entry was deleted previously.
            $cash = (new CashMovement())->fetchByReference('payment', $id);
            if ($cash === null) {
                (new CashMovement())->create([
                    'direction'      => CashMovement::IN,
                    'amount'         => $new['amount'],
                    'reason'         => 'تحصيل من موزع',
                    'reference_type' => 'payment',
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

        $this->logAudit(
            'payment_update',
            'تعديل تحصيل #' . $id . ': من ' . $old['amount'] . ' إلى ' . $new['amount'],
            ['old' => $old, 'new' => $new]
        );

        $this->redirect('/payments');
    }

    public function delete(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        if ($id > 0) {
            $payment = (new Payment())->find($id);
            if (!$payment) {
                $this->redirect('/payments');
            }

            $db = \Database::connection();

            try {
                $db->beginTransaction();

                // Remove the linked cash movement first, then the payment.
                // Both operations are atomic so a failed delete cannot leave
                // the cash balance inconsistent with distributor collections.
                (new CashMovement())->deleteByReference('payment', $id);
                (new Payment())->delete($id);

                $db->commit();
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }

            $this->logAudit(
                'payment_delete',
                'حذف تحصيل #' . $id,
                ['deleted' => $payment]
            );
        }

        $this->redirect('/payments');
    }

    private function validateInput(Request $request): ?array
    {
        $distributorId = (int)$request->input('distributor_id', 0);
        $amount        = (int)$request->input('amount', 0);
        $note          = trim((string)$request->input('note', ''));

        if ($distributorId <= 0 || $amount <= 0) {
            return null;
        }

        if ((new Distributor())->find($distributorId) === null) {
            return null;
        }

        return [
            'distributor_id' => $distributorId,
            'amount'         => $amount,
            'note'           => $note !== '' ? $note : null,
        ];
    }
}
