<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Beklemede',
            self::Processing => 'Hazırlanıyor',
            self::Shipped => 'Kargoda',
            self::Delivered => 'Teslim Edildi',
            self::Returned => 'İade Edildi',
            self::Cancelled => 'İptal Edildi',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        if ($this === $status) {
            return true;
        }

        return match ($this) {
            self::Pending => in_array($status, [self::Processing, self::Cancelled], true),
            self::Processing => in_array($status, [self::Shipped, self::Cancelled], true),
            self::Shipped => $status === self::Delivered,
            self::Delivered => $status === self::Returned,
            self::Returned, self::Cancelled => false,
        };
    }
}
