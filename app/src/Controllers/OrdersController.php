<?php
final class OrdersController extends Controller
{
    private function redirectWithMsg(string $msg): void
    {
    $this->redirect("index.php?c=orders&a=index&msg=$msg");
    }

    private function onlyPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }
    }

    public function index(): void
    {
        // TODO: requireLogin()
        // TODO: requireOrderAccess($orderId)

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


    public function ship(): void
{
    $this->onlyPost();

    $orderId = (int)($_POST['id'] ?? 0);

    $ok = $orderId > 0
        ? OrderModel::ship($this->pdo, $orderId)
        : false;

    if ($ok) {
        $this->redirectWithFlash(
            'success',
            'Sipariş kargoya verildi.',
            'index.php?c=orders&a=index'
        );
    } else {
        $this->redirectWithFlash(
            'error',
            'Sipariş kargoya verilemedi.',
            'index.php?c=orders&a=index'
        );
    }
}


    public function reserve(): void
{
    $this->onlyPost();

    $orderId = (int)($_POST['id'] ?? 0);

    $ok = $orderId > 0
        ? OrderModel::reserve($this->pdo, $orderId)
        : false;

    if ($ok) {
        $this->redirectWithFlash(
            'succes',
            'Sipariş başarıyla rezerve edildi.',
            'index.php?c=orders&a=index'
        );
    } else {
        $this->redirectWithFlash(
            'error',
            'Sipariş rezerve edilemedi.',
            'index.php?c=order&a=index'
        );
    }
}


    public function cancel(): void
{
    $this->onlyPost();

    $orderId = (int)($_POST['id'] ?? 0);

    $ok = $orderId > 0
        ? OrderModel::cancel($this->pdo, $orderId)
        : false;

    if ($ok) {
        $this->redirectWithFlash(
            'success',
            'Sipariş iptal edildi.',
            'index.php?c=orders&a=index'
        );
    } else {
        $this->redirectWithFlash(
            'error',
            'Sipariş iptal edilemedi.',
            'index.php?c=orders&a=index'
        );
    }
}


    public function create(): void
{
    $customers  = $this->pdo->query("SELECT id, name, city FROM customers ORDER BY name")->fetchAll();
    $warehouses = $this->pdo->query("SELECT id, name, city FROM warehouses ORDER BY name")->fetchAll();
    $products   = $this->pdo->query("SELECT id, sku, name, price FROM products ORDER BY name")->fetchAll();

    $this->render('orders/create.php', [
        'title'      => 'Yeni Sipariş',
        'activeNav'  => 'orders',
        'customers'  => $customers,
        'warehouses' => $warehouses,
        'products'   => $products,
    ]);
}


    public function store(): void
{
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
                'quantity'   => $qty,
            ];
        }
    }

    if ($customerId <= 0 || $warehouseId <= 0 || empty($cleanItems)) {
        $this->redirectWithFlash(
            'error',
            'Sipariş bilgileri eksik.',
            'index.php?c=orders&a=create'
        );
    }

    [$ok, $msg] = OrderModel::createMulti(
        $this->pdo,
        $customerId,
        $warehouseId,
        $cleanItems
    );

    if ($ok) {
        $this->redirectWithFlash(
            'success',
            'Sipariş başarıyla oluşturuldu.',
            'index.php?c=orders&a=index'
        );
    } else {
        $this->redirectWithFlash(
            'error',
            'Sipariş oluşturulamadı. Stok yetersiz olabilir.',
            'index.php?c=orders&a=create'
        );
    }
}
}
