<?php
final class OrdersController extends Controller
{
    public function index(): void
    {
        $msg = $_GET['msg'] ?? null;
        $orders = OrderModel::list($this->pdo);

        $this->render('orders/index.php', [
            'msg' => $msg,
            'orders' => $orders,
        ]);
    }

    public function view(): void
    {
        $orderId = (int)($_GET['id'] ?? 0);
        if ($orderId <= 0) {
            http_response_code(400);
            exit("Geçersiz sipariş ID");
        }

        $order = OrderModel::findHeader($this->pdo, $orderId);
        if (!$order) {
            http_response_code(404);
            exit("Sipariş bulunamadı");
        }

        $items = OrderModel::findItems($this->pdo, $orderId);

        $this->render('orders/view.php', [
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
}
