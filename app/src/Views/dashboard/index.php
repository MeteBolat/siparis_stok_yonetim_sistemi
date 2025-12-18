<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Dashboard</h1>
        <div>
            <a href="index.php?c=orders&a=index" class="btn btn-secondary btn-sm me-2">Sipariş Listesi</a>
            <a href="index.php?c=orders&a=create" class="btn btn-success btn-sm">+ Yeni Sipariş</a>
        </div>
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

    <!-- Grafik: En çok satılan 5 ürün -->
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

    <div class="mt-4">
        <a href="index.php?c=warehouse&a=stock" class="btn btn-outline-primary btn-sm">
            Depo Stoklarını Gör
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
const topLabels = <?= json_encode($topLabels ?? [], JSON_UNESCAPED_UNICODE) ?>;
const topData   = <?= json_encode($topData ?? [], JSON_UNESCAPED_UNICODE) ?>;

if (topLabels.length && document.getElementById('topProductsChart')) {
  new Chart(document.getElementById('topProductsChart'), {
    type: 'bar',
    data: {
      labels: topLabels,
      datasets: [{
        label: 'Satış Adedi',
        data: topData
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: true }
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } }
      }
    }
  });
}
</script>

</body>
</html>
