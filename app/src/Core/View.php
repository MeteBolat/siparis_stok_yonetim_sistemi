<?php
final class View
{
    public static function render(string $viewFile, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $full = __DIR__ . '/../Views/' . ltrim($viewFile, '/');
        if (!file_exists($full)) {
            http_response_code(500);
            exit("View not found: " . htmlspecialchars($full));
        }

        require $full;
    }
}
