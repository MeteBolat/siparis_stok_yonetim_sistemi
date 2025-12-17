<?php

class OrderModel
{
    public function __construct(private PDO $pdo) {}

    public function listOrders(): array
    {
        $sql = "
            SELECT 
                o.id,
                o.status,
                o.total_amount,
                o.shipping_cost,
                o.created_at,
                c.name  AS customer_name,
                c.city  AS customer_city,
                w.name  AS warehouse_name,
                w.city  AS warehouse_city,
                SUM(oi.quantity)              AS total_quantity,
                COUNT(DISTINCT oi.product_id) AS product_count
            FROM orders o
            JOIN customers c    ON c.id = o.customer_id
            JOIN warehouses w   ON w.id = o.warehouse_id
            JOIN order_items oi ON oi.order_id = o.id
            GROUP BY 
                o.id, o.status, o.total_amount, o.shipping_cost, o.created_at,
                c.name, c.city, w.name, w.city
            ORDER BY o.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
