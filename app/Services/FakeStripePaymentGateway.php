<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DataTransferObjects\InstallmentOption;
use App\DataTransferObjects\PaymentInitializationResult;
use App\DataTransferObjects\PaymentRefundResult;
use App\DataTransferObjects\PaymentRetrievalResult;
use App\Models\Order;
use App\Support\StripeCheckoutData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class FakeStripePaymentGateway implements PaymentGateway
{
    private function cacheKey(string $sessionId): string
    {
        return 'fake-stripe-session:'.$sessionId;
    }

    public function initialize(Order $order, string $buyerIp): PaymentInitializationResult
    {
        $sessionId = 'fake-stripe-'.Str::uuid()->toString();

        Cache::put($this->cacheKey($sessionId), true, now()->addHours(2));

        return new PaymentInitializationResult(
            token: $sessionId,
            paymentPageUrl: url('/payment/stripe/fake/'.$sessionId),
            conversationId: $sessionId,
        );
    }

    public function retrieve(string $token): PaymentRetrievalResult
    {
        if (! Cache::has($this->cacheKey($token))) {
            return new PaymentRetrievalResult(
                successful: false,
                errorMessage: 'Test ödemesi başarısız.',
            );
        }

        $successful = (bool) Cache::get($this->cacheKey($token));

        return new PaymentRetrievalResult(
            successful: $successful,
            paymentId: $successful ? 'fake-pi-'.Str::uuid()->toString() : null,
            errorMessage: $successful ? null : 'Test ödemesi başarısız.',
        );
    }

    public function chargeDirectly(Order $order, string $buyerIp, int $installment = 1): PaymentRetrievalResult
    {
        throw new RuntimeException('Stripe doğrudan ödeme desteklemiyor.');
    }

    public function refund(Order $order, ?float $amount = null): PaymentRefundResult
    {
        return new PaymentRefundResult(
            successful: true,
            refundReference: 'fake-stripe-refund-'.Str::uuid()->toString(),
        );
    }

    /**
     * @return list<InstallmentOption>
     */
    public function getInstallmentOptions(string $price, string $binNumber): array
    {
        throw new RuntimeException('Stripe taksit sorgusu desteklemiyor.');
    }

    public function markSessionFailed(string $sessionId): void
    {
        Cache::put($this->cacheKey($sessionId), false, now()->addHours(2));
    }

    public function paidPriceForOrder(Order $order): string
    {
        return StripeCheckoutData::formatAmount(
            StripeCheckoutData::amountInMinorUnits((float) $order->total_price),
        );
    }
}
