<?php

require_once __DIR__ . '/../src/order.php';

// 1) Yeni bir sipariş oluştur
$customerId = 1;
$productId  = 1;
$quantity   = 3;

echo "<pre>";

$orderId = createSimpleOrder($pdo, $customerId, $productId, $quantity);
echo "Oluşan sipariş ID: ";
var_dump($orderId);

if ($orderId !== null) {
    // 2) Stok rezervasyonu yap
    $reserved = reserveOrderStock($pdo, $orderId);
    echo "Rezervasyon sonucu: ";
    var_dump($reserved);

    // 3) Kargoya ver (alokasyon)
    if ($reserved) {
        $shipped = shipOrder($pdo, $orderId);
        echo "Kargoya verme (ship) sonucu: ";
        var_dump($shipped);
    }
}

echo "</pre>";
