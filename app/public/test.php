<?php

require_once __DIR__ . '/../src/order.php';

$customerId = 1;
$productId  = 1;
$quantity   = 3;

$orderId = createSimpleOrder($pdo, $customerId, $productId, $quantity);

echo "<pre>";
var_dump($orderId);
echo "</pre>";