<?php

require_once __DIR__ . '/../src/db.php'; 

$successMessage = '';
$errorMessage   = '';

// Dropdownlar için listeler
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

// Form POST edildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId  = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
    $warehouseId = isset($_POST['warehouse_id']) ? (int) $_POST['warehouse_id'] : 0;
    $itemsInput  = $_POST['items'] ?? [];

    // Ürün satırlarını temizle
    $cleanItems = [];
    if (is_array($itemsInput)) {
        foreach ($itemsInput as $row) {
            $pid = isset($row['product_id']) ? (int)$row['product_id'] : 0;
            $qty = isset($row['quantity']) ? (int)$row['quantity'] : 0;
            if ($pid > 0 && $qty > 0) {
                $cleanItems[] = [
                    'product_id' => $pid,
                    'quantity'   => $qty,
                ];
            }
        }
    }

    if ($customerId <= 0 || $warehouseId <= 0) {
        $errorMessage = "Lütfen müşteri ve depo seçin.";
    } elseif (count($cleanItems) === 0) {
        $errorMessage = "En az bir ürün satırı ekleyin ve adetleri 1 veya üzeri girin.";
    } else {
        try {
            $pdo->beginTransaction();

            // Müşteri ve depo (şehirler)
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

            // Ürün bilgilerini topluca çek
            $productIds   = array_column($cleanItems, 'product_id');
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));

            $productSql = "SELECT id, name, price FROM products WHERE id IN ($placeholders)";
            $stmtProd = $pdo->prepare($productSql);
            $stmtProd->execute($productIds);
            $productRows = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

            if (count($productRows) !== count(array_unique($productIds))) {
                throw new Exception("Bazı ürünler bulunamadı.");
            }

            $productMap = [];
            foreach ($productRows as $pr) {
                $productMap[$pr['id']] = $pr;
            }

            // Depodaki stok kayıtlarını çek
            $invSql = "
                SELECT product_id, quantity_on_hand
                FROM inventory
                WHERE warehouse_id = ?
                  AND product_id IN ($placeholders)
            ";
            $params  = array_merge([$warehouseId], $productIds);
            $stmtInv = $pdo->prepare($invSql);
            $stmtInv->execute($params);
            $invRows = $stmtInv->fetchAll(PDO::FETCH_ASSOC);

            $invMap = [];
            foreach ($invRows as $ir) {
                $invMap[$ir['product_id']] = (int)$ir['quantity_on_hand'];
            }

            // Stok kontrolü (daha rezervasyonda düşüreceğiz)
            foreach ($cleanItems as $row) {
                $pid = $row['product_id'];
                $qty = $row['quantity'];

                if (!isset($invMap[$pid])) {
                    throw new Exception("Bu depoda ürün için stok kaydı yok. Ürün ID: {$pid}");
                }

                $available = $invMap[$pid];
                if ($available < $qty) {
                    throw new Exception("Yeterli stok yok. Ürün ID: {$pid}, Mevcut: {$available}, İstenen: {$qty}");
                }
            }

            // Kargo ücreti
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
            $shippingCost = $distanceRow ? (float)$distanceRow['shipping_cost'] : 0.00;

            // Fiyat hesapları
            $itemsTotal = 0.0;
            foreach ($cleanItems as &$row) {
                $pid       = $row['product_id'];
                $qty       = $row['quantity'];
                $unitPrice = (float)$productMap[$pid]['price'];

                $row['unit_price']  = $unitPrice;
                $row['total_price'] = $unitPrice * $qty;
                $itemsTotal += $row['total_price'];
            }
            unset($row);

            $totalAmount = $itemsTotal + $shippingCost;

            // Orders
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

            $orderId = (int)$pdo->lastInsertId();

            // Order_items (çoklu)
            $itemSql = "
                INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price)
                VALUES (:order_id, :product_id, :quantity, :unit_price, :total_price)
            ";
            $stmtItem = $pdo->prepare($itemSql);

            foreach ($cleanItems as $row) {
                $stmtItem->execute([
                    ':order_id'   => $orderId,
                    ':product_id' => $row['product_id'],
                    ':quantity'   => $row['quantity'],
                    ':unit_price' => $row['unit_price'],
                    ':total_price'=> $row['total_price'],
                ]);
            }

            $pdo->commit();

            $successMessage = "Sipariş oluşturuldu. Sipariş ID: {$orderId} | Kalem sayısı: "
                            . count($cleanItems)
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
    <h1 class="mb-4">Yeni Sipariş Oluştur (Çok Ürünlü)</h1>

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

        <h4 class="mt-4">Ürünler</h4>

        <table class="table align-middle" id="items-table">
            <thead>
                <tr>
                    <th style="width:60%;">Ürün</th>
                    <th style="width:20%;">Adet</th>
                    <th style="width:15%;">Birim Fiyat</th>
                    <th style="width:5%;"></th>
                </tr>
            </thead>
            <tbody id="items-body">
                <!-- JS ile satırlar eklenecek -->
            </tbody>
        </table>

        <button type="button" class="btn btn-secondary btn-sm mb-3" id="add-row">
            + Satır Ekle
        </button>

        <div class="text-end mb-3">
            <strong>Tahmini Ürün Toplamı: </strong>
            <span id="items-total">0.00</span> ₺
        </div>

        <button type="submit" class="btn btn-primary">Siparişi Oluştur</button>
        <a href="index.php" class="btn btn-secondary ms-2">Geri Dön</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// PHP'den ürünleri JS'e alalım
