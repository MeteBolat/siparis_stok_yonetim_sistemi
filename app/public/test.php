<?php

require_once __DIR__ . '/../src/order.php';

// Test için:
// 1 numaralı müşteri (önce customers tablosuna bir müşteri eklemelisin)
// 1 numaralı ürün (iPhone 15)
// 3 adet sipariş

$customerId = 1;
$productId  = 1;
$quantity   = 3;

$orderId = createSimpleOrder($pdo, $customerId, $productId, $quantity);

echo "<pre>";
var_dump($orderId);
echo "</pre>";
