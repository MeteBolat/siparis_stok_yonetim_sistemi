<?php
final class WarehouseModel
{
    public static function listWarehouses(PDO $pdo): array
    {
        return $pdo->query("SELECT id, name, city FROM warehouses ORDER BY name ASC")->fetchAll();
    }

    public static function stocks(PDO $pdo, int $warehouseId): array
    {
        $sql = "
            SELECT 
                p.id, p.sku, p.name, p.price,
                i.quantity_on_hand,
                i.reserved_quantity,
                i.quantity_on_hand AS total_quantity,
                (i.quantity_on_hand * p.price) AS stock_value
            FROM inventory i
            JOIN products p ON p.id = i.product_id
            WHERE i.warehouse_id = :wid
            ORDER BY p.name ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':wid' => $warehouseId]);
        return $stmt->fetchAll();
    }
}