const products = <?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>;

let rowIndex = 0;

function createRow(index) {
    const tbody = document.getElementById('items-body');
    const tr = document.createElement('tr');

    // Ürün select
    const tdProduct = document.createElement('td');
    const select = document.createElement('select');
    select.name = `items[${index}][product_id]`;
    select.className = 'form-select';
    select.required = true;

    const optEmpty = document.createElement('option');
    optEmpty.value = '';
    optEmpty.textContent = '-- Ürün seç --';
    select.appendChild(optEmpty);

    products.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = `${p.name} - ${p.sku}`;
        opt.dataset.price = p.price;
        select.appendChild(opt);
    });

    tdProduct.appendChild(select);

    // Adet input
    const tdQty = document.createElement('td');
    const inputQty = document.createElement('input');
    inputQty.type = 'number';
    inputQty.name = `items[${index}][quantity]`;
    inputQty.min = '1';
    inputQty.value = '1';
    inputQty.required = true;
    inputQty.className = 'form-control';
    tdQty.appendChild(inputQty);

    // Birim fiyat
    const tdUnit = document.createElement('td');
    tdUnit.className = 'text-end unit-price-cell';
    tdUnit.textContent = '0.00 ₺';

    // Sil butonu
    const tdActions = document.createElement('td');
    const btnDel = document.createElement('button');
    btnDel.type = 'button';
    btnDel.className = 'btn btn-danger btn-sm';
    btnDel.textContent = 'Sil';
    btnDel.onclick = function() {
        tr.remove();
        recalcTotals();
    };
    tdActions.appendChild(btnDel);

    tr.appendChild(tdProduct);
    tr.appendChild(tdQty);
    tr.appendChild(tdUnit);
    tr.appendChild(tdActions);

    select.addEventListener('change', recalcTotals);
    inputQty.addEventListener('input', recalcTotals);

    tbody.appendChild(tr);
    recalcTotals();
}

function recalcTotals() {
    const rows = document.querySelectorAll('#items-body tr');
    let total = 0;

    rows.forEach(row => {
        const select = row.querySelector('select');
        const qtyInput = row.querySelector('input[type="number"]');
        const unitCell = row.querySelector('.unit-price-cell');

        const qty = parseInt(qtyInput.value || '0', 10);
        let unitPrice = 0;

        if (select.value) {
            const opt = select.selectedOptions[0];
            unitPrice = parseFloat(opt.dataset.price || '0');
        }

        const rowTotal = unitPrice * (isNaN(qty) ? 0 : qty);
        total += rowTotal;

        unitCell.textContent = unitPrice.toFixed(2) + ' ₺';
    });

    document.getElementById('items-total').textContent = total.toFixed(2);
}

document.getElementById('add-row').addEventListener('click', function () {
    createRow(rowIndex++);
});

// Sayfa açılınca bir satır olması için
createRow(rowIndex++);
</script>
</body>
</html>
