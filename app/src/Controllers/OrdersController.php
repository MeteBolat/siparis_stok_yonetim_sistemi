<?php
final class OrdersController extends Controller
{
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
        'title' => 'Sipariş Detayı #' . $orderId,
        'activeNav' => 'orders',
        'order' => $order,
        'items' => $items,
    ]);
}


    public function ship(): void
    {
        $orderId = (int)($_GET['id'] ?? 0);
        $ok = $orderId > 0 ? OrderModel::ship($this->pdo, $orderId) : false;
        $this->redirect("index.php?c=orders&a=index&msg=" . ($ok ? "shipped_success" : "shipped_fail"));
    }

    public function cancel(): void
    {
        $orderId = (int)($_GET['id'] ?? 0);
        $ok = $orderId > 0 ? OrderModel::cancel($this->pdo, $orderId) : false;
        $this->redirect("index.php?c=orders&a=index&msg=" . ($ok ? "cancel_success" : "cancel_fail"));
    }

    public function create(): void
{
    $customers = $this->pdo->query("SELECT id, name, city FROM customers ORDER BY name ASC")->fetchAll();
    $warehouses = $this->pdo->query("SELECT id, name, city FROM warehouses ORDER BY name ASC")->fetchAll();
    $products = $this->pdo->query("SELECT id, sku, name, price FROM products ORDER BY name ASC")->fetchAll();

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

    // form verileri
    $customerId  = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    $warehouseId = isset($_POST['warehouse_id']) ? (int)$_POST['warehouse_id'] : 0;
    $itemsInput  = $_POST['items'] ?? [];

    // temizle
    $cleanItems = [];
    if (is_array($itemsInput)) {
        foreach ($itemsInput as $row) {
            $pid = isset($row['product_id']) ? (int)$row['product_id'] : 0;
            $qty = isset($row['quantity']) ? (int)$row['quantity'] : 0;
            if ($pid > 0 && $qty > 0) {
                $cleanItems[] = ['product_id' => $pid, 'quantity' => $qty];
            }
        }
    }

    // dropdown verileri (hata olursa aynı sayfayı tekrar basmak için)
    $customers = $this->pdo->query("SELECT id, name, city FROM customers ORDER BY name ASC")->fetchAll();
    $warehouses = $this->pdo->query("SELECT id, name, city FROM warehouses ORDER BY name ASC")->fetchAll();
    $products = $this->pdo->query("SELECT id, sku, name, price FROM products ORDER BY name ASC")->fetchAll();

    $successMessage = '';
    $errorMessage = '';

    if ($customerId <= 0 || $warehouseId <= 0) {
        $errorMessage = "Lütfen müşteri ve depo seçin.";
    } elseif (count($cleanItems) === 0) {
        $errorMessage = "En az bir ürün satırı ekleyin ve adetleri 1 veya üzeri girin.";
    } else {
        // asıl işlem modelde
        [$ok, $msg] = OrderModel::createMulti($this->pdo, $customerId, $warehouseId, $cleanItems);
        if ($ok) $successMessage = $msg;
        else $errorMessage = $msg;
    }

    $this->render('orders/create.php', [
    'title' => 'Yeni Sipariş',
    'activeNav' => 'orders',
    'customers' => $customers,
    'warehouses' => $warehouses,
    'products' => $products,
    'successMessage' => $successMessage,
    'errorMessage' => $errorMessage,
]);

}
    public function reserve(): void
{
    $orderId = (int)($_GET['id'] ?? 0);

    $ok = $orderId > 0 ? OrderModel::reserve($this->pdo, $orderId) : false;

    $this->redirect("index.php?c=orders&a=index&msg=" . ($ok ? "reserved_success" : "reserved_fail"));
}
}
