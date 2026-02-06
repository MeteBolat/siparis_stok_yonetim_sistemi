<?php
final class OrderModel
{
    public static function list(PDO $pdo): array
    {
        $sql = "
            SELECT 
                o.id,
                o.status,
                o.total_amount,
                o.shipping_cost,
                o.created_at,
                c.name  AS customer_name,
                c.city  AS customer_city,
                w.name  AS warehouse_name,
                w.city  AS warehouse_city,
                SUM(oi.quantity)              AS total_quantity,
                COUNT(DISTINCT oi.product_id) AS product_count
            FROM orders o
            JOIN customers c    ON c.id = o.customer_id
            JOIN warehouses w   ON w.id = o.warehouse_id
            JOIN order_items oi ON oi.order_id = o.id
            GROUP BY 
                o.id, o.status, o.total_amount, o.shipping_cost, o.created_at,
                c.name, c.city, w.name, w.city
            ORDER BY o.id DESC
        ";
        return $pdo->query($sql)->fetchAll();
    }

    public static function findHeader(PDO $pdo, int $orderId): ?array
    {
        $sql = "
            SELECT 
                o.id, o.status, o.total_amount, o.shipping_cost, o.created_at,
                c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
                c.city AS customer_city, c.address AS customer_address,
                w.name AS warehouse_name, w.city AS warehouse_city
            FROM orders o
            JOIN customers c ON c.id = o.customer_id
            JOIN warehouses w ON w.id = o.warehouse_id
            WHERE o.id = :id
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $orderId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findItems(PDO $pdo, int $orderId): array
    {
        $sql = "
            SELECT 
                oi.product_id, oi.quantity, oi.unit_price, oi.total_price,
                p.name AS product_name, p.sku AS product_sku
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = :order_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    // ship_order.php ve cancel_order.php logic’ini “servis” gibi buraya taşıyoruz:

    public static function ship(PDO $pdo, int $orderId): array
{
    try {
        $pdo->beginTransaction();

        $order = self::lockOrder($pdo, $orderId);
        if (!$order || $order['status'] !== 'reserved') {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Sipariş kargoya verilemez.'];
        }

        $warehouseId = (int)$order['warehouse_id'];
        $items = self::lockOrderInventoryItems($pdo, $orderId, $warehouseId);

        foreach ($items as $it) {
            if ((int)$it['reserved_quantity'] < (int)$it['quantity']) {
                $pdo->rollBack();
                return ['ok' => false, 'msg' => 'Rezerv stok yetersiz.'];
            }

            $pdo->prepare("
                UPDATE inventory
                SET 
                    reserved_quantity = reserved_quantity - :qty,
                    quantity_on_hand = quantity_on_hand - :qty
                WHERE id = :inv_id
            ")->execute([
                ':qty' => (int)$it['quantity'],
                ':inv_id' => (int)$it['inventory_id'],
            ]);
        }

        $pdo->prepare("UPDATE orders SET status='shipped' WHERE id=:id")
            ->execute([':id' => $orderId]);

        $pdo->commit();

        return [
            'ok' => true,
            'msg' => 'Sipariş kargoya verildi.'
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok' => false, 'msg' => 'Kargoya verme işlemi başarısız.'];
    }
}


    public static function cancel(PDO $pdo, int $orderId): array
{
    try {
        $pdo->beginTransaction();

        $order = self::lockOrder($pdo, $orderId);
        if (!$order || in_array($order['status'], ['cancelled','shipped'])) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Sipariş iptal edilemez.'];
        }

        if ($order['status'] === 'pending') {
            $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=:id")
                ->execute([':id' => $orderId]);

            $pdo->commit();
            return ['ok' => true, 'msg' => 'Sipariş iptal edildi.'];
        }

        $warehouseId = (int)$order['warehouse_id'];
        $items = self::lockOrderInventoryItems($pdo, $orderId, $warehouseId);

        foreach ($items as $it) {
            $pdo->prepare("
                UPDATE inventory
                SET 
                    reserved_quantity = reserved_quantity - :qty,
                    quantity_on_hand = quantity_on_hand + :qty
                WHERE id = :inv_id
            ")->execute([
                ':qty' => (int)$it['quantity'],
                ':inv_id' => (int)$it['inventory_id']
            ]);
        }

        $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=:id")
            ->execute([':id' => $orderId]);

        $pdo->commit();

        return [
            'ok' => true,
            'msg' => 'Rezervasyon iptal edildi.'
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok' => false, 'msg' => 'İptal sırasında hata oluştu.'];
    }
}


    private static function lockOrder(PDO $pdo, int $orderId): ?array
    {
        $stmt = $pdo->prepare("
            SELECT id, status, warehouse_id
            FROM orders
            WHERE id = :id
            FOR UPDATE
        ");
        $stmt->execute([':id' => $orderId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function lockOrderInventoryItems(PDO $pdo, int $orderId, int $warehouseId): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                oi.product_id,
                oi.quantity,
                inv.id AS inventory_id,
                inv.quantity_on_hand,
                inv.reserved_quantity
            FROM order_items oi
            JOIN inventory inv
              ON inv.warehouse_id = :warehouse_id
             AND inv.product_id   = oi.product_id
            WHERE oi.order_id = :order_id
            FOR UPDATE
        ");
        $stmt->execute([
            ':warehouse_id' => $warehouseId,
            ':order_id' => $orderId,
        ]);
        return $stmt->fetchAll() ?: [];
    }

    public static function createMulti(PDO $pdo, int $customerId, int $warehouseId, array $cleanItems): array
{
    try {
        $pdo->beginTransaction();

        // Müşteri şehir
        $stmtCust = $pdo->prepare("SELECT id, city FROM customers WHERE id = :id");
        $stmtCust->execute([':id' => $customerId]);
        $customer = $stmtCust->fetch();
        if (!$customer) throw new Exception("Müşteri bulunamadı.");

        // Depo şehir
        $stmtWh = $pdo->prepare("SELECT id, city FROM warehouses WHERE id = :id");
        $stmtWh->execute([':id' => $warehouseId]);
        $warehouse = $stmtWh->fetch();
        if (!$warehouse) throw new Exception("Depo bulunamadı.");

        // Ürünleri topluca çek
        $productIds = array_column($cleanItems, 'product_id');
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $stmtProd = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders)");
        $stmtProd->execute($productIds);
        $productRows = $stmtProd->fetchAll();

        if (count($productRows) !== count(array_unique($productIds))) {
            throw new Exception("Bazı ürünler bulunamadı.");
        }

        $productMap = [];
        foreach ($productRows as $pr) $productMap[(int)$pr['id']] = $pr;

        // Stok kayıtlarını çek
        $invSql = "
            SELECT product_id, quantity_on_hand
            FROM inventory
            WHERE warehouse_id = ?
              AND product_id IN ($placeholders)
        ";
        $params = array_merge([$warehouseId], $productIds);
        $stmtInv = $pdo->prepare($invSql);
        $stmtInv->execute($params);
        $invRows = $stmtInv->fetchAll();

        $invMap = [];
        foreach ($invRows as $ir) $invMap[(int)$ir['product_id']] = (int)$ir['quantity_on_hand'];

        // Stok kontrolü
        foreach ($cleanItems as $row) {
            $pid = (int)$row['product_id'];
            $qty = (int)$row['quantity'];

            if (!isset($invMap[$pid])) {
                throw new Exception("Bu depoda ürün için stok kaydı yok. Ürün ID: {$pid}");
            }

            $available = (int)$invMap[$pid];
            if ($available < $qty) {
                throw new Exception("Yeterli stok yok. Ürün ID: {$pid}, Mevcut: {$available}, İstenen: {$qty}");
            }
        }

        // Kargo ücreti (şehirler)
        $stmtShip = $pdo->prepare("
            SELECT shipping_cost
            FROM city_distances
            WHERE from_city = :from_city AND to_city = :to_city
            LIMIT 1
        ");
        $stmtShip->execute([
            ':from_city' => $warehouse['city'],
            ':to_city'   => $customer['city'],
        ]);
        $distanceRow = $stmtShip->fetch();
        $shippingCost = $distanceRow ? (float)$distanceRow['shipping_cost'] : 0.00;

        // Fiyat hesapları
        $itemsTotal = 0.0;
        $enriched = [];
        foreach ($cleanItems as $row) {
            $pid = (int)$row['product_id'];
            $qty = (int)$row['quantity'];
            $unitPrice = (float)$productMap[$pid]['price'];
            $totalPrice = $unitPrice * $qty;

            $enriched[] = [
                'product_id' => $pid,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ];
            $itemsTotal += $totalPrice;
        }

        $totalAmount = $itemsTotal + $shippingCost;

        // Orders insert
        $stmtOrder = $pdo->prepare("
            INSERT INTO orders (customer_id, warehouse_id, status, shipping_cost, total_amount)
            VALUES (:customer_id, :warehouse_id, :status, :shipping_cost, :total_amount)
        ");
        $stmtOrder->execute([
            ':customer_id' => $customerId,
            ':warehouse_id' => $warehouseId,
            ':status' => 'pending',
            ':shipping_cost' => $shippingCost,
            ':total_amount' => $totalAmount,
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // Order items insert
        $stmtItem = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price)
            VALUES (:order_id, :product_id, :quantity, :unit_price, :total_price)
        ");

        foreach ($enriched as $row) {
            $stmtItem->execute([
                ':order_id' => $orderId,
                ':product_id' => $row['product_id'],
                ':quantity' => $row['quantity'],
                ':unit_price' => $row['unit_price'],
                ':total_price' => $row['total_price'],
            ]);
        }

        $pdo->commit();

        $msg = "Sipariş oluşturuldu. Sipariş ID: {$orderId} | Kalem sayısı: "
             . count($enriched)
             . " | Toplam: " . number_format($totalAmount, 2) . " ₺";

        return [
            'ok' => true,
            'msg'=> $msg
        ];
        
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return [
            'ok' => false,
            'msg' => "Sipariş oluşturulurken hata: " . $e->getMessage()];
    }
}
    public static function reserve(PDO $pdo, int $orderId): array
{
    try {
        $pdo->beginTransaction();

        $stmtOrder = $pdo->prepare("
            SELECT id, warehouse_id, status
            FROM orders
            WHERE id = :oid
            FOR UPDATE
        ");
        $stmtOrder->execute([':oid' => $orderId]);
        $order = $stmtOrder->fetch();

        if (!$order || $order['status'] !== 'pending') {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Sipariş rezerve edilemez.'];
        }

        $warehouseId = (int)$order['warehouse_id'];

        $stmtItems = $pdo->prepare("
            SELECT product_id, quantity
            FROM order_items
            WHERE order_id = :oid
        ");
        $stmtItems->execute([':oid' => $orderId]);
        $items = $stmtItems->fetchAll();

        foreach ($items as $item) {
            $stmtInv = $pdo->prepare("
                SELECT id, quantity_on_hand, reserved_quantity
                FROM inventory
                WHERE warehouse_id = :wid AND product_id = :pid
                FOR UPDATE
            ");
            $stmtInv->execute([
                ':wid' => $warehouseId,
                ':pid' => (int)$item['product_id']
            ]);
            $inv = $stmtInv->fetch();

            if (!$inv) {
                $pdo->rollBack();
                return ['ok' => false, 'msg' => 'Stok kaydı bulunamadı.'];
            }

            $available = (int)$inv['quantity_on_hand'] - (int)$inv['reserved_quantity'];
            if ($available < (int)$item['quantity']) {
                $pdo->rollBack();
                return ['ok' => false, 'msg' => 'Yeterli stok yok.'];
            }

            $pdo->prepare("
                UPDATE inventory
                SET reserved_quantity = reserved_quantity + :qty
                WHERE id = :id
            ")->execute([
                ':qty' => (int)$item['quantity'],
                ':id' => (int)$inv['id']
            ]);
        }

        $pdo->prepare("UPDATE orders SET status='reserved' WHERE id=:oid")
            ->execute([':oid' => $orderId]);

        $pdo->commit();

        return [
            'ok' => true,
            'msg' => 'Sipariş başarıyla rezerve edildi.'
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok' => false, 'msg' => 'Rezervasyon sırasında hata oluştu.'];
    }
}
}
