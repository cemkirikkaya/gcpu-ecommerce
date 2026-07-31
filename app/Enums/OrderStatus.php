<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Beklemede',
            self::Processing => 'Hazırlanıyor',
            self::Shipped => 'Kargoda',
            self::Delivered => 'Teslim Edildi',
            self::Cancelled => 'İptal Edildi',
        };
    }
}
