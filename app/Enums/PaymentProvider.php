<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case Iyzico = 'iyzico';
    case Stripe = 'stripe';

    public function label(): string
    {
        return match ($this) {
            self::Iyzico => 'Iyzico',
            self::Stripe => 'Stripe',
        };
    }
}
