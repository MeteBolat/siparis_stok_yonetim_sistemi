<?php

final class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewsDir = realpath(__DIR__ . '/../Views');
        if ($viewsDir === false) {
            http_response_code(500);
            exit('Views klasörü bulunamadı: ' . __DIR__ . '/../Views');
        }

        $header   = $viewsDir . '/layout/header.php';
        $footer   = $viewsDir . '/layout/footer.php';
        $viewFile = $viewsDir . '/' . ltrim($view, '/');

        if (!is_file($header))  { http_response_code(500); exit('Header yok: ' . $header); }
        if (!is_file($footer))  { http_response_code(500); exit('Footer yok: ' . $footer); }
        if (!is_file($viewFile)){ http_response_code(500); exit('View yok: ' . $viewFile); }

        require $header;
        require $viewFile;
        require $footer;
    }
}
