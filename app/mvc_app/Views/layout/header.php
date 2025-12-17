<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title ?? 'Sipariş Sistemi') ?></title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background-color: #f5f5f5; }
    .badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:12px; color:#fff; }
    .status-pending { background-color:#f0ad4e; }
    .status-reserved { background-color:#5bc0de; }
    .status-shipped { background-color:#5cb85c; }
    .status-cancelled { background-color:#d9534f; }
    .btn { padding:4px 10px; text-decoration:none; border-radius:4px; font-size:12px; margin-right:4px; }
    .btn-reserve { background-color:#0275d8; color:white; }
    .btn-ship { background-color:#5cb85c; color:white; }
    .btn-disabled { background-color:#ccc; color:#666; cursor:not-allowed; }
    .btn-cancel { background-color:#d9534f; color:white; }
    .msg { padding:10px; border-radius:4px; margin-bottom:10px; }
    .msg-success { background-color:#dff0d8; color:#3c763d; }
    .msg-error { background-color:#f2dede; color:#a94442; }
    .btn-secondary{ background:#6c757d; color:#fff; }
    .btn-success{ background:#28a745; color:#fff; }
  </style>
</head>
<body>
