<?php
// cancel_order.php

require_once __DIR__ . '/../src/order.php'; // $pdo için

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Geri döneceğimiz liste sayfası
$redirectBase = $_SERVER['HTTP_REFERER'] ?? 'orders.php';

if ($orderId <= 0) {
    header("Location: {$redirectBase}?msg=cancel_fail");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1) Siparişi kilitle
    $orderSql = "
        SELECT id, status, warehouse_id
        FROM orders
        WHERE id = :id
        FOR UPDATE
    ";
    $stmtOrder = $pdo->prepare($orderSql);
    $stmtOrder->execute([':id' => $orderId]);
    $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Sipariş bulunamadı.");
    }

    $status = $order['status'];

    if ($status === 'cancelled') {
        throw new Exception("Sipariş zaten iptal edilmiş.");
    }

    if ($status === 'shipped') {
        throw new Exception("Kargoya verilen sipariş iptal edilemez.");
    }

    $warehouseId = (int) $order['warehouse_id'];

    // Eğer pending ise sadece durumu cancelled yap, stoklara dokunma
    if ($status === 'pending') {

        $updateOrderSql = "
            UPDATE orders
            SET status = 'cancelled'
            WHERE id = :id
        ";
        $stmtUpdOrder = $pdo->prepare($updateOrderSql);
        $stmtUpdOrder->execute([':id' => $orderId]);

        $pdo->commit();
        header("Location: {$redirectBase}?msg=cancel_success");
        exit;
    }

    // Eğer reserved ise stok iadesi yapmamız gerekiyor
    if ($status === 'reserved') {
        // Sipariş kalemleri + stok kayıtlarını kilitle
        $itemsSql = "
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
        ";
        $stmtItems = $pdo->prepare($itemsSql);
        $stmtItems->execute([
            ':warehouse_id' => $warehouseId,
            ':order_id'     => $orderId,
        ]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        if (!$items) {
            throw new Exception("Sipariş kalemi bulunamadı.");
        }

        // Reserved miktar yeterli mi?
        foreach ($items as $item) {
            $reserved = (int) $item['reserved_quantity'];
            $qty      = (int) $item['quantity'];

            if ($reserved < $qty) {
                throw new Exception("Yeterli reserved stok yok (ürün ID: {$item['product_id']}). Reserved: {$reserved}, gereken: {$qty}");
            }
        }

        // Stok iadesi: reserved ↓, eldeki ↑
        $updateInvSql = "
            UPDATE inventory
            SET reserved_quantity = reserved_quantity - :qty,
                quantity_on_hand  = quantity_on_hand  + :qty
            WHERE id = :inv_id
        ";
        $stmtUpdInv = $pdo->prepare($updateInvSql);

        foreach ($items as $item) {
            $stmtUpdInv->execute([
                ':qty'    => (int) $item['quantity'],
                ':inv_id' => (int) $item['inventory_id'],
            ]);
        }

        // Siparişi cancelled yap
        $updateOrderSql = "
            UPDATE orders
            SET status = 'cancelled'
            WHERE id = :id
        ";
        $stmtUpdOrder = $pdo->prepare($updateOrderSql);
        $stmtUpdOrder->execute([':id' => $orderId]);

        $pdo->commit();
        header("Location: {$redirectBase}?msg=cancel_success");
        exit;
    }

    // Buraya normalde düşmez ama tedbir:
    throw new Exception("Beklenmeyen sipariş durumu: {$status}");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Hata olursa loglamak istersen:
    // error_log('Cancel error: ' . $e->getMessage());

    header("Location: {$redirectBase}?msg=cancel_fail");
    exit;
}
