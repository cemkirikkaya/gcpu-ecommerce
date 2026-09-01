<?php

namespace App\Enums;

enum ReturnRequestType: string
{
    case Return = 'return';
    case Exchange = 'exchange';

    public function label(): string
    {
        return match ($this) {
            self::Return => 'İade',
            self::Exchange => 'Değişim',
        };
    }
}
