<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Sipariş & Stok Yönetimi') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">


<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="index.php?c=dashboard&a=index">SiparişStok</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>"
             href="index.php?c=dashboard&a=index">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($activeNav ?? '') === 'orders' ? 'active' : '' ?>"
             href="index.php?c=orders&a=index">Siparişler</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($activeNav ?? '') === 'warehouse' ? 'active' : '' ?>"
             href="index.php?c=warehouse&a=stock">Depo Stokları</a>
        </li>
      </ul>

      <div class="d-flex gap-2">
        <a class="btn btn-success btn-sm" href="index.php?c=orders&a=create">
          + Yeni Sipariş
        </a>
      </div>
    </div>
  </div>
</nav>

<?php if (Flash::has()): ?>
  <?php [$type, $message] = Flash::get(); ?>
  <div class="container mt-3">
    <div class="alert alert-<?= htmlspecialchars($type) ?> alert-dismissible fade show">
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  </div>
<?php endif; ?>

<?php if ($flash = Flash::get()): ?>
<?php endif; ?>

<main class="container py-4">
