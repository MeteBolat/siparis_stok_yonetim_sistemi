<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sipariş Listesi</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #fff;
        }
        .status-pending { background-color: #f0ad4e; }
        .status-reserved { background-color: #5bc0de; }
        .status-shipped { background-color: #5cb85c; }
        .status-cancelled { background-color: #d9534f; }
        .btn {
            padding: 4px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 4px;
            display: inline-block;
        }
        .btn-reserve { background-color: #0275d8; color: white; }
        .btn-ship { background-color: #5cb85c; color: white; }
        .btn-disabled { background-color: #ccc; color: #666; cursor: not-allowed; }
        .btn-cancel { background-color: #d9534f; color: white; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .msg-success { background-color: #dff0d8; color: #3c763d; }
        .msg-error { background-color: #f2dede; color: #a94442; }
    </style>
</head>
<body>

<div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Sipariş Listesi</h1>
    <div>
        <a href="index.php?c=dashboard&a=index" class="btn btn-secondary">Dashboard</a>

        <a href="index.php?c=warehouse&a=stock" class="btn btn-secondary" style="padding:8px 14px; font-size:14px; margin-right:6px;">
            Depo Stokları
        </a>

        <!-- create action'ı daha sonra ekleyeceğiz -->
        <a href="index.php?c=orders&a=create" class="btn btn-success" style="padding:8px 14px; font-size:14px;">
            + Yeni Sipariş
        </a>
    </div>
</div>

<?php if (($msg ?? null) === 'reserved_success'): ?>
    <div class="msg msg-success">Stok rezervasyonu başarıyla yapıldı.</div>
<?php elseif (($msg ?? null) === 'reserved_fail'): ?>
    <div class="msg msg-error">Rezervasyon yapılamadı. Yeterli stok olmayabilir.</div>
<?php elseif (($msg ?? null) === 'shipped_success'): ?>
    <div class="msg msg-success">Sipariş başarıyla kargoya verildi.</div>
<?php elseif (($msg ?? null) === 'shipped_fail'): ?>
    <div class="msg msg-error">Kargoya verme işlemi başarısız oldu.</div>
<?php elseif (($msg ?? null) === 'cancel_success'): ?>
    <div class="msg msg-success">Sipariş başarıyla iptal edildi.</div>
<?php elseif (($msg ?? null) === 'cancel_fail'): ?>
    <div class="msg msg-error">Sipariş iptal edilirken hata oluştu.</div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Müşteri</th>
            <th>Depo</th>
            <th>Durum</th>
            <th>Toplam Tutar</th>
            <th>Kargo</th>
            <th>Oluşturulma</th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($orders)): ?>
        <tr>
            <td colspan="8">Henüz hiç sipariş yok.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= htmlspecialchars((string)$order['id']) ?></td>
                <td><?= htmlspecialchars((string)$order['customer_name']) ?></td>
                <td><?= htmlspecialchars((string)$order['warehouse_name']) ?></td>
                <td>
                    <?php
                        $status = (string)$order['status'];
                        $statusClass = 'status-' . $status;
                    ?>
                    <span class="badge <?= htmlspecialchars($statusClass) ?>">
                        <?= htmlspecialchars($status) ?>
                    </span>
                </td>
                <td><?= number_format((float)$order['total_amount'], 2) ?> ₺</td>
                <td><?= number_format((float)$order['shipping_cost'], 2) ?> ₺</td>
                <td><?= htmlspecialchars((string)$order['created_at']) ?></td>
                <td>
                    <a class="btn btn-reserve" style="background:#6f42c1; color:white;"
                       href="index.php?c=orders&a=view&id=<?= (int)$order['id'] ?>">Detay</a>

                    <?php if ($order['status'] === 'pending'): ?>
                        <!-- reserve action'ı daha sonra ekleyeceğiz -->
                        <a class="btn btn-reserve" href="index.php?c=orders&a=reserve&id=<?= (int)$order['id'] ?>">Rezerv Et</a>

                        <a class="btn btn-cancel"
                           href="index.php?c=orders&a=cancel&id=<?= (int)$order['id'] ?>"
                           onclick="return confirm('Bu siparişi iptal etmek istediğinize emin misiniz?');">
                           İptal Et
                        </a>

                    <?php elseif ($order['status'] === 'reserved'): ?>
                        <a class="btn btn-ship"
                           href="index.php?c=orders&a=ship&id=<?= (int)$order['id'] ?>">Kargoya Ver</a>

                        <a class="btn btn-cancel"
                           href="index.php?c=orders&a=cancel&id=<?= (int)$order['id'] ?>"
                           onclick="return confirm('Bu rezervasyonu iptal etmek istediğinize emin misiniz? Stoklar geri iade edilecek.');">
                           İptal Et
                        </a>

                    <?php else: ?>
                        <span class="btn btn-disabled">İşlem yok</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

</body>
</html>
