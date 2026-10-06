<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Models\Expense;
use Models\CashMovement;

final class ExpenseController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $expense = new Expense();
        $expenses = $expense->all();
        $total = $expense->total();

        $this->view('expenses/index', [
            'pageTitle' => 'المصروفات',
            'active'    => 'expenses',
            'expenses'  => $expenses,
            'total'     => $total,
        ]);
    }

    public function store(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $category = (string)$request->input('category', '');
        $amount   = (int)$request->input('amount', 0);
        $note     = (string)$request->input('note', '');

        if ($category === '' || $amount <= 0) {
            $this->redirect('/expenses');
        }

        $data = [
            'category' => $category,
            'amount'   => $amount,
            'note'     => $note ?: null,
        ];

        $db = \Database::connection();
        try {
            $db->beginTransaction();
            $expId = (new Expense())->create($data);
            (new CashMovement())->create([
                'direction'      => CashMovement::OUT,
                'amount'         => $amount,
                'reason'         => 'مصروف: ' . $category,
                'reference_type' => 'expense',
                'reference_id'   => $expId,
            ]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $e;
        }

        $this->logAudit('expense_create', 'مصروف ' . $category . ': ' . $amount, $data);

        $this->redirect('/expenses');
    }

    /** Edit an expense; its cash movement (OUT) follows atomically. */
    public function update(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        $expense = new Expense();
        $old = $id > 0 ? $expense->find($id) : null;
        if (!$old) {
            $this->redirect('/expenses');
        }

        $category = (string)$request->input('category', '');
        $amount   = (int)$request->input('amount', 0);
        $note     = (string)$request->input('note', '');

        if ($category === '' || $amount <= 0) {
            $this->redirect('/expenses');
        }

        $data = [
            'category' => $category,
            'amount'   => $amount,
            'note'     => $note ?: null,
        ];

        $db = \Database::connection();
        try {
            $db->beginTransaction();
            $expense->update($id, $data);

            $cash = new CashMovement();
            $updated = $cash->updateAmountByReference('expense', $id, $amount);
            if ($updated === 0) {
                $cash->create([
                    'direction'      => CashMovement::OUT,
                    'amount'         => $amount,
                    'reason'         => 'مصروف: ' . $category,
                    'reference_type' => 'expense',
                    'reference_id'   => $id,
                ]);
            } else {
                $cash->updateByReference('expense', $id, $amount, CashMovement::OUT, 'مصروف: ' . $category);
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $e;
        }

        $this->logAudit('expense_update', 'تعديل مصروف #' . $id . ': ' . $old['category'] . ' ' . $old['amount'] . ' → ' . $category . ' ' . $amount, ['old' => $old, 'new' => $data]);
        $this->redirect('/expenses');
    }

    public function delete(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        if ($id > 0) {
            $db = \Database::connection();
            try {
                $db->beginTransaction();
                (new Expense())->delete($id);
                (new CashMovement())->execute(
                    "DELETE FROM cash_movements WHERE reference_type = 'expense' AND reference_id = ?",
                    [$id]
                );
                $db->commit();
            } catch (\Throwable $e) {
                if ($db->inTransaction()) { $db->rollBack(); }
                throw $e;
            }
            $this->logAudit('expense_delete', 'حذف مصروف #' . $id);
        }

        $this->redirect('/expenses');
    }
}
