<?php
final class DashboardController extends Controller
{
    public function index(): void
    {
        // Şimdilik orders’a yönlendir, sonra dashboard’u yaparız
        $this->redirect("index.php?c=orders&a=index");
    }
}
