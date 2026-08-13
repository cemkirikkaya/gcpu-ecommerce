<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DataTransferObjects\InstallmentOption;
use App\DataTransferObjects\PaymentInitializationResult;
use App\DataTransferObjects\PaymentRefundResult;
use App\DataTransferObjects\PaymentRetrievalResult;
use App\Models\Order;
use Illuminate\Support\Str;

class FakePaymentGateway implements PaymentGateway
{
    /** @var array<string, bool> */
    private array $tokenOutcomes = [];

    public int $lastInstallment = 1;

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

    public function chargeDirectly(Order $order, string $buyerIp, int $installment = 1): PaymentRetrievalResult
    {
        $this->lastInstallment = $installment;

        return new PaymentRetrievalResult(
            successful: true,
            paymentId: 'fake-direct-'.Str::uuid()->toString(),
            installment: $installment,
            paidPrice: null,
            iyzicoPaymentItems: [[
                'payment_transaction_id' => 'fake-txn-'.Str::uuid()->toString(),
                'price' => number_format((float) $order->total_price, 2, '.', ''),
            ]],
        );
    }

    public function retrieve(string $token): PaymentRetrievalResult
    {
        $successful = $this->tokenOutcomes[$token] ?? false;

        return new PaymentRetrievalResult(
            successful: $successful,
            paymentId: $successful ? 'fake-payment-'.Str::uuid()->toString() : null,
            errorMessage: $successful ? null : 'Test ödemesi başarısız.',
            iyzicoPaymentItems: $successful ? [[
                'payment_transaction_id' => 'fake-txn-'.Str::uuid()->toString(),
                'price' => '100.00',
            ]] : [],
        );
    }

    public function refund(Order $order): PaymentRefundResult
    {
        return new PaymentRefundResult(
            successful: true,
            refundReference: 'fake-refund-'.Str::uuid()->toString(),
        );
    }

    /**
     * @return list<InstallmentOption>
     */
    public function getInstallmentOptions(string $price, string $binNumber): array
    {
        $total = (float) $price;

        return collect([1, 2, 3, 6, 9])
            ->map(function (int $number) use ($total): InstallmentOption {
                $monthly = $number === 1 ? $total : round($total / $number, 2);

                return new InstallmentOption(
                    number: $number,
                    monthlyPrice: number_format($monthly, 2, '.', ''),
                    totalPrice: number_format($total, 2, '.', ''),
                );
            })
            ->all();
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
