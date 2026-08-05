<?php

namespace App\Contracts;

use App\DataTransferObjects\InstallmentOption;
use App\DataTransferObjects\PaymentInitializationResult;
use App\DataTransferObjects\PaymentRetrievalResult;
use App\Models\Order;

interface PaymentGateway
{
    public function initialize(Order $order, string $buyerIp): PaymentInitializationResult;

    public function retrieve(string $token): PaymentRetrievalResult;

    public function chargeDirectly(Order $order, string $buyerIp, int $installment = 1): PaymentRetrievalResult;

    /**
     * @return list<InstallmentOption>
     */
    public function getInstallmentOptions(string $price, string $binNumber): array;
}
