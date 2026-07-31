<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ödeme Bekleniyor',
            self::Paid => 'Ödendi',
            self::Failed => 'Başarısız',
            self::Refunded => 'İade Edildi',
        };
    }
}
