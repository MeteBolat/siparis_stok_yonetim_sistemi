<?php
require_once __DIR__ . '/../src/db.php'; // /var/www/src/db.php

$c = $_GET['c'] ?? 'orders';
$a = $_GET['a'] ?? 'index';

$routes = [
  'orders' => __DIR__ . '/../mvc_app/Controllers/OrdersController.php',
];

if (!isset($routes[$c])) {
  http_response_code(404);
  exit('Controller yok');
}

require_once $routes[$c];

$controller = new OrdersController($pdo);

if (!method_exists($controller, $a)) {
  http_response_code(404);
  exit('Action yok');
}

$controller->$a();
