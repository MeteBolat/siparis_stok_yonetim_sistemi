<?php
final class DashboardController extends Controller
{
    public function index(): void
    {
        // TODO: requirelogin()
        // TODO: requireRole('admin')
        
        // 1) Sipariş sayıları (durum bazlı)
        $stmt = $this->pdo->query("
            SELECT status, COUNT(*) AS cnt
            FROM orders
            GROUP BY status
        ");
        $rows = $stmt->fetchAll();

        $counts = [
            'pending' => 0,
            'reserved' => 0,
            'shipped' => 0,
            'cancelled' => 0,
        ];
        foreach ($rows as $r) {
            $status = (string)$r['status'];
            if (isset($counts[$status])) {
                $counts[$status] = (int)$r['cnt'];
            }
        }

        // 2) Toplam sipariş ve toplam ciro
        $totals = $this->pdo->query("
            SELECT 
                COUNT(*) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS total_revenue
            FROM orders
        ")->fetch();

        // 3) Depo sayısı
        $wh = $this->pdo->query("SELECT COUNT(*) AS total_warehouses FROM warehouses")->fetch();

        // 4) En çok satılan 5 ürün (sadece shipped siparişlerden)
        $topProducts = $this->pdo->query("
            SELECT 
                p.name AS product_name,
                SUM(oi.quantity) AS sold_qty
            FROM order_items oi
            JOIN orders o   ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            WHERE o.status = 'shipped'
            GROUP BY p.id, p.name
            ORDER BY sold_qty DESC
            LIMIT 5
        ")->fetchAll();

        $topLabels = [];
        $topData = [];

        foreach ($topProducts as $row) {
            $topLabels[] = (string)$row['product_name'];
            $topData[]   = (int)$row['sold_qty'];
        }

        // 5) View'a gönder
        $this->render('dashboard/index.php', [
            'title' => 'Dashboard',
            'activeNav' => 'dashboard',
            'counts' => $counts,
            'totalOrders' => (int)($totals['total_orders'] ?? 0),
            'totalRevenue' => (float)($totals['total_revenue'] ?? 0),
            'totalWarehouses' => (int)($wh['total_warehouses'] ?? 0),
            'topLabels' => $topLabels,
            'topData' => $topData,
        ]);

}
}
