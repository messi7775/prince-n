<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Services\ReportService;
use Models\Distributor;
use Models\Package;
use Models\Expense;

final class ReportController extends Controller
{
    private const TYPES = ['sales', 'payments', 'expenses', 'cash', 'inventory', 'distributors', 'profit'];

    private const TITLES = [
        'sales'        => 'تقرير المبيعات',
        'payments'     => 'تقرير التحصيلات',
        'expenses'     => 'تقرير المصروفات',
        'cash'         => 'تقرير الصندوق',
        'inventory'    => 'تقرير المخزون',
        'distributors' => 'تقرير الموزعين',
        'profit'       => 'تقرير الأرباح',
    ];

    public function index(Request $request): void
    {
        $this->requireAuth();

        $type = (string)$request->input('type', 'sales');
        if (!in_array($type, self::TYPES, true)) {
            $type = 'sales';
        }

        // Period shortcuts (day / week / month) expand to a date range.
        $dateFrom = trim((string)$request->input('date_from', ''));
        $dateTo   = trim((string)$request->input('date_to', ''));
        $period   = (string)$request->input('period', 'custom');
        [$dateFrom, $dateTo] = $this->resolvePeriod($period, $dateFrom, $dateTo);

        $filters = [
            'q'              => (string)$request->input('q', ''),
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
            'period'         => $period,
            'distributor_id' => (int)$request->input('distributor_id', 0),
            'package_id'     => (int)$request->input('package_id', 0),
            'payment_type'   => (string)$request->input('payment_type', ''),
            'category'       => (string)$request->input('category', ''),
            'direction'      => (string)$request->input('direction', ''),
        ];

        $report = new ReportService();
        $data   = [];
        switch ($type) {
            case 'sales':
                $data = $report->salesReport($filters);
                break;
            case 'payments':
                $data = $report->paymentsReport($filters);
                break;
            case 'expenses':
                $data = $report->expensesReport($filters);
                break;
            case 'cash':
                $data = $report->cashReport($filters);
                break;
            case 'inventory':
                $data = $report->inventoryReport();
                break;
            case 'distributors':
                $data = $report->distributorsReport();
                break;
            case 'profit':
                $data = $report->profitReport($filters);
                break;
        }

        $this->view('reports/index', [
            'pageTitle'    => 'التقارير',
            'active'       => 'reports',
            'type'         => $type,
            'title'        => self::TITLES[$type],
            'types'        => self::TYPES,
            'titles'       => self::TITLES,
            'filters'      => $filters,
            'data'         => $data,
            'distributors' => (new Distributor())->all(),
            'packages'     => (new Package())->active(),
            'categories'   => (new Expense())->categories(),
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

    /** Expand a period shortcut into a concrete date range. */
    private function resolvePeriod(string $period, string $dateFrom, string $dateTo): array
    {
        if ($period === 'day') {
            return [date('Y-m-d'), date('Y-m-d')];
        }
        if ($period === 'week') {
            return [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')];
        }
        if ($period === 'month') {
            return [date('Y-m-01'), date('Y-m-d')];
        }
        // custom: use the provided dates (already validated downstream).
        return [$dateFrom, $dateTo];
    }
}
