<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h3 mb-0">Depo Stokları</h1>
    <div class="text-muted small">Depo bazlı stok durumu</div>
  </div>
</div>

<form method="GET" class="card p-3 mb-3 shadow-sm" action="index.php">
  <input type="hidden" name="c" value="warehouse">
  <input type="hidden" name="a" value="stock">

  <div class="row g-3 align-items-end">
    <div class="col-md-6">
      <label for="warehouse_id" class="form-label">Depo Seç</label>
      <select name="warehouse_id" id="warehouse_id" class="form-select" onchange="this.form.submit()">
        <?php foreach (($warehouses ?? []) as $w): ?>
          <option value="<?= (int)$w['id'] ?>" <?= ((int)$w['id'] === (int)($selectedWarehouseId ?? 0)) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$w['name']) ?> (<?= htmlspecialchars((string)$w['city']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</form>

<?php if (!empty($selectedWarehouse)): ?>
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card p-3 shadow-sm">
        <div class="small text-muted">Toplam Ürün Sayısı</div>
        <div class="h4 mb-0"><?= (int)($summary['total_products'] ?? 0) ?></div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card p-3 shadow-sm">
        <div class="small text-muted">Eldeki Stok (quantity_on_hand)</div>
        <div class="h4 mb-0"><?= (int)($summary['total_on_hand'] ?? 0) ?></div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card p-3 shadow-sm">
        <div class="small text-muted">Toplam Stok Değeri (eldeki × fiyat)</div>
        <div class="h4 mb-0"><?= number_format((float)($summary['total_stock_value'] ?? 0), 2) ?> ₺</div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header">
      <strong><?= htmlspecialchars((string)$selectedWarehouse['name']) ?></strong> stokları
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-striped mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>SKU</th>
              <th>Ürün</th>
              <th class="text-end">Fiyat</th>
              <th class="text-end">Eldeki</th>
              <th class="text-end">Rezerve</th>
              <th class="text-end">Toplam</th>
              <th class="text-end">Stok Değeri</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($stocks)): ?>
              <tr><td colspan="7" class="text-center py-3">Bu depoda stok kaydı yok.</td></tr>
            <?php else: ?>
              <?php foreach ($stocks as $row): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$row['sku']) ?></td>
                  <td><?= htmlspecialchars((string)$row['name']) ?></td>
                  <td class="text-end"><?= number_format((float)$row['price'], 2) ?> ₺</td>
                  <td class="text-end"><?= (int)$row['quantity_on_hand'] ?></td>
                  <td class="text-end"><?= (int)$row['reserved_quantity'] ?></td>
                  <td class="text-end"><?= (int)$row['total_quantity'] ?></td>
                  <td class="text-end"><?= number_format((float)$row['stock_value'], 2) ?> ₺</td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="alert alert-warning">Henüz tanımlı depo yok.</div>
<?php endif; ?>
