<?php

require_once __DIR__ . '/../src/order.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    header('Location: orders.php?msg=reserved_fail');
    exit;
}

$success = reserveOrderStock($pdo, $orderId);

if ($success) {
    header('Location: orders.php?msg=reserved_success');
} else {
    header('Location: orders.php?msg=reserved_fail');
}
exit;
