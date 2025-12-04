<?php

require_once __DIR__ . '/../src/order.php';

function getCustomerIdByEmail(PDO $pdo, string $email): ?int {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

function getProductIdBySku(PDO $pdo, string $sku): ?int {
    $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = :sku LIMIT 1");
    $stmt->execute([':sku' => $sku]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

echo "<pre>";

$aliId    = getCustomerIdByEmail($pdo, 'ali@example.com');
$ayseId   = getCustomerIdByEmail($pdo, 'ayse@example.com');
$mehmetId = getCustomerIdByEmail($pdo, 'mehmet@example.com');
$zeynepId = getCustomerIdByEmail($pdo, 'zeynep@example.com');

$iphoneId = getProductIdBySku($pdo, 'IP15');
$laptopId = getProductIdBySku($pdo, 'LP01');
$mouseId  = getProductIdBySku($pdo, 'MS01');
$tvId     = getProductIdBySku($pdo, 'TV4K');

var_dump([
    'aliId'    => $aliId,
    'ayseId'   => $ayseId,
    'mehmetId' => $mehmetId,
    'zeynepId' => $zeynepId,
    'iphoneId' => $iphoneId,
    'laptopId' => $laptopId,
    'mouseId'  => $mouseId,
    'tvId'     => $tvId,
]);

if ($aliId === null || $ayseId === null || $mehmetId === null || $zeynepId === null ||
    $iphoneId === null || $laptopId === null || $mouseId === null || $tvId === null) {
    echo "ERROR: Bazı müşteri veya ürünler bulunamadı.\n";
    exit;
}

// 1) Ali → 2 adet iPhone → pending
$order1 = createSimpleOrder($pdo, $aliId, $iphoneId, 2);
echo "Order1 (pending) ID: "; var_dump($order1);

// 2) Ayşe → 3 adet Laptop → reserved
$order2 = createSimpleOrder($pdo, $ayseId, $laptopId, 3);
if ($order2 !== null) {
    reserveOrderStock($pdo, $order2);
}
echo "Order2 (reserved) ID: "; var_dump($order2);

// 3) Mehmet → 5 adet Mouse → reserved + shipped
$order3 = createSimpleOrder($pdo, $mehmetId, $mouseId, 5);
if ($order3 !== null) {
    reserveOrderStock($pdo, $order3);
    shipOrder($pdo, $order3);
}
echo "Order3 (shipped) ID: "; var_dump($order3);

// 4) Zeynep → 1 adet TV → reserved + shipped
$order4 = createSimpleOrder($pdo, $zeynepId, $tvId, 1);
if ($order4 !== null) {
    reserveOrderStock($pdo, $order4);
    shipOrder($pdo, $order4);
}
echo "Order4 (shipped) ID: "; var_dump($order4);

echo "</pre>";
