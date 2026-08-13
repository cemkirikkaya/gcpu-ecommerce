<?php

namespace App\Enums;

enum CancellationRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'İnceleniyor',
            self::Approved => 'Onaylandı',
            self::Rejected => 'Reddedildi',
        };
    }
}
