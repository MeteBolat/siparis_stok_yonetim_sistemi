<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Dashboard</h1>
</div>

<!-- Üst kartlar -->
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card p-3 shadow-sm">
      <div class="small text-muted">Toplam Sipariş</div>
      <div class="h3 mb-0"><?= (int)($totalOrders ?? 0) ?></div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card p-3 shadow-sm">
      <div class="small text-muted">Toplam Ciro</div>
      <div class="h3 mb-0"><?= number_format((float)($totalRevenue ?? 0), 2) ?> ₺</div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card p-3 shadow-sm">
      <div class="small text-muted">Depo Sayısı</div>
      <div class="h3 mb-0"><?= (int)($totalWarehouses ?? 0) ?></div>
    </div>
  </div>
</div>

<!-- Durum kartları -->
<div class="row g-3">
  <div class="col-md-3">
    <div class="card p-3 shadow-sm">
      <div class="small text-muted">Pending</div>
      <div class="h3 mb-0 text-warning"><?= (int)($counts['pending'] ?? 0) ?></div>
      <a class="small" href="index.php?c=orders&a=index">Listeyi Aç</a>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card p-3 shadow-sm">
      <div class="small text-muted">Reserved</div>
      <div class="h3 mb-0 text-info"><?= (int)($counts['reserved'] ?? 0) ?></div>
      <a class="small" href="index.php?c=orders&a=index">Listeyi Aç</a>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card p-3 shadow-sm">
      <div class="small text-muted">Shipped</div>
      <div class="h3 mb-0 text-success"><?= (int)($counts['shipped'] ?? 0) ?></div>
      <a class="small" href="index.php?c=orders&a=index">Listeyi Aç</a>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card p-3 shadow-sm">
      <div class="small text-muted">Cancelled</div>
      <div class="h3 mb-0 text-danger"><?= (int)($counts['cancelled'] ?? 0) ?></div>
      <a class="small" href="index.php?c=orders&a=index">Listeyi Aç</a>
    </div>
  </div>
</div>

<!-- Grafik -->
<div class="row g-3 mt-3">
  <div class="col-12">
    <div class="card p-3 shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <div class="small text-muted">Grafik</div>
          <div class="h5 mb-0">En Çok Satılan 5 Ürün (Shipped)</div>
        </div>
      </div>

      <?php if (empty($topLabels)): ?>
        <div class="alert alert-warning mb-0">
          Henüz <strong>shipped</strong> sipariş yok. Grafik için en az 1 siparişi “Kargoya Ver” yap.
        </div>
      <?php else: ?>
        <canvas id="topProductsChart" height="110"></canvas>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Chart.js (bootstrap yok, o footer'da) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const topLabels = <?= json_encode($topLabels ?? [], JSON_UNESCAPED_UNICODE) ?>;
const topData   = <?= json_encode($topData ?? [], JSON_UNESCAPED_UNICODE) ?>;

if (topLabels.length && document.getElementById('topProductsChart')) {
  new Chart(document.getElementById('topProductsChart'), {
    type: 'bar',
    data: {
      labels: topLabels,
      datasets: [{ label: 'Satış Adedi', data: topData }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });
}
</script>
