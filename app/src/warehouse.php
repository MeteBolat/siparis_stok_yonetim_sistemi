<?php

require_once __DIR__ . '/db.php';

/**
 * En uygun depoyu bulur
 *
 * @param PDO $pdo
 * @param int $productId
 * @param string $customerCity
 * @param int $quantity
 * @return array|null
 */
function findBestWarehouse(PDO $pdo, int $productId, string $customerCity, int $quantity): ?array
{
    // SQL sorgusu
    $sql = "
        SELECT 
            w.id AS warehouse_id,
            w.name AS warehouse_name,
            w.city AS warehouse_city,
            cd.distance_km,
            cd.shipping_cost
        FROM inventory i
        JOIN warehouses w 
              ON w.id = i.warehouse_id
        JOIN city_distances cd 
              ON cd.from_city = w.city 
             AND cd.to_city   = :customer_city
        WHERE i.product_id = :product_id
          AND (i.quantity_on_hand - i.reserved_quantity) >= :quantity
        ORDER BY cd.shipping_cost ASC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':customer_city', $customerCity);
    $stmt->bindParam(':product_id', $productId);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->execute();

    $result = $stmt->fetch();

    return $result ?: null;
}
