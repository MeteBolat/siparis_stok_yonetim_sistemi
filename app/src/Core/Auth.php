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

    public static function roles(array $roles): void
    {
        if (!in_array($_SESSION['role'] ?? null, $roles, true)) {
            http_response_code(403);
            exit('Yetkisiz erişim');
        }
    }

    public static function hasRole(string $role): bool
    {
        return ($_SESSION['role'] ?? null) === $role;
    }
}