<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Services\ReportService;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $report = new ReportService();
        $kpis   = $report->dashboardKpis();
        $operations = $report->recentOperations();
        $inventory  = $report->inventoryStatus();
        $lowStock   = $report->lowStockAlerts();

        $this->view('dashboard/index', [
            'pageTitle'  => 'لوحة التحكم',
            'active'     => 'dashboard',
            'kpis'       => $kpis,
            'operations' => $operations,
            'inventory'  => $inventory,
            'lowStock'   => $lowStock,
        ]);
    }
}
