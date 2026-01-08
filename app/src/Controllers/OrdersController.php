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

    public function index(): void
    {
        $msg = $_GET['msg'] ?? null;
        $orders = OrderModel::list($this->pdo);

        $this->render('orders/index.php', [
            'title' => 'Siparişler',
            'activeNav' => 'orders',
            'orders' => $orders,
            'msg' => $msg,
        ]);
    }

    public function view(): void
    {
        $orderId = (int)($_POST['id'] ?? 0);
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
            'title' => 'Sipariş Detayı #' . $orderId,
            'activeNav' => 'orders',
            'order' => $order,
            'items' => $items,
        ]);
    }

    // STATE CHANGES → POST ZORUNLU
    public function ship(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }

        $orderId = (int)($_POST['id'] ?? 0);
        $ok = $orderId > 0 ? OrderModel::ship($this->pdo, $orderId) : false;

        $this->redirect(
            "index.php?c=orders&a=index&msg=" . ($ok ? "shipped_success" : "shipped_fail")
        );
    }

    public function reserve(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }

        $orderId = (int)(['id'] ?? 0);
        $ok = $orderId > 0 ? OrderModel::reserve($this->pdo, $orderId) : false;

        $this->redirect(
            "index.php?c=orders&a=index&msg=" . ($ok ? "reserved_success" : "reserved_fail")
        );
    }

    public function cancel(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }

        $orderId = (int)($_POST['id'] ?? 0);
        $ok = $orderId > 0 ? OrderModel::cancel($this->pdo, $orderId) : false;

        $this->redirect(
            "index.php?c=orders&a=index&msg=" . ($ok ? "cancel_success" : "cancel_fail")
        );
    }

    public function create(): void
    {
        $customers = $this->pdo->query("SELECT id, name, city FROM customers ORDER BY name")->fetchAll();
        $warehouses = $this->pdo->query("SELECT id, name, city FROM warehouses ORDER BY name")->fetchAll();
        $products = $this->pdo->query("SELECT id, sku, name, price FROM products ORDER BY name")->fetchAll();

        $this->render('orders/create.php', [
            'title' => 'Yeni Sipariş',
            'activeNav' => 'orders',
            'customers' => $customers,
            'warehouses' => $warehouses,
            'products' => $products,
            'successMessage' => '',
            'errorMessage' => '',
        ]);
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("index.php?c=orders&a=create");
        }

        $customerId  = (int)($_POST['customer_id'] ?? 0);
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $itemsInput  = $_POST['items'] ?? [];

        $cleanItems = [];
        foreach ($itemsInput as $row) {
            $pid = (int)($row['product_id'] ?? 0);
            $qty = (int)($row['quantity'] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $cleanItems[] = ['product_id' => $pid, 'quantity' => $qty];
            }
        }

        [$ok, $msg] = OrderModel::createMulti(
            $this->pdo,
            $customerId,
            $warehouseId,
            $cleanItems
        );

        $this->redirect(
            "index.php?c=orders&a=index&msg=" . ($ok ? "create_success" : "create_fail")
        );
    }
}
