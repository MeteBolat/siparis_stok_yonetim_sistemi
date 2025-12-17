<?php $title = 'Sipariş Listesi'; ?>

<div style="display:flex; justify-content:space-between; align-items:center;">
  <h1>Sipariş Listesi</h1>
  <div>
    <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
    <a href="warehouse_stock.php" class="btn btn-secondary" style="padding:8px 14px; font-size:14px; margin-right:6px;">
      Depo Stokları
    </a>
    <a href="order_create.php" class="btn btn-success" style="padding:8px 14px; font-size:14px;">
      + Yeni Sipariş
    </a>
  </div>
</div>

<?php if ($msg === 'reserved_success'): ?>
  <div class="msg msg-success">Stok rezervasyonu başarıyla yapıldı.</div>
<?php elseif ($msg === 'reserved_fail'): ?>
  <div class="msg msg-error">Rezervasyon yapılamadı. Yeterli stok olmayabilir.</div>
<?php elseif ($msg === 'shipped_success'): ?>
  <div class="msg msg-success">Sipariş başarıyla kargoya verildi.</div>
<?php elseif ($msg === 'shipped_fail'): ?>
  <div class="msg msg-error">Kargoya verme işlemi başarısız oldu.</div>
<?php elseif ($msg === 'cancel_success'): ?>
  <div class="msg msg-success">Sipariş başarıyla iptal edildi.</div>
<?php elseif ($msg === 'cancel_fail'): ?>
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
    <?php if (!$orders): ?>
      <tr><td colspan="8">Henüz hiç sipariş yok.</td></tr>
    <?php else: ?>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td><?= htmlspecialchars($order['id']) ?></td>
          <td><?= htmlspecialchars($order['customer_name']) ?></td>
          <td><?= htmlspecialchars($order['warehouse_name']) ?></td>
          <td>
            <?php $status = $order['status']; $statusClass = 'status-' . $status; ?>
            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
          </td>
          <td><?= number_format((float)$order['total_amount'], 2) ?> ₺</td>
          <td><?= number_format((float)$order['shipping_cost'], 2) ?> ₺</td>
          <td><?= htmlspecialchars($order['created_at']) ?></td>
          <td>
            <a class="btn btn-reserve" style="background:#6f42c1; color:white;"
               href="order_view.php?id=<?= (int)$order['id'] ?>">Detay</a>

            <?php if ($order['status'] === 'pending'): ?>
              <a class="btn btn-reserve" href="reserve_order.php?id=<?= (int)$order['id'] ?>">Rezerv Et</a>
              <a class="btn btn-cancel" href="cancel_order.php?id=<?= (int)$order['id'] ?>"
                 onclick="return confirm('Bu siparişi iptal etmek istediğinize emin misiniz?');">İptal Et</a>

            <?php elseif ($order['status'] === 'reserved'): ?>
              <a class="btn btn-ship" href="ship_order.php?id=<?= (int)$order['id'] ?>">Kargoya Ver</a>
              <a class="btn btn-cancel" href="cancel_order.php?id=<?= (int)$order['id'] ?>"
                 onclick="return confirm('Bu rezervasyonu iptal etmek istediğinize emin misiniz? Stoklar geri iade edilecek.');">İptal Et</a>

            <?php else: ?>
              <span class="btn btn-disabled">İşlem yok</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>