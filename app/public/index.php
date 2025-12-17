<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


// Basit autoload (namespace kullanmadan)
function app_require(string $path): void {
    $full = __DIR__ . '/../src/' . ltrim($path, '/');
    if (!file_exists($full)) {
        http_response_code(500);
        exit("Missing file: " . htmlspecialchars($full));
    }
    require_once $full;
}

app_require('Models/Db.php');
app_require('Core/View.php');
app_require('Core/Controller.php');

app_require('Models/OrderModel.php');
app_require('Models/WarehouseModel.php');

app_require('Controllers/OrdersController.php');
app_require('Controllers/WarehouseController.php');
app_require('Controllers/DashboardController.php');

// Controller/action okuma
$c = $_GET['c'] ?? 'orders';
$a = $_GET['a'] ?? 'index';

$controllerMap = [
    'orders'    => OrdersController::class,
    'warehouse' => WarehouseController::class,
    'dashboard' => DashboardController::class,
];

if (!isset($controllerMap[$c])) {
    http_response_code(404);
    exit('Controller yok');
}

$controllerClass = $controllerMap[$c];

// Db bağlantısı (Model/Controller içine veriyoruz)
$pdo = Db::connect();

$controller = new $controllerClass($pdo);

if (!method_exists($controller, $a)) {
    http_response_code(404);
    exit('Action yok');
}

$controller->$a();
