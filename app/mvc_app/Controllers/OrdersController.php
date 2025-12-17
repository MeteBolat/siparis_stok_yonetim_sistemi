<?php
require_once __DIR__ . '/../Models/OrderModel.php';

class OrdersController
{
    public function __construct(private PDO $pdo) {}

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../Views/layout/header.php';
        require __DIR__ . '/../Views/' . $view . '.php';
        require __DIR__ . '/../Views/layout/footer.php';
    }

    public function index(): void
    {
        $msg = $_GET['msg'] ?? null;

        $model = new OrderModel($this->pdo);
        $orders = $model->listOrders();

        $this->render('orders/index', compact('orders', 'msg'));
    }
}
