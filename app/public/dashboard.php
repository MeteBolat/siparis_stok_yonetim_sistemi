<?php
require_once __DIR__ . '/../src/db.php';

/**
 * Notlar:
 * - “Satış” metrikleri için shipped siparişleri baz aldım.
 * - İstersen reserved+shipped olarak da değiştirebiliriz.
 */

function fetchAllAssoc(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 1) Özet kartlar
$kpi = fetchAllAssoc($pdo, "
    SELECT
        (SELECT COUNT(*) FROM orders) AS total_orders,
        (SELECT COUNT(*) FROM orders WHERE status='pending') AS pending_orders,
        (SELECT COUNT(*) FROM orders WHERE status='reserved') AS reserved_orders,
        (SELECT COUNT(*) FROM orders WHERE status='shipped') AS shipped_orders,
        (SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='shipped') AS shipped_revenue
")[0] ?? [
    'total_orders'=>0,'pending_orders'=>0,'reserved_orders'=>0,'shipped_orders'=>0,'shipped_revenue'=>0
];

// 2) Aylık sipariş sayısı + ciro (shipped)
$monthly = fetchAllAssoc($pdo, "
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS ym,
        COUNT(*) AS order_count,
        COALESCE(SUM(total_amount),0) AS revenue
    FROM orders
    WHERE status='shipped'
    GROUP BY ym
    ORDER BY ym ASC
");

// 3) En çok satılan 5 ürün (shipped)
$topProducts = fetchAllAssoc($pdo, "
    SELECT
        p.name,
        p.sku,
        SUM(oi.quantity) AS qty_sold
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN products p ON p.id = oi.product_id
    WHERE o.status='shipped'
    GROUP BY p.id
    ORDER BY qty_sold DESC
    LIMIT 5
");

// 4) Depolara göre eldeki stok değeri (quantity_on_hand * price)
$warehouseValue = fetchAllAssoc($pdo, "
    SELECT
        w.name AS warehouse_name,
        COALESCE(SUM(i.quantity_on_hand * p.price),0) AS stock_value
    FROM warehouses w
    LEFT JOIN inventory i ON i.warehouse_id = w.id
    LEFT JOIN products p ON p.id = i.product_id
    GROUP BY w.id
    ORDER BY stock_value DESC
");

// 5) Şehirlere göre shipped sipariş sayısı (müşteri şehri)
$cityOrders = fetchAllAssoc($pdo, "
    SELECT
        c.city AS city,
        COUNT(*) AS order_count
    FROM orders o
    JOIN customers c ON c.id = o.customer_id
    WHERE o.status='shipped'
    GROUP BY c.city
    ORDER BY order_count DESC
    LIMIT 10
");

// Chart data hazırlığı
$monthlyLabels = array_column($monthly, 'ym');
$monthlyCounts = array_map('intval', array_column($monthly, 'order_count'));
$monthlyRevenue= array_map('floatval', array_column($monthly, 'revenue'));

$topLabels = array_map(fn($r) => $r['name'].' ('.$r['sku'].')', $topProducts);
$topQty    = array_map('intval', array_column($topProducts, 'qty_sold'));

$whLabels  = array_column($warehouseValue, 'warehouse_name');
$whValues  = array_map('floatval', array_column($warehouseValue, 'stock_value'));

$cityLabels= array_column($cityOrders, 'city');
$cityCounts= array_map('intval', array_column($cityOrders, 'order_count'));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Dashboard</h1>
    <div>
      <a href="orders.php" class="btn btn-secondary btn-sm me-2">Siparişler</a>
      <a href="warehouse_stock.php" class="btn btn-secondary btn-sm me-2">Depo Stokları</a>
      <a href="order_create.php" class="btn btn-success btn-sm">+ Yeni Sipariş</a>
    </div>
  </div>

  <!-- KPI -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card p-3">
        <div class="small text-muted">Toplam Sipariş</div>
        <div class="h4 mb-0"><?= (int)$kpi['total_orders'] ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3">
        <div class="small text-muted">Pending</div>
        <div class="h4 mb-0"><?= (int)$kpi['pending_orders'] ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3">
        <div class="small text-muted">Reserved</div>
        <div class="h4 mb-0"><?= (int)$kpi['reserved_orders'] ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3">
        <div class="small text-muted">Shipped Ciro</div>
        <div class="h4 mb-0"><?= number_format((float)$kpi['shipped_revenue'], 2) ?> ₺</div>
      </div>
    </div>
  </div>

  <!-- Charts -->
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Aylık Shipped Sipariş & Ciro</div>
        <canvas id="monthlyChart" height="110"></canvas>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card p-3">
        <div class="fw-semibold mb-2">En Çok Satan 5 Ürün (Adet)</div>
        <canvas id="topProductsChart" height="180"></canvas>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Depolara Göre Eldeki Stok Değeri</div>
        <canvas id="warehouseValueChart" height="160"></canvas>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Şehirlere Göre Shipped Sipariş (Top 10)</div>
        <canvas id="cityChart" height="160"></canvas>
      </div>
    </div>
  </div>

</div>

<script>
const monthlyLabels  = <?= json_encode($monthlyLabels, JSON_UNESCAPED_UNICODE) ?>;
const monthlyCounts  = <?= json_encode($monthlyCounts, JSON_UNESCAPED_UNICODE) ?>;
const monthlyRevenue = <?= json_encode($monthlyRevenue, JSON_UNESCAPED_UNICODE) ?>;

const topLabels = <?= json_encode($topLabels, JSON_UNESCAPED_UNICODE) ?>;
const topQty    = <?= json_encode($topQty, JSON_UNESCAPED_UNICODE) ?>;

const whLabels = <?= json_encode($whLabels, JSON_UNESCAPED_UNICODE) ?>;
const whValues = <?= json_encode($whValues, JSON_UNESCAPED_UNICODE) ?>;

const cityLabels = <?= json_encode($cityLabels, JSON_UNESCAPED_UNICODE) ?>;
const cityCounts = <?= json_encode($cityCounts, JSON_UNESCAPED_UNICODE) ?>;

// Aylık chart (2 dataset)
new Chart(document.getElementById('monthlyChart'), {
  type: 'line',
  data: {
    labels: monthlyLabels,
    datasets: [
      { label: 'Sipariş (adet)', data: monthlyCounts, yAxisID: 'y' },
      { label: 'Ciro (₺)', data: monthlyRevenue, yAxisID: 'y1' }
    ]
  },
  options: {
    responsive: true,
    scales: {
      y: { beginAtZero: true, position: 'left' },
      y1:{ beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
    }
  }
});

new Chart(document.getElementById('topProductsChart'), {
  type: 'bar',
  data: { labels: topLabels, datasets: [{ label: 'Adet', data: topQty }] },
  options: { responsive: true, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('warehouseValueChart'), {
  type: 'bar',
  data: { labels: whLabels, datasets: [{ label: 'Stok Değeri (₺)', data: whValues }] },
  options: { responsive: true }
});

new Chart(document.getElementById('cityChart'), {
  type: 'bar',
  data: { labels: cityLabels, datasets: [{ label: 'Sipariş', data: cityCounts }] },
  options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
