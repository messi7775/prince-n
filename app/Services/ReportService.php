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

    public function dashboardKpis(): array
    {
        return [
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

    public function salesReport(): array
    {
        return $this->sales->all();
    }

    public function paymentsReport(): array
    {
        return $this->payments->all();
    }

    public function expensesReport(): array
    {
        return $this->expenses->all();
    }

    public function cashReport(): array
    {
        return $this->cash->all(500);
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
