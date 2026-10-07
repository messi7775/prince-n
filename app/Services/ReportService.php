<?php
declare(strict_types=1);

namespace Services;

use Models\Sale;
use Models\Payment;
use Models\Expense;
use Models\Distributor;
use Models\Inventory;
use Models\Package;
use Models\CashMovement;
use Models\Line;
use Models\OwnerWithdrawal;

final class ReportService
{
    private Sale $sales;
    private Payment $payments;
    private Expense $expenses;
    private Distributor $distributors;
    private Inventory $inventory;
    private Package $packages;
    private CashMovement $cash;
    private Line $lines;
    private OwnerWithdrawal $withdrawals;

    public function __construct()
    {
        $this->sales        = new Sale();
        $this->payments     = new Payment();
        $this->expenses     = new Expense();
        $this->distributors = new Distributor();
        $this->inventory    = new Inventory();
        $this->packages     = new Package();
        $this->cash         = new CashMovement();
        $this->lines        = new Line();
        $this->withdrawals  = new OwnerWithdrawal();
    }

    // ------------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------------

    public function dashboardKpis(): array
    {
        $kpis = [
            'sales_today'        => $this->sales->totalToday(),
            'sales_month'        => $this->sales->totalThisMonth(),
            'collections_today'  => $this->payments->totalToday(),
            'distributor_debt'   => $this->distributors->totalDebt(),
            'cash_balance'       => $this->cash->balance(),
            'inventory_value'    => $this->packages->inventoryValue(),
            'distributors_count' => $this->distributors->count(),
            'packages_count'     => $this->packages->count(),
            'packages_active'    => $this->packages->countActive(),
            'owner_withdrawals'  => $this->withdrawals->total(),
            'expenses_total'     => $this->expenses->total(),
            'cash_payments_out'  => $this->cash->totalOut(),
            'cash_receipts_in'   => $this->cash->totalIn(),
            'lines_count'        => $this->lines->count(),
            'total_bundles_sold' => $this->sales->totalBundles(),
        ];

        $kpis['expenses_today']   = $this->expenses->totalToday();
        $kpis['cash_net_today']   = $this->cash->balance();
        $kpis['bundles_remaining'] = (new Inventory())->remainingTotal();

        return $kpis;
    }

    /** Sales / collections / cash net-flow per day for the last N days. */
    public function dailyActivity(int $days = 7): array
    {
        $salesByDay = [];
        foreach ($this->sales->dailyTotals($days) as $row) {
            $salesByDay[$row['day']] = $row;
        }
        $paymentsByDay = [];
        foreach ($this->payments->dailyTotals($days) as $row) {
            $paymentsByDay[$row['day']] = $row;
        }
        $cashByDay = [];
        foreach ($this->cash->dailyTotals($days) as $row) {
            $cashByDay[$row['day']] = $row;
        }

        $out = [];
        $period = new \DatePeriod(
            new \DateTime(date('Y-m-d', strtotime('-' . ($days - 1) . ' days'))),
            new \DateInterval('P1D'),
            new \DateTime(date('Y-m-d') . ' +1 day')
        );
        foreach ($period as $dt) {
            $key = $dt->format('Y-m-d');
            $out[] = [
                'day'        => $key,
                'sales'      => (int)($salesByDay[$key]['total'] ?? 0),
                'payments'   => (int)($paymentsByDay[$key]['total'] ?? 0),
                'cash_in'    => (int)($cashByDay[$key]['cash_in'] ?? 0),
                'cash_out'   => (int)($cashByDay[$key]['cash_out'] ?? 0),
            ];
        }

        return $out;
    }

    public function topPackages(int $limit = 5): array
    {
        return $this->sales->topPackages($limit);
    }

    public function topDistributors(int $limit = 5): array
    {
        return array_slice($this->distributors->reportRows(), 0, $limit);
    }

    public function recentOperations(int $limit = 10): array
    {
        return $this->sales->recent($limit);
    }

    public function inventoryStatus(): array
    {
        return $this->inventory->stockByPackage();
    }

    public function lowStockAlerts(): array
    {
        return $this->inventory->lowStock();
    }

    // ------------------------------------------------------------------
    // Reports — every report reads from actual operations, never stored sums.
    // ------------------------------------------------------------------

