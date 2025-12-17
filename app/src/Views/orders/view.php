<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sipariş #<?= htmlspecialchars((string)$order['id']) ?></title>
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
            display:inline-block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<a href="index.php?c=orders&a=index" class="back-btn">← Geri Dön</a>

<h1>Sipariş #<?= htmlspecialchars((string)$order['id']) ?></h1>

<div class="card">
    <h2>Genel Bilgiler</h2>
    <p><strong>Durum:</strong>
        <span class="status <?= htmlspecialchars((string)$order['status']) ?>">
            <?= htmlspecialchars((string)$order['status']) ?>
        </span>
    </p>
    <p><strong>Oluşturulma:</strong> <?= htmlspecialchars((string)$order['created_at']) ?></p>
    <p><strong>Kargo Ücreti:</strong> <?= number_format((float)$order['shipping_cost'], 2) ?> ₺</p>
    <p><strong>Toplam Tutar:</strong> <?= number_format((float)$order['total_amount'], 2) ?> ₺</p>
</div>

<div class="card">
    <h2>Müşteri Bilgileri</h2>
    <p><strong>İsim:</strong> <?= htmlspecialchars((string)$order['customer_name']) ?></p>
    <p><strong>E-posta:</strong> <?= htmlspecialchars((string)$order['customer_email']) ?></p>
    <p><strong>Telefon:</strong> <?= htmlspecialchars((string)$order['customer_phone']) ?></p>
    <p><strong>Şehir:</strong> <?= htmlspecialchars((string)$order['customer_city']) ?></p>
    <p><strong>Adres:</strong> <?= htmlspecialchars((string)$order['customer_address']) ?></p>
</div>

<div class="card">
    <h2>Depo Bilgileri</h2>
    <p><strong>Depo:</strong> <?= htmlspecialchars((string)$order['warehouse_name']) ?></p>
    <p><strong>Şehir:</strong> <?= htmlspecialchars((string)$order['warehouse_city']) ?></p>
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
                <td><?= htmlspecialchars((string)$it['product_name']) ?></td>
                <td><?= htmlspecialchars((string)$it['product_sku']) ?></td>
                <td><?= (int)$it['quantity'] ?></td>
                <td><?= number_format((float)$it['unit_price'], 2) ?> ₺</td>
                <td><?= number_format((float)$it['total_price'], 2) ?> ₺</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
