<?php

final class DashboardModel
{
    public static function orderCounts(PDO $pdo): array
    {
        $rows = $pdo->query("
            SELECT status, COUNT(*) AS cnt
            FROM orders
            GROUP BY status
        ")->fetchAll();

        $counts = [
            'pending'   => 0,
            'reserved'  => 0,
            'shipped'   => 0,
            'cancelled' => 0,
        ];

        foreach ($rows as $r) {
            $status = (string)$r['status'];
            if (isset($counts[$status])) {
                $counts[$status] = (int)$r['cnt'];
            }
        }

        return $counts;
    }

    public static function orderTotals(PDO $pdo): array
    {
        $row = $pdo->query("
            SELECT 
                COUNT(*) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS total_revenue
            FROM orders
        ")->fetch();

        return [
            'total_orders'  => (int)$row['total_orders'],
            'total_revenue' => (float)$row['total_revenue'],
        ];
    }

    public static function warehouseCount(PDO $pdo): int
    {
        $row = $pdo->query("
            SELECT COUNT(*) AS total
            FROM warehouses
        ")->fetch();

        return (int)$row['total'];
    }

    public static function topProducts(PDO $pdo, int $limit = 5): array
    {
        return $pdo->query("
            SELECT 
                p.name AS product_name,
                SUM(oi.quantity) AS sold_qty
            FROM order_items oi
            JOIN orders o   ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            WHERE o.status = 'shipped'
            GROUP BY p.id, p.name
            ORDER BY sold_qty DESC
            LIMIT {$limit}
        ")->fetchAll();
    }
}
