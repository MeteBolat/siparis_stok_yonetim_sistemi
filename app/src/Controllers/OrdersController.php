<?php

final class OrdersController extends Controller
{
    private function onlyPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }
    }

    private function handleResult(array $result, string $successUrl, string $failUrl): void
    {
        $this->redirectWithFlash(
            $result['ok'] ? 'success' : 'danger',
            $result['msg'],
            $result['ok'] ? $successUrl : $failUrl
        );
    }

    public function index(): void
    {
        $orders = OrderModel::list($this->pdo);

        $this->render('orders/index.php', [
            'title' => 'Siparişler',
            'activeNav' => 'orders',
            'orders' => $orders,
        ]);
    }

    public function view(): void
    {
        Auth::check();

        $orderId = (int)($_GET['id'] ?? 0);

        if ($orderId <= 0) {
            http_response_code(400);
            exit('Geçersiz sipariş ID');
        }

        $order = OrderModel::findHeader($this->pdo, $orderId);
        $items = OrderModel::findItems($this->pdo, $orderId);

        if (!$order) {
            http_response_code(404);
            exit('Sipariş bulunamadı');
        }

        $this->render('orders/view.php', [
            'title' => 'Sipariş #' . $orderId,
            'activeNav' => 'orders',
            'order' => $order,
            'items' => $items,
        ]);
    }

    public function reserve(): void
    {
        Auth::check();

        $this->onlyPost();

        $orderId = (int)($_POST['id'] ?? 0);

        $result = OrderModel::reserve($this->pdo, $orderId);

        $this->handleResult(
            $result,
            'index.php?c=orders&a=index',
            'index.php?c=orders&a=index'
        );
    }

    public function ship(): void
    {
        Auth::check();
        
        $this->onlyPost();

        $orderId = (int)($_POST['id'] ?? 0);

        $result = OrderModel::ship($this->pdo, $orderId);

        $this->handleResult(
            $result,
            'index.php?c=orders&a=index',
            'index.php?c=orders&a=index'
        );
    }

    public function cancel(): void
    {
        Auth::check();
        
        $this->onlyPost();

        $orderId = (int)($_POST['id'] ?? 0);

        $result = OrderModel::cancel($this->pdo, $orderId);

        $this->handleResult(
            $result,
            'index.php?c=orders&a=index',
            'index.php?c=orders&a=index'
        );
    }

    public function create(): void
    {
        Auth::check();

        $customers  = $this->pdo->query("SELECT id, name, city FROM customers ORDER BY name")->fetchAll();
        $warehouses = $this->pdo->query("SELECT id, name, city FROM warehouses ORDER BY name")->fetchAll();
        $products   = $this->pdo->query("SELECT id, sku, name, price FROM products ORDER BY name")->fetchAll();

        $this->render('orders/create.php', [
            'title' => 'Yeni Sipariş',
            'activeNav' => 'orders',
            'customers' => $customers,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function store(): void
    {
        Auth::check();
        
        $this->onlyPost();

        $customerId  = (int)($_POST['customer_id'] ?? 0);
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $itemsInput  = $_POST['items'] ?? [];

        $cleanItems = [];
        foreach ($itemsInput as $row) {
            $pid = (int)($row['product_id'] ?? 0);
            $qty = (int)($row['quantity'] ?? 0);

            if ($pid > 0 && $qty > 0) {
                $cleanItems[] = [
                    'product_id' => $pid,
                    'quantity' => $qty
                ];
            }
        }

        if ($customerId <= 0 || $warehouseId <= 0 || empty($cleanItems)) {
            $this->redirectWithFlash(
                'danger',
                'Sipariş bilgileri eksik.',
                'index.php?c=orders&a=create'
            );
        }

        $result = OrderModel::createMulti(
            $this->pdo,
            $customerId,
            $warehouseId,
            $cleanItems
        );

        $this->handleResult(
            $result,
            'index.php?c=orders&a=index',
            'index.php?c=orders&a=create'
        );
    }
}
