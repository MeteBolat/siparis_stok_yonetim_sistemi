<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';

$controller = $_GET['c'] ?? 'orders';
$action     = $_GET['a'] ?? 'index';

$map = [
  'orders'     => __DIR__ . '/../app/Controllers/OrderController.php',
  'warehouses' => __DIR__ . '/../app/Controllers/WarehouseController.php',
  'dashboard'  => __DIR__ . '/../app/Controllers/DashboardController.php',
  'auth'       => __DIR__ . '/../app/Controllers/AuthController.php',
];

if (!isset($map[$controller])) {
  http_response_code(404);
  exit('Controller bulunamadı');
}

require_once $map[$controller];

$class = ucfirst($controller) . 'Controller';
$ctrl  = new $class($pdo);

if (!method_exists($ctrl, $action)) {
  http_response_code(404);
  exit('Action bulunamadı');
}

$ctrl->$action();
