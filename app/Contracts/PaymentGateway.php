<?php

namespace App\Contracts;

use App\DataTransferObjects\PaymentInitializationResult;
use App\DataTransferObjects\PaymentRetrievalResult;
use App\Models\Order;

interface PaymentGateway
{
    public function initialize(Order $order, string $buyerIp): PaymentInitializationResult;

    public function retrieve(string $token): PaymentRetrievalResult;

    public function chargeDirectly(Order $order, string $buyerIp): PaymentRetrievalResult;
}
