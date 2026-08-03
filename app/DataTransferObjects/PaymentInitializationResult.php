<?php

namespace App\DataTransferObjects;

readonly class PaymentInitializationResult
{
    public function __construct(
        public string $token,
        public string $paymentPageUrl,
        public string $conversationId,
    ) {}
}
