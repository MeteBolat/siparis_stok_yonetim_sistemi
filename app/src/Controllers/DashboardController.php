<?php

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::roles(['admin','sales']);
        $counts = DashboardModel::orderCounts($this->pdo);
        $totals = DashboardModel::orderTotals($this->pdo);
        $totalWarehouses = DashboardModel::warehouseCount($this->pdo);

        $topProducts = DashboardModel::topProducts($this->pdo);

        $topLabels = [];
        $topData   = [];

        foreach ($topProducts as $row) {
            $topLabels[] = (string)$row['product_name'];
            $topData[]   = (int)$row['sold_qty'];
        }

        $this->render('dashboard/index.php', [
            'title'            => 'Dashboard',
            'activeNav'        => 'dashboard',
            'counts'           => $counts,
            'totalOrders'      => $totals['total_orders'],
            'totalRevenue'     => $totals['total_revenue'],
            'totalWarehouses'  => $totalWarehouses,
            'topLabels'        => $topLabels,
            'topData'          => $topData,
        ]);
    }
}
