<?php
require_once __DIR__ . '/../src/order.php'; // $pdo bağlantısı

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($orderId <= 0) {
    die("Geçersiz sipariş ID");
}

// Sipariş + müşteri + depo bilgileri
$orderSql = "
    SELECT 
        o.id,
        o.status,
        o.total_amount,
        o.shipping_cost,
        o.created_at,
        
        c.name AS customer_name,
        c.email AS customer_email,
        c.phone AS customer_phone,
        c.city AS customer_city,
        c.address AS customer_address,
        
        w.name AS warehouse_name,
        w.city AS warehouse_city
        
    FROM orders o
    JOIN customers c ON c.id = o.customer_id
    JOIN warehouses w ON w.id = o.warehouse_id
    WHERE o.id = :id
    LIMIT 1
";
$stmt = $pdo->prepare($orderSql);
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Sipariş bulunamadı");
}

// Sipariş kalemleri
$itemsSql = "
    SELECT 
        oi.product_id,
        oi.quantity,
        oi.unit_price,
        oi.total_price,
        p.name AS product_name,
        p.sku AS product_sku
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = :order_id
";
$stmtItems = $pdo->prepare($itemsSql);
$stmtItems->execute([':order_id' => $orderId]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sipariş #<?= $order['id'] ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: #f8f8f8;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px;
        }
        th, td {
            padding: 10px; 
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th { background: #fafafa; }
        .status {
            display: inline-block;
            padding: 5px 10px;
            color: white;
            border-radius: 4px;
            font-size: 13px;
        }
        .pending  { background: #f0ad4e; }
        .reserved { background: #5bc0de; }
        .shipped  { background: #5cb85c; }
        .cancelled{ background: #d9534f; }

        .back-btn {
            background: #777;
            color: white;
            padding: 8px 14px;
            border-radius: 4px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<a href="orders.php" class="back-btn">← Geri Dön</a>

<h1>Sipariş #<?= htmlspecialchars($order['id']) ?></h1>

<div class="card">
    <h2>Genel Bilgiler</h2>
    <p><strong>Durum:</strong> 
        <span class="status <?= $order['status'] ?>">
            <?= htmlspecialchars($order['status']) ?>
        </span>
    </p>
    <p><strong>Oluşturulma:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
    <p><strong>Kargo Ücreti:</strong> <?= number_format($order['shipping_cost'], 2) ?> ₺</p>
    <p><strong>Toplam Tutar:</strong> <?= number_format($order['total_amount'], 2) ?> ₺</p>
</div>

<div class="card">
    <h2>Müşteri Bilgileri</h2>
    <p><strong>İsim:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
    <p><strong>E-posta:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
    <p><strong>Telefon:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
    <p><strong>Şehir:</strong> <?= htmlspecialchars($order['customer_city']) ?></p>
    <p><strong>Adres:</strong> <?= htmlspecialchars($order['customer_address']) ?></p>
</div>

<div class="card">
    <h2>Depo Bilgileri</h2>
    <p><strong>Depo:</strong> <?= htmlspecialchars($order['warehouse_name']) ?></p>
    <p><strong>Şehir:</strong> <?= htmlspecialchars($order['warehouse_city']) ?></p>
</div>

<div class="card">
    <h2>Sipariş Kalemleri</h2>
    <table>
        <thead>
            <tr>
                <th>Ürün</th>
                <th>SKU</th>
                <th>Adet</th>
                <th>Birim Fiyat</th>
                <th>Toplam</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= htmlspecialchars($it['product_name']) ?></td>
                <td><?= htmlspecialchars($it['product_sku']) ?></td>
                <td><?= $it['quantity'] ?></td>
                <td><?= number_format($it['unit_price'], 2) ?> ₺</td>
                <td><?= number_format($it['total_price'], 2) ?> ₺</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
