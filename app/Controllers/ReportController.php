<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Services\ReportService;

final class ReportController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $report = new ReportService();

        $type = $request->input('type', 'sales');
        $data = [];
        $title = 'التقارير';

        switch ($type) {
            case 'sales':
                $data  = $report->salesReport();
                $title = 'تقرير المبيعات';
                break;
            case 'payments':
                $data  = $report->paymentsReport();
                $title = 'تقرير التحصيلات';
                break;
            case 'expenses':
                $data  = $report->expensesReport();
                $title = 'تقرير المصروفات';
                break;
            case 'cash':
                $data  = $report->cashReport();
                $title = 'تقرير الحركة النقدية';
                break;
            case 'inventory':
                $data  = $report->inventoryStatus();
                $title = 'تقرير المخزون';
                break;
            default:
                $data  = $report->salesReport();
                $title = 'تقرير المبيعات';
        }

        $this->view('reports/index', [
            'pageTitle' => 'التقارير',
            'active'    => 'reports',
            'type'      => $type,
            'title'     => $title,
            'data'      => $data,
        ]);
    }

    public function search(Request $request): void
    {
        $this->requireAuth();

        $query = (string)$request->input('q', '');
        $results = [];

        if ($query !== '') {
            $report = new ReportService();
            $results = $report->search($query);
        }

        $this->view('reports/search', [
            'pageTitle' => 'البحث',
            'active'    => 'search',
            'query'     => $query,
            'results'   => $results,
        ]);
    }
}
