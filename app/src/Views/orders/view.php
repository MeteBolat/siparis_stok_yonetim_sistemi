<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h3 mb-0">Sipariş #<?= (int)($order['id'] ?? 0) ?></h1>
    <div class="text-muted small">Detay ekranı</div>
  </div>

  <a href="index.php?c=orders&a=index" class="btn btn-outline-secondary btn-sm">← Geri</a>
</div>

<?php
  $status = (string)($order['status'] ?? '');
  $badge = match($status) {
  'pending' => 'warning',
  'reserved' => 'info',
  'shipped' => 'success',
  'cancelled' => 'danger',
   default => 'secondary',
};
?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 text-muted">Genel Bilgiler</h2>

        <div class="d-flex justify-content-between">
          <span class="text-muted">Durum</span>
          <span class="badge text-bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span>
        </div>
        <hr>

        <div class="d-flex justify-content-between">
          <span class="text-muted">Oluşturulma</span>
          <span><?= htmlspecialchars((string)($order['created_at'] ?? '')) ?></span>
        </div>

        <div class="d-flex justify-content-between mt-2">
          <span class="text-muted">Kargo</span>
          <span><?= number_format((float)($order['shipping_cost'] ?? 0), 2) ?> ₺</span>
        </div>

        <div class="d-flex justify-content-between mt-2">
          <span class="text-muted">Toplam</span>
          <span class="fw-semibold"><?= number_format((float)($order['total_amount'] ?? 0), 2) ?> ₺</span>
        </div>

        <hr>

         <div class="d-flex gap-2 flex-wrap">
    <?php if ($status === 'pending'): ?>

        <form method="POST" action="index.php?c=orders&a=reserve">
            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
            <button type="submit" class="btn btn-sm btn-primary">
                Rezerv Et
            </button>
        </form>

        <form method="POST"
              action="index.php?c=orders&a=cancel"
              onsubmit="return confirm('Bu siparişi iptal etmek istediğinize emin misiniz?');">
            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger">
                İptal
            </button>
        </form>

    <?php elseif ($status === 'reserved'): ?>

        <form method="POST" action="index.php?c=orders&a=ship">
            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
            <button type="submit" class="btn btn-sm btn-success">
                Kargoya Ver
            </button>
        </form>

        <form method="POST"
              action="index.php?c=orders&a=cancel"
              onsubmit="return confirm('Bu rezervasyonu iptal etmek istediğinize emin misiniz? Stoklar geri iade edilecek.');">
            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger">
                İptal
            </button>
        </form>

    <?php else: ?>

        <span class="badge text-bg-secondary">İşlem yok</span>

    <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6 text-muted">Müşteri</h2>
        <div class="row g-2">
          <div class="col-md-6"><span class="text-muted">İsim:</span> <?= htmlspecialchars((string)($order['customer_name'] ?? '')) ?></div>
          <div class="col-md-6"><span class="text-muted">Telefon:</span> <?= htmlspecialchars((string)($order['customer_phone'] ?? '')) ?></div>
          <div class="col-md-6"><span class="text-muted">E-posta:</span> <?= htmlspecialchars((string)($order['customer_email'] ?? '')) ?></div>
          <div class="col-md-6"><span class="text-muted">Şehir:</span> <?= htmlspecialchars((string)($order['customer_city'] ?? '')) ?></div>
          <div class="col-12"><span class="text-muted">Adres:</span> <?= htmlspecialchars((string)($order['customer_address'] ?? '')) ?></div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6 text-muted">Depo</h2>
        <div class="row g-2">
          <div class="col-md-6"><span class="text-muted">Depo:</span> <?= htmlspecialchars((string)($order['warehouse_name'] ?? '')) ?></div>
          <div class="col-md-6"><span class="text-muted">Şehir:</span> <?= htmlspecialchars((string)($order['warehouse_city'] ?? '')) ?></div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 text-muted mb-3">Sipariş Kalemleri</h2>

        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Ürün</th>
                <th>SKU</th>
                <th class="text-end">Adet</th>
                <th class="text-end">Birim</th>
                <th class="text-end">Toplam</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="5" class="text-center py-3">Kalem yok.</td></tr>
              <?php else: ?>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td><?= htmlspecialchars((string)$it['product_name']) ?></td>
                    <td><?= htmlspecialchars((string)$it['product_sku']) ?></td>
                    <td class="text-end"><?= (int)$it['quantity'] ?></td>
                    <td class="text-end"><?= number_format((float)$it['unit_price'], 2) ?> ₺</td>
                    <td class="text-end"><?= number_format((float)$it['total_price'], 2) ?> ₺</td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </div>
</div>
