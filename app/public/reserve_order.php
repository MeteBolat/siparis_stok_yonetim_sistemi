<?php

require_once __DIR__ . '/../src/order.php'; // $pdo için

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Geri döneceğimiz liste sayfası (REFERRER yoksa yedek olarak orders.php)
$redirectBase = $_SERVER['HTTP_REFERER'] ?? 'orders.php';

if ($orderId <= 0) {
    header("Location: {$redirectBase}?msg=reserved_fail");
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

    if ($order['status'] !== 'pending') {
        throw new Exception("Sadece 'pending' siparişler rezerve edilebilir.");
    }

    $warehouseId = (int) $order['warehouse_id'];

    // 2) Sipariş kalemleri + stok kayıtları
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

    // 3) Yeterli stok var mı?
    foreach ($items as $item) {
        $available = (int) $item['quantity_on_hand'];
        $qty       = (int) $item['quantity'];

        if ($available < $qty) {
            throw new Exception("Yeterli stok yok (ürün ID: {$item['product_id']}). Mevcut: {$available}, istenen: {$qty}");
        }
    }

    // 4) Stokları güncelle: eldeki ↓, reserved ↑
    $updateInvSql = "
        UPDATE inventory
        SET quantity_on_hand = quantity_on_hand - :qty,
            reserved_quantity = reserved_quantity + :qty
        WHERE id = :inv_id
    ";
    $stmtUpdInv = $pdo->prepare($updateInvSql);

    foreach ($items as $item) {
        $stmtUpdInv->execute([
            ':qty'    => (int) $item['quantity'],
            ':inv_id' => (int) $item['inventory_id'],
        ]);
    }

    // 5) Sipariş durumunu 'reserved' yap
    $updateOrderSql = "
        UPDATE orders
        SET status = 'reserved'
        WHERE id = :id
    ";
    $stmtUpdOrder = $pdo->prepare($updateOrderSql);
    $stmtUpdOrder->execute([':id' => $orderId]);

    $pdo->commit();

    header("Location: {$redirectBase}?msg=reserved_success");
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: {$redirectBase}?msg=reserved_fail");
    exit;
}
