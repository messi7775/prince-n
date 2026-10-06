<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Models\Distributor;
use Models\AuditLog;

final class DistributorController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $distributor = new Distributor();
        $distributors = $distributor->all();

        $this->view('distributors/index', [
            'pageTitle'    => 'الموزعون',
            'active'       => 'distributors',
            'distributors' => $distributors,
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
