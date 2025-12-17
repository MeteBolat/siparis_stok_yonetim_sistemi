<?php
echo "__DIR__ = " . __DIR__ . "<br>";
echo "ls __DIR__: <pre>" . shell_exec("ls -la " . escapeshellarg(__DIR__)) . "</pre>";
echo "ls parent: <pre>" . shell_exec("ls -la " . escapeshellarg(dirname(__DIR__))) . "</pre>";
exit;

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';

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
