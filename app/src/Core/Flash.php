<?php

final class Flash
{
    private const KEY = '__flash_message__';

    public static function set(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION[self::KEY] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    public static function has(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return isset($_SESSION[self::KEY]);
    }

    public static function get(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION[self::KEY])) {
            return [null, null];
        }

        $flash = $_SESSION[self::KEY];
        unset($_SESSION[self::KEY]); // 🔥 tek seferlik

        return [$flash['type'], $flash['message']];
    }
}
