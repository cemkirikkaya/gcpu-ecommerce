<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DataTransferObjects\PaymentInitializationResult;
use App\DataTransferObjects\PaymentRetrievalResult;
use App\Models\Order;
use Illuminate\Support\Str;

class FakePaymentGateway implements PaymentGateway
{
    /** @var array<string, bool> */
    private array $tokenOutcomes = [];

    public function initialize(Order $order, string $buyerIp): PaymentInitializationResult
    {
        $token = 'fake-'.Str::uuid()->toString();
        $conversationId = 'order-'.$order->id;

        $this->tokenOutcomes[$token] = true;

        return new PaymentInitializationResult(
            token: $token,
            paymentPageUrl: url('/payment/iyzico/fake/'.$token),
            conversationId: $conversationId,
        );
    }

    public function chargeDirectly(Order $order, string $buyerIp): PaymentRetrievalResult
    {
        return new PaymentRetrievalResult(
            successful: true,
            paymentId: 'fake-direct-'.Str::uuid()->toString(),
        );
    }

    public function retrieve(string $token): PaymentRetrievalResult
    {
        $successful = $this->tokenOutcomes[$token] ?? false;

        return new PaymentRetrievalResult(
            successful: $successful,
            paymentId: $successful ? 'fake-payment-'.Str::uuid()->toString() : null,
            errorMessage: $successful ? null : 'Test ödemesi başarısız.',
        );
    }

    public function markTokenSuccessful(string $token): void
    {
        $this->tokenOutcomes[$token] = true;
    }

    public function markTokenFailed(string $token): void
    {
        $this->tokenOutcomes[$token] = false;
    }
}
