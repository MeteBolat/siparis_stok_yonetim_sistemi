<?php
// order_create.php

require_once __DIR__ . '/../src/db.php'; // kendi yoluna göre gerekirse düzelt

$successMessage = '';
$errorMessage   = '';

// 1) Dropdownlar için listeler
try {
    $customersStmt = $pdo->query("SELECT id, name, city FROM customers ORDER BY name ASC");
    $customers = $customersStmt->fetchAll(PDO::FETCH_ASSOC);

    $warehousesStmt = $pdo->query("SELECT id, name, city FROM warehouses ORDER BY name ASC");
    $warehouses = $warehousesStmt->fetchAll(PDO::FETCH_ASSOC);

    $productsStmt = $pdo->query("SELECT id, sku, name, price FROM products ORDER BY name ASC");
    $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Liste verileri alınırken hata oluştu: " . $e->getMessage());
}

// 2) Form POST edildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId  = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
    $warehouseId = isset($_POST['warehouse_id']) ? (int) $_POST['warehouse_id'] : 0;
    $productId   = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $quantity    = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;

    if ($customerId <= 0 || $warehouseId <= 0 || $productId <= 0 || $quantity <= 0) {
        $errorMessage = "Lütfen müşteri, depo, ürün seçin ve pozitif bir adet girin.";
    } else {
        try {
            $pdo->beginTransaction();

            // 2.1) Müşteri ve depo bilgilerini çek (şehir için)
            $customerSql = "SELECT id, city FROM customers WHERE id = :id";
            $stmtCust = $pdo->prepare($customerSql);
            $stmtCust->execute([':id' => $customerId]);
            $customer = $stmtCust->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                throw new Exception("Müşteri bulunamadı.");
            }

            $warehouseSql = "SELECT id, city FROM warehouses WHERE id = :id";
            $stmtWh = $pdo->prepare($warehouseSql);
            $stmtWh->execute([':id' => $warehouseId]);
            $warehouse = $stmtWh->fetch(PDO::FETCH_ASSOC);

            if (!$warehouse) {
                throw new Exception("Depo bulunamadı.");
            }

            // 2.2) Ürün bilgisi
            $productSql = "SELECT id, name, price FROM products WHERE id = :id";
            $stmtProd = $pdo->prepare($productSql);
            $stmtProd->execute([':id' => $productId]);
            $product = $stmtProd->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception("Ürün bulunamadı.");
            }

            // 2.3) Inventory kaydı (ilgili depo + ilgili ürün) - FOR UPDATE ile kilitle
            $invSql = "
                SELECT id, quantity_on_hand, reserved_quantity
                FROM inventory
                WHERE warehouse_id = :warehouse_id
                  AND product_id   = :product_id
                FOR UPDATE
            ";
            $stmtInv = $pdo->prepare($invSql);
            $stmtInv->execute([
                ':warehouse_id' => $warehouseId,
                ':product_id'   => $productId,
            ]);
            $inventory = $stmtInv->fetch(PDO::FETCH_ASSOC);

            if (!$inventory) {
                throw new Exception("Bu depoda bu ürüne ait stok kaydı yok.");
            }

            $availableQty = (int) $inventory['quantity_on_hand'];
            if ($availableQty < $quantity) {
                throw new Exception("Yeterli stok yok. Mevcut stok: {$availableQty}");
            }

            // 2.4) Kargo ücreti (şehir mesafelerinden)
            $fromCity = $warehouse['city'];
            $toCity   = $customer['city'];

            $shipSql = "
                SELECT shipping_cost
                FROM city_distances
                WHERE from_city = :from_city
                  AND to_city   = :to_city
                LIMIT 1
            ";
            $stmtShip = $pdo->prepare($shipSql);
            $stmtShip->execute([
                ':from_city' => $fromCity,
                ':to_city'   => $toCity,
            ]);
            $distanceRow = $stmtShip->fetch(PDO::FETCH_ASSOC);

            $shippingCost = $distanceRow ? (float) $distanceRow['shipping_cost'] : 0.00;

            // 2.5) Fiyatlar
            $unitPrice   = (float) $product['price'];
            $itemsTotal  = $unitPrice * $quantity;
            $totalAmount = $itemsTotal + $shippingCost;

            // 2.6) Orders tablosuna ekle
            $orderSql = "
                INSERT INTO orders (customer_id, warehouse_id, status, shipping_cost, total_amount)
                VALUES (:customer_id, :warehouse_id, :status, :shipping_cost, :total_amount)
            ";
            $stmtOrder = $pdo->prepare($orderSql);
            $stmtOrder->execute([
                ':customer_id'  => $customerId,
                ':warehouse_id' => $warehouseId,
                ':status'       => 'pending',
                ':shipping_cost'=> $shippingCost,
                ':total_amount' => $totalAmount,
            ]);

            $orderId = (int) $pdo->lastInsertId();

            // 2.7) Order_items tablosuna kalemi ekle
            $itemSql = "
                INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price)
                VALUES (:order_id, :product_id, :quantity, :unit_price, :total_price)
            ";
            $stmtItem = $pdo->prepare($itemSql);
            $stmtItem->execute([
                ':order_id'   => $orderId,
                ':product_id' => $productId,
                ':quantity'   => $quantity,
                ':unit_price' => $unitPrice,
                ':total_price'=> $itemsTotal,
            ]);

            // 2.8) Inventory güncelle (eldeki stoktan düş)
            $updateInvSql = "
                UPDATE inventory
                SET quantity_on_hand = quantity_on_hand - :qty
                WHERE id = :inv_id
            ";
            $stmtUpdInv = $pdo->prepare($updateInvSql);
            $stmtUpdInv->execute([
                ':qty'    => $quantity,
                ':inv_id' => $inventory['id'],
            ]);

            $pdo->commit();

            $successMessage = "Sipariş oluşturuldu. Sipariş ID: {$orderId} | Ürün: " 
                            . htmlspecialchars($product['name']) 
                            . " | Toplam: " . number_format($totalAmount, 2) . " ₺";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMessage = "Sipariş oluşturulurken hata: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Sipariş Oluştur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h1 class="mb-4">Yeni Sipariş Oluştur</h1>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success">
            <?= $successMessage ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm bg-white">
        <div class="mb-3">
            <label for="customer_id" class="form-label">Müşteri</label>
            <select name="customer_id" id="customer_id" class="form-select" required>
                <option value="">-- Seç --</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>">
                        <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['city']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="warehouse_id" class="form-label">Depo</label>
            <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                <option value="">-- Seç --</option>
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?= (int)$w['id'] ?>">
                        <?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars($w['city']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="product_id" class="form-label">Ürün</label>
            <select name="product_id" id="product_id" class="form-select" required>
                <option value="">-- Seç --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int)$p['id'] ?>">
                        <?= htmlspecialchars($p['name']) ?> - <?= htmlspecialchars($p['sku']) ?>
                        (<?= number_format((float)$p['price'], 2) ?> ₺)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="quantity" class="form-label">Adet</label>
            <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
        </div>

        <button type="submit" class="btn btn-primary">Siparişi Oluştur</button>
        <a href="index.php" class="btn btn-secondary ms-2">Geri Dön</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
