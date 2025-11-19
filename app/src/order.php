<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/warehouse.php';

/**
 * Basit bir sipariş oluşturur:
 * - En uygun depoyu bulur
 * - orders tablosuna kayıt ekler
 * - order_items tablosuna satır ekler
 * - (şimdilik) sadece kaydeder, stok düşmez, rezerv de yok
 */
function createSimpleOrder(PDO $pdo, int $customerId, int $productId, int $quantity): ?int
{
    // 1) Müşterinin şehrini bul
    $stmt = $pdo->prepare("SELECT city FROM customers WHERE id = :id");
    $stmt->execute([':id' => $customerId]);
    $customer = $stmt->fetch();

    if (!$customer) {
        return null; // müşteri yoksa
    }

    $customerCity = $customer['city'];

    // 2) En uygun depoyu bul
    $bestWarehouse = findBestWarehouse($pdo, $productId, $customerCity, $quantity);

    if (!$bestWarehouse) {
        // uygun depo yoksa (yeterli stok yok vs.)
        return null;
    }

    // 3) Ürünün fiyatını al
    $stmt = $pdo->prepare("SELECT price FROM products WHERE id = :pid");
    $stmt->execute([':pid' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        return null;
    }

    $unitPrice = $product['price'];
    $totalPrice = $unitPrice * $quantity;
    $shippingCost = $bestWarehouse['shipping_cost'];

    try {
        $pdo->beginTransaction();

        // 4) orders tablosuna insert
        $stmtOrder = $pdo->prepare("
            INSERT INTO orders (customer_id, warehouse_id, status, shipping_cost, total_amount)
            VALUES (:cid, :wid, 'pending', :shipping_cost, :total_amount)
        ");
        $stmtOrder->execute([
            ':cid'           => $customerId,
            ':wid'           => $bestWarehouse['warehouse_id'],
            ':shipping_cost' => $shippingCost,
            ':total_amount'  => $totalPrice + $shippingCost,
        ]);

        $orderId = (int)$pdo->lastInsertId();

        // 5) order_items tablosuna insert
        $stmtItem = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price)
            VALUES (:oid, :pid, :qty, :unit_price, :total_price)
        ");
        $stmtItem->execute([
            ':oid'         => $orderId,
            ':pid'         => $productId,
            ':qty'         => $quantity,
            ':unit_price'  => $unitPrice,
            ':total_price' => $totalPrice,
        ]);

        $pdo->commit();
        return $orderId;
    } catch (Exception $e) {
        $pdo->rollBack();
        // gerçekte loglanır
        return null;
    }
}
