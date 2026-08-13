<?php

namespace App\DataTransferObjects;

class PaymentRefundResult
{
    public function __construct(
        public bool $successful,
        public ?string $refundReference = null,
        public ?string $errorMessage = null,
    ) {}
}
