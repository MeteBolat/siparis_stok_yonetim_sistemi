<?php

class Auth
{
    public static function check(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?c=auth&a=login");
            exit;
        }
    }

    public static function role(string $role): void
    {
        if (($_SESSION['role'] ?? null) !== $role) {
            http_response_code(403);
            exit('Yetkisiz erişim');
        }
    }
}