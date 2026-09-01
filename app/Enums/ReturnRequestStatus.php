<?php

namespace App\Enums;

enum ReturnRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'İnceleniyor',
            self::Approved => 'Etiket hazır',
            self::Rejected => 'Reddedildi',
            self::Completed => 'Tamamlandı',
        };
    }

    public function locksQuantity(): bool
    {
        return in_array($this, [self::Pending, self::Approved, self::Completed], true);
    }
}
