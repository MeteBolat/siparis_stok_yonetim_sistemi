<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Sipariş Listesi</h1>
</div>

<?php if (($msg ?? null) === 'reserved_success'): ?>
  <div class="alert alert-success">Stok rezervasyonu başarıyla yapıldı.</div>
<?php elseif (($msg ?? null) === 'reserved_fail'): ?>
  <div class="alert alert-danger">Rezervasyon yapılamadı. Yeterli stok olmayabilir.</div>
<?php elseif (($msg ?? null) === 'shipped_success'): ?>
  <div class="alert alert-success">Sipariş başarıyla kargoya verildi.</div>
<?php elseif (($msg ?? null) === 'shipped_fail'): ?>
  <div class="alert alert-danger">Kargoya verme işlemi başarısız oldu.</div>
<?php elseif (($msg ?? null) === 'cancel_success'): ?>
  <div class="alert alert-success">Sipariş başarıyla iptal edildi.</div>
<?php elseif (($msg ?? null) === 'cancel_fail'): ?>
  <div class="alert alert-danger">Sipariş iptal edilirken hata oluştu.</div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-striped table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Müşteri</th>
          <th>Depo</th>
          <th>Durum</th>
          <th class="text-end">Toplam</th>
          <th class="text-end">Kargo</th>
          <th>Oluşturulma</th>
          <th style="min-width:240px;">İşlemler</th>
        </tr>
      </thead>

      <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="8" class="text-center py-4">Henüz hiç sipariş yok.</td></tr>
      <?php else: ?>
        <?php foreach ($orders as $order): ?>
          <?php $status = (string)$order['status']; ?>
          <tr>
            <td><?= (int)$order['id'] ?></td>
            <td><?= htmlspecialchars((string)$order['customer_name']) ?></td>
            <td><?= htmlspecialchars((string)$order['warehouse_name']) ?></td>
            <td>
              <?php
                $badge = match($status) {
                  'pending' => 'warning',
                  'reserved' => 'info',
                  'shipped' => 'success',
                  'cancelled' => 'danger',
                  default => 'secondary',
                };
              ?>
              <span class="badge text-bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span>
            </td>
            <td class="text-end"><?= number_format((float)$order['total_amount'], 2) ?> ₺</td>
            <td class="text-end"><?= number_format((float)$order['shipping_cost'], 2) ?> ₺</td>
            <td><?= htmlspecialchars((string)$order['created_at']) ?></td>
            <td class="d-flex gap-2 flex-wrap">
                <a class="btn btn-sm btn-outline-primary"
                  href="index.php?c=orders&a=view&id=<?= (int)$order['id'] ?>">
                   Detay
                </a>

                <?php if ($status === 'pending'): ?>

                      <form method="POST"
                            action="index.php?c=orders&a=reserve&id=<?= (int)$order['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-primary">
                          Rezerv Et
                        </button>
                      </form>

                          <form method="POST"
                                action="index.php?c=orders&a=cancel&id=<?= (int)$order['id'] ?>"
                                onsubmit="return confirm('Bu siparişi iptal etmek istediğinize emin misiniz?');">
                            <button type="submit" class="btn btn-sm btn-danger">
                              İptal
                            </button>
                          </form>
                
                <?php elseif ($status === 'reserved'): ?>
                  <form method="POST"
                        action="index.php?c=orders&a=ship&id=<?= (int)$order['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-success">
                        Kargoya Ver
                      </button>
                  </form>

                          <form method="POST"
                                action="index.php?c=orders&a=cancel&id=<?= (int)$order['id'] ?>"
                                onsubmit="return confirm('Bu rezervasyonu iptal etmek istediğinize emin misiniz?');">
                            <button type="submit" class="btn btn-sm btn-danger">
                              İptal
                            </button>
                          </form>

                        <?php else: ?>
                          <span class="badge text-bg-secondary">İşlem yok</span>
                        <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
