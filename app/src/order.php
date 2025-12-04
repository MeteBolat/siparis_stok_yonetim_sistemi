<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/warehouse.php'; //dosya bağlantıları

/*
  Basit sipariş oluşturma:
  - En uygun depoyu bulur
  - orders + order_items kayıtlarını ekler
  (Stok düşmez, rezerv de yapmaz)
 */

function createSimpleOrder(PDO $pdo, int $customerId, int $productId, int $quantity): ?int
{
    // 1) Müşterinin şehrini bul
    $stmt = $pdo->prepare("SELECT city FROM customers WHERE id = :id");
    $stmt->execute([':id' => $customerId]);
    $customer = $stmt->fetch();

    if (!$customer) {
        echo "DEBUG: Müşteri bulunamadı (customer_id = {$customerId})<br>";
        return null;
    }

    $customerCity = $customer['city'];
    echo "DEBUG: Müşteri bulundu, şehir = {$customerCity}<br>";

    // 2) En uygun depoyu bul
    $bestWarehouse = findBestWarehouse($pdo, $productId, $customerCity, $quantity);

    if (!$bestWarehouse) {
        echo "DEBUG: Uygun depo bulunamadı (product_id = {$productId}, city = {$customerCity}, quantity = {$quantity})<br>";
        return null;
    }

    echo "DEBUG: Depo bulundu, warehouse_id = {$bestWarehouse['warehouse_id']}<br>";

    // 3) Ürünün fiyatını al
    $stmt = $pdo->prepare("SELECT price FROM products WHERE id = :pid");
    $stmt->execute([':pid' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        echo "DEBUG: Ürün bulunamadı (product_id = {$productId})<br>";
        return null;
    }

    echo "DEBUG: Ürün bulundu, price = {$product['price']}<br>";

    // Fiyat hesapları
    $unitPrice    = (float)$product['price'];
    $totalPrice   = $unitPrice * $quantity;
    $shippingCost = (float)$bestWarehouse['shipping_cost'];

    try {
        $pdo->beginTransaction();

        // orders kaydı
        $stmtOrder = $pdo->prepare("
            INSERT INTO orders (customer_id, warehouse_id, status, shipping_cost, total_amount)
            VALUES (:cid, :wid, 'pending', :shipping_cost, :total_amount)
        ");
        $stmtOrder->execute([
            ':cid'           => $customerId,
            ':wid'           => $bestWarehouse['warehouse_id'],
            ':shipping_cost' => $shippingCost,
            ':total_amount'  => $totalPrice + $shippingCost,
        ]);

        $orderId = (int)$pdo->lastInsertId();

        // order_items kaydı
        $stmtItem = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price)
            VALUES (:oid, :pid, :qty, :unit_price, :total_price)
        ");
        $stmtItem->execute([
            ':oid'         => $orderId,
            ':pid'         => $productId,
            ':qty'         => $quantity,
            ':unit_price'  => $unitPrice,
            ':total_price' => $totalPrice,
        ]);

        $pdo->commit();
        echo "DEBUG: Sipariş başarıyla oluşturuldu, ID = {$orderId}<br>";
        return $orderId;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "DEBUG: Exception oluştu: " . $e->getMessage() . "<br>";
        return null;
    }
}



/*
  Sipariş için stok rezervasyonu yapar:
  - inventory.reserved_quantity artırır
  - orders.status = 'reserved' yapar
 */
function reserveOrderStock(PDO $pdo, int $orderId): bool
{
    try {
        $pdo->beginTransaction();

        //Siparişi ve depoyu kilitle
        $sqlOrder = "
            SELECT id, warehouse_id, status
            FROM orders
            WHERE id = :oid
            FOR UPDATE
        ";
        $stmtOrder = $pdo->prepare($sqlOrder);
        $stmtOrder->execute([':oid' => $orderId]);
        $order = $stmtOrder->fetch();

        if (!$order) {
            $pdo->rollBack();
            return false;
        }

        // Sadece pending siparişler için rezervasyon yap
        if ($order['status'] !== 'pending') {
            $pdo->rollBack();
            return false;
        }

        $warehouseId = (int)$order['warehouse_id'];

        //Sipariş ürünlerini çek
        $sqlItems = "
            SELECT product_id, quantity
            FROM order_items
            WHERE order_id = :oid
        ";
        $stmtItems = $pdo->prepare($sqlItems);
        $stmtItems->execute([':oid' => $orderId]);
        $items = $stmtItems->fetchAll();

        if (!$items) {
            $pdo->rollBack();
            return false;
        }

        //Her ürün için ilgili inventory satırını kilitle ve reserved artır
        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $qty       = (int)$item['quantity'];

            // İlgili stok satırını FOR UPDATE ile kilitle
            $sqlInv = "
                SELECT id, quantity_on_hand, reserved_quantity
                FROM inventory
                WHERE warehouse_id = :wid
                  AND product_id   = :pid
                FOR UPDATE
            ";
            $stmtInv = $pdo->prepare($sqlInv);
            $stmtInv->execute([
                ':wid' => $warehouseId,
                ':pid' => $productId,
            ]);
            $inv = $stmtInv->fetch();

            if (!$inv) {
                // Bu depoda bu ürün yok -> rezervasyon yapamayız
                $pdo->rollBack();
                return false;
            }

            $available = (int)$inv['quantity_on_hand'] - (int)$inv['reserved_quantity'];

            if ($available < $qty) {
                // Yeterli serbest stok yoksa
                $pdo->rollBack();
                return false;
            }

            // reserved_quantity artır
            $sqlUpdateInv = "
                UPDATE inventory
                SET reserved_quantity = reserved_quantity + :qty
                WHERE id = :id
            ";
            $stmtUpdateInv = $pdo->prepare($sqlUpdateInv);
            $stmtUpdateInv->execute([
                ':qty' => $qty,
                ':id'  => $inv['id'],
            ]);
        }

        //Sipariş durumunu reserved yap
        $sqlUpdateOrder = "
            UPDATE orders
            SET status = 'reserved'
            WHERE id = :oid
        ";
        $stmtUpdateOrder = $pdo->prepare($sqlUpdateOrder);
        $stmtUpdateOrder->execute([':oid' => $orderId]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function shipOrder(PDO $pdo, int $orderId): bool  //kargoya verme kısmı
{
    try {
        $pdo->beginTransaction();

        //Siparişi kilitle ve durumunu kontrol et
        $sqlOrder = "
            SELECT id, warehouse_id, status
            FROM orders
            WHERE id = :oid
            FOR UPDATE
        ";
        $stmtOrder = $pdo->prepare($sqlOrder);
        $stmtOrder->execute([':oid' => $orderId]);
        $order = $stmtOrder->fetch();

        if (!$order) {
            $pdo->rollBack();
            return false;
        }

        // Sadece 'reserved' siparişler kargoya verilebilir
        if ($order['status'] !== 'reserved') {
            $pdo->rollBack();
            return false;
        }

        $warehouseId = (int)$order['warehouse_id'];

        //Sipariş kalemlerini al
        $sqlItems = "
            SELECT product_id, quantity
            FROM order_items
            WHERE order_id = :oid
        ";
        $stmtItems = $pdo->prepare($sqlItems);
        $stmtItems->execute([':oid' => $orderId]);
        $items = $stmtItems->fetchAll();

        if (!$items) {
            $pdo->rollBack();
            return false;
        }

        //Her kalem için stok düş
        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $qty       = (int)$item['quantity'];

            // İlgili stok satırını kilitle
            $sqlInv = "
                SELECT id, quantity_on_hand, reserved_quantity
                FROM inventory
                WHERE warehouse_id = :wid
                  AND product_id   = :pid
                FOR UPDATE
            ";
            $stmtInv = $pdo->prepare($sqlInv);
            $stmtInv->execute([
                ':wid' => $warehouseId,
                ':pid' => $productId,
            ]);
            $inv = $stmtInv->fetch();

            if (!$inv) {
                $pdo->rollBack();
                return false;
            }

            $onHand   = (int)$inv['quantity_on_hand'];
            $reserved = (int)$inv['reserved_quantity'];

            // Güvenlik kontrolleri
            if ($reserved < $qty || $onHand < $qty) {
                $pdo->rollBack();
                return false;
            }

            // quantity_on_hand ve reserved_quantity düş
            $sqlUpdateInv = "
                UPDATE inventory
                SET 
                    quantity_on_hand = quantity_on_hand - :qty,
                    reserved_quantity = reserved_quantity - :qty
                WHERE id = :id
            ";
            $stmtUpdateInv = $pdo->prepare($sqlUpdateInv);
            $stmtUpdateInv->execute([
                ':qty' => $qty,
                ':id'  => $inv['id'],
            ]);
        }

        //Sipariş durumunu 'shipped' yap
        $sqlUpdateOrder = "
            UPDATE orders
            SET status = 'shipped'
            WHERE id = :oid
        ";
        $stmtUpdateOrder = $pdo->prepare($sqlUpdateOrder);
        $stmtUpdateOrder->execute([':oid' => $orderId]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}