    /** Sales report with period / distributor / package / sale-type filters. */
    public function salesReport(array $filters = []): array
    {
        $result = $this->sales->report($filters);
        $result['cost'] = $this->sales->costBasis($filters);
        return $result;
    }

    /** Collections report with distributor / period filters. */
    public function paymentsReport(array $filters = []): array
    {
        $rows  = $this->payments->search($filters);
        $total = array_sum(array_map(static fn ($r) => (int)$r['amount'], $rows));
        return ['rows' => $rows, 'total' => $total, 'count' => count($rows)];
    }

    /** Expenses report with category / period filters + per-category breakdown. */
    public function expensesReport(array $filters = []): array
    {
        $rows  = $this->expenses->search($filters);
        $total = array_sum(array_map(static fn ($r) => (int)$r['amount'], $rows));

        $byCategory = [];
        foreach ($rows as $r) {
            $cat = $r['category'] ?? 'غير مصنف';
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + (int)$r['amount'];
        }
        arsort($byCategory);

        return [
            'rows'       => $rows,
            'total'      => $total,
            'count'      => count($rows),
            'byCategory' => $byCategory,
        ];
    }

    /** Cash report: in / out / balance over the filtered set + details. */
    public function cashReport(array $filters = []): array
    {
        return $this->cash->search($filters);
    }

    /** Inventory report: in / sold / remaining / value per package. */
    public function inventoryReport(): array
    {
        $rows = $this->inventory->stockByPackage();

        $totals = ['stock_in' => 0, 'sold' => 0, 'bundles' => 0, 'value' => 0];
        foreach ($rows as $r) {
            $totals['stock_in'] += (int)$r['stock_in'];
            $totals['sold']     += (int)$r['sold'];
            $totals['bundles']  += (int)$r['bundles'];
            $totals['value']    += (int)$r['value'];
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    /** Distributors report: sales / collections / debt per distributor. */
    public function distributorsReport(): array
    {
        $rows = $this->distributors->reportRows();

        $totals = ['total_sales' => 0, 'credit_sales' => 0, 'paid_total' => 0, 'debt' => 0];
        foreach ($rows as $r) {
            $totals['total_sales']  += (int)$r['total_sales'];
            $totals['credit_sales'] += (int)$r['credit_sales'];
            $totals['paid_total']   += (int)$r['paid_total'];
            $totals['debt']         += (int)$r['debt'];
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    /**
     * Honest profit report.
     * Real profit = revenue − real purchase cost − expenses.
     * Purchase cost comes only from recorded batch costs; when any sold bundle
     * has no recorded cost, profit is reported as unavailable (never estimated).
     */
    public function profitReport(array $filters = []): array
    {
        $sales = $this->sales->report($filters);
        $cost  = $this->sales->costBasis($filters);

        $unpriced = (int)($cost['unpriced_bundles'] ?? 0);
        $available = $unpriced === 0 && (int)($cost['cost'] ?? 0) > 0;

        $expensesTotal = array_sum(
            array_map(static fn ($r) => (int)$r['amount'], $this->expenses->search($filters))
        );

        return [
            'revenue'    => (int)($sales['totals']['total'] ?? 0),
            'cost'       => (int)($cost['cost'] ?? 0),
            'expenses'   => $expensesTotal,
            'profit'     => $available
                ? ((int)($sales['totals']['total'] ?? 0) - (int)($cost['cost'] ?? 0) - $expensesTotal)
                : null,
            'available'  => $available,
            'unpriced'   => $unpriced,
            'salesCount' => (int)($sales['totals']['count'] ?? 0),
        ];
    }

    public function search(string $query): array
    {
        $q = '%' . $query . '%';
        $results = [];

        // Search distributors
        $distributors = $this->distributors->all();
        foreach ($distributors as $d) {
            if (mb_stripos($d['name'] ?? '', $query) !== false
                || mb_stripos($d['phone'] ?? '', $query) !== false) {
                $results['distributors'][] = $d;
            }
        }

        // Search sales by distributor name or note
        $sales = $this->sales->all();
        foreach ($sales as $s) {
            if (mb_stripos($s['distributor_name'] ?? '', $query) !== false
                || mb_stripos($s['package_name'] ?? '', $query) !== false
                || mb_stripos($s['note'] ?? '', $query) !== false) {
                $results['sales'][] = $s;
            }
        }

        return $results;
    }
}
