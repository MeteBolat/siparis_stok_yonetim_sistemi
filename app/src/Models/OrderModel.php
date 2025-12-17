<?php
final class OrderModel
{
    public static function list(PDO $pdo): array
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
        return $pdo->query($sql)->fetchAll();
    }

    public static function findHeader(PDO $pdo, int $orderId): ?array
    {
        $sql = "
            SELECT 
                o.id, o.status, o.total_amount, o.shipping_cost, o.created_at,
                c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
                c.city AS customer_city, c.address AS customer_address,
                w.name AS warehouse_name, w.city AS warehouse_city
            FROM orders o
            JOIN customers c ON c.id = o.customer_id
            JOIN warehouses w ON w.id = o.warehouse_id
            WHERE o.id = :id
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $orderId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findItems(PDO $pdo, int $orderId): array
    {
        $sql = "
            SELECT 
                oi.product_id, oi.quantity, oi.unit_price, oi.total_price,
                p.name AS product_name, p.sku AS product_sku
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = :order_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    // ship_order.php ve cancel_order.php logic’ini “servis” gibi buraya taşıyoruz:

    public static function ship(PDO $pdo, int $orderId): bool
    {
        try {
            $pdo->beginTransaction();

            $order = self::lockOrder($pdo, $orderId);
            if (!$order || $order['status'] !== 'reserved') {
                $pdo->rollBack();
                return false;
            }

            $warehouseId = (int)$order['warehouse_id'];
            $items = self::lockOrderInventoryItems($pdo, $orderId, $warehouseId);
            if (!$items) {
                $pdo->rollBack();
                return false;
            }

            foreach ($items as $it) {
                if ((int)$it['reserved_quantity'] < (int)$it['quantity']) {
                    $pdo->rollBack();
                    return false;
                }
            }

            $stmtUpdInv = $pdo->prepare("
                UPDATE inventory
                SET reserved_quantity = reserved_quantity - :qty
                WHERE id = :inv_id
            ");
            foreach ($items as $it) {
                $stmtUpdInv->execute([
                    ':qty' => (int)$it['quantity'],
                    ':inv_id' => (int)$it['inventory_id'],
                ]);
            }

            $pdo->prepare("UPDATE orders SET status='shipped' WHERE id=:id")
                ->execute([':id' => $orderId]);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return false;
        }
    }

    public static function cancel(PDO $pdo, int $orderId): bool
    {
        try {
            $pdo->beginTransaction();

            $order = self::lockOrder($pdo, $orderId);
            if (!$order) {
                $pdo->rollBack();
                return false;
            }

            $status = $order['status'];
            if ($status === 'cancelled' || $status === 'shipped') {
                $pdo->rollBack();
                return false;
            }

            // pending: sadece cancelled
            if ($status === 'pending') {
                $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=:id")
                    ->execute([':id' => $orderId]);
                $pdo->commit();
                return true;
            }

            // reserved: stok iadesi + cancelled
            if ($status === 'reserved') {
                $warehouseId = (int)$order['warehouse_id'];
                $items = self::lockOrderInventoryItems($pdo, $orderId, $warehouseId);
                if (!$items) {
                    $pdo->rollBack();
                    return false;
                }

                foreach ($items as $it) {
                    if ((int)$it['reserved_quantity'] < (int)$it['quantity']) {
                        $pdo->rollBack();
                        return false;
                    }
                }

                $stmtUpdInv = $pdo->prepare("
                    UPDATE inventory
                    SET reserved_quantity = reserved_quantity - :qty,
                        quantity_on_hand  = quantity_on_hand  + :qty
                    WHERE id = :inv_id
                ");
                foreach ($items as $it) {
                    $stmtUpdInv->execute([
                        ':qty' => (int)$it['quantity'],
                        ':inv_id' => (int)$it['inventory_id'],
                    ]);
                }

                $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=:id")
                    ->execute([':id' => $orderId]);

                $pdo->commit();
                return true;
            }

            $pdo->rollBack();
            return false;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return false;
        }
    }

    private static function lockOrder(PDO $pdo, int $orderId): ?array
    {
        $stmt = $pdo->prepare("
            SELECT id, status, warehouse_id
            FROM orders
            WHERE id = :id
            FOR UPDATE
        ");
        $stmt->execute([':id' => $orderId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function lockOrderInventoryItems(PDO $pdo, int $orderId, int $warehouseId): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                oi.product_id,
                oi.quantity,
                inv.id AS inventory_id,
                inv.quantity_on_hand,
                inv.reserved_quantity
            FROM order_items oi
            JOIN inventory inv
              ON inv.warehouse_id = :warehouse_id
             AND inv.product_id   = oi.product_id
            WHERE oi.order_id = :order_id
            FOR UPDATE
        ");
        $stmt->execute([
            ':warehouse_id' => $warehouseId,
            ':order_id' => $orderId,
        ]);
        return $stmt->fetchAll() ?: [];
    }
}
