<?php

final class OrderStatus
{
    public const PENDING   = 'pending';
    public const RESERVED  = 'reserved';
    public const SHIPPED   = 'shipped';
    public const CANCELLED = 'cancelled';

    public static function canReserve(string $status): bool
    {
        return $status === self::PENDING;
    }

    public static function canShip(string $status): bool
    {
        return $status === self::RESERVED;
    }

    public static function canCancel(string $status): bool
    {
        return in_array($status, [self::PENDING, self::RESERVED], true);
    }
}
