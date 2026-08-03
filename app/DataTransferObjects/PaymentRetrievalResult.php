<?php

namespace App\DataTransferObjects;

readonly class PaymentRetrievalResult
{
    public function __construct(
        public bool $successful,
        public ?string $paymentId = null,
        public ?string $errorMessage = null,
    ) {}
}
