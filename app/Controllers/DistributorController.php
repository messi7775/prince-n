<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Models\Distributor;
use Models\AuditLog;

final class DistributorController extends Controller
{
    /** ملف الموزع + كشف الحساب (التاريخ | نوع الحركة | البيان | مدين | دائن | الرصيد) */
    public function show(Request $request): void
    {
        $this->requireAuth();

        $id = (int)$request->input('id', 0);
        $distributor = $id > 0 ? (new Distributor())->find($id) : null;
        if ($distributor === null) {
            $this->notFound();
        }

        $filters = [
            'date_from' => (string)$request->input('date_from', ''),
            'date_to'   => (string)$request->input('date_to', ''),
            'type'      => (string)$request->input('type', ''),
        ];

        $model = new Distributor();
        $statement = $model->statement($id, $filters);

        $this->view('distributors/show', [
            'pageTitle'    => 'كشف حساب: ' . $distributor['name'],
            'active'       => 'distributors',
            'distributor'  => $distributor,
            'filters'      => $filters,
            'ledger'       => $statement['ledger'],
            'opening'      => $statement['opening'],
            'salesTotal'   => $statement['sales_total'],
            'paymentsTotal'=> $statement['payments_total'],
            'finalBalance' => $statement['final_balance'],
            'lastActivity' => $model->lastActivity($id),
            'creditTotal'  => $model->creditTotal($id),
            'paidTotal'    => $model->paidTotal($id),
        ]);
    }

    public function index(Request $request): void
    {
        $this->requireAuth();

        $distributor = new Distributor();
        $q = (string)$request->input('q', '');
        $distributors = $distributor->search($q);

        $this->view('distributors/index', [
            'pageTitle'    => 'الموزعون',
            'active'       => 'distributors',
            'distributors' => $distributors,
            'q'            => $q,
        ]);
    }

    public function store(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $name  = (string)$request->input('name', '');
        $phone = (string)$request->input('phone', '');
        $note  = (string)$request->input('note', '');

        if ($name === '') {
            $this->redirect('/distributors');
        }

        if ((new Distributor())->findByName($name) !== null) {
            \Session::flash('error', 'يوجد موزع بنفس الاسم بالفعل: ' . $name);
            $this->redirect('/distributors');
        }

        $data = [
            'name'  => $name,
            'phone' => $phone ?: null,
            'note'  => $note ?: null,
        ];

        (new Distributor())->create($data);
        $this->logAudit('distributor_create', 'إضافة موزع: ' . $name, $data);

        $this->redirect('/distributors');
    }

    public function delete(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$request->input('id', 0);
        if ($id > 0) {
            $distributor = new Distributor();

            // Prevent deletion when there is any financial balance (positive debt or negative distributor credit).
            $balance = $distributor->balance($id);
            if ($balance !== 0) {
                $d = $distributor->find($id);
                $name = $d['name'] ?? '';
                $this->logAudit('distributor_delete_blocked', "منع حذف موزع {$name} — رصيد مالي: {$balance}");
                // Redirect with error message via session flash
                \Session::flash('error', "لا يمكن حذف الموزع «{$name}» لأن لديه رصيدًا ماليًا غير صفري: " . money($balance));
                $this->redirect('/distributors');
            }

            $d = $distributor->find($id);
            $distributor->delete($id);
            if ($d) {
                $this->logAudit('distributor_delete', 'حذف موزع: ' . ($d['name'] ?? ''));
            }
        }

        $this->redirect('/distributors');
    }
}
