<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Models\CashMovement;
use Models\OwnerWithdrawal;

final class CashController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $cash = new CashMovement();

        $filters = [
            'q'         => (string)$request->input('q', ''),
            'direction' => (string)$request->input('direction', ''),
            'date_from' => (string)$request->input('date_from', ''),
            'date_to'   => (string)$request->input('date_to', ''),
        ];

        $result    = $cash->search($filters);
        $movements = $result['rows'];
        $balance   = $cash->balance();
        $totalIn   = $result['total_in'];
        $totalOut  = $result['total_out'];

        $this->view('cash/index', [
            'pageTitle'     => 'الصندوق',
            'active'        => 'cash',
            'movements'     => $movements,
            'balance'       => $balance,
            'totalIn'       => $totalIn,
            'totalOut'      => $totalOut,
            'filters'       => $filters,
            'filteredBalance' => $result['balance'],
            'filteredCount'   => $result['count'],
        ]);
    }

    public function withdrawals(Request $request): void
    {
        $this->requireAuth();

        $withdrawal = new OwnerWithdrawal();
        $withdrawals = $withdrawal->all();
        $total = $withdrawal->total();

        $this->view('cash/withdrawals', [
            'pageTitle'   => 'سحوبات المالك',
            'active'      => 'owner-withdrawals',
            'withdrawals' => $withdrawals,
            'total'       => $total,
        ]);
    }

    public function storeWithdrawal(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $amount = (int)$request->input('amount', 0);
        $note   = (string)$request->input('note', '');

        if ($amount <= 0) {
            $this->redirect('/owner-withdrawals');
        }

        $data = [
            'amount' => $amount,
            'note'   => $note ?: null,
        ];

        $db = \Database::connection();
        try {
            $db->beginTransaction();
            $wId = (new OwnerWithdrawal())->create($data);
            (new CashMovement())->create([
                'direction'      => CashMovement::OUT,
                'amount'         => $amount,
                'reason'         => 'سحب المالك',
                'reference_type' => 'owner_withdrawal',
                'reference_id'   => $wId,
            ]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $e;
        }

        $this->logAudit('owner_withdrawal', 'سحب المالك: ' . $amount, $data);

        $this->redirect('/owner-withdrawals');
    }

    /** Edit an owner withdrawal; its cash movement (OUT) follows atomically. */
    public function updateWithdrawal(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        $withdrawal = new OwnerWithdrawal();
        $old = $id > 0 ? $withdrawal->find($id) : null;
        if (!$old) {
            $this->redirect('/owner-withdrawals');
        }

        $amount = (int)$request->input('amount', 0);
        $note   = (string)$request->input('note', '');

        if ($amount <= 0) {
            $this->redirect('/owner-withdrawals');
        }

        $data = ['amount' => $amount, 'note' => $note ?: null];

        $db = \Database::connection();
        try {
            $db->beginTransaction();
            $withdrawal->update($id, $data);

            $cash = new CashMovement();
            $updated = $cash->updateAmountByReference('owner_withdrawal', $id, $amount);
            if ($updated === 0) {
                $cash->create([
                    'direction'      => CashMovement::OUT,
                    'amount'         => $amount,
                    'reason'         => 'سحب المالك',
                    'reference_type' => 'owner_withdrawal',
                    'reference_id'   => $id,
                ]);
            } else {
                $cash->updateByReference('owner_withdrawal', $id, $amount, CashMovement::OUT, 'سحب المالك');
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $e;
        }

        $this->logAudit('owner_withdrawal_update', 'تعديل سحب #' . $id . ': من ' . $old['amount'] . ' إلى ' . $amount, ['old' => $old, 'new' => $data]);
        $this->redirect('/owner-withdrawals');
    }

    public function deleteWithdrawal(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        if ($id > 0) {
            $db = \Database::connection();
            try {
                $db->beginTransaction();
                (new OwnerWithdrawal())->delete($id);
                (new CashMovement())->deleteByReference('owner_withdrawal', $id);
                $db->commit();
            } catch (\Throwable $e) {
                if ($db->inTransaction()) { $db->rollBack(); }
                throw $e;
            }
            $this->logAudit('owner_withdrawal_delete', 'حذف سحب #' . $id);
        }

        $this->redirect('/owner-withdrawals');
    }
}
