<?php

namespace App\DataTransferObjects;

readonly class PaymentRetrievalResult
{
    /**
     * @param  list<array{payment_transaction_id: string, price: string}>  $iyzicoPaymentItems
     */
    public function __construct(
        public bool $successful,
        public ?string $paymentId = null,
        public ?string $errorMessage = null,
        public ?int $installment = null,
        public ?string $paidPrice = null,
        public array $iyzicoPaymentItems = [],
    ) {}
}
