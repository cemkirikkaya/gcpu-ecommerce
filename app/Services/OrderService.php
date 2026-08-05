<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DataTransferObjects\InstallmentOption;
use App\DataTransferObjects\PaymentInitializationResult;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OrderService
{
    public function __construct(
        private OrderRepository $repository,
        private PaymentGateway $paymentGateway,
    ) {}

    public function checkout(User $user, ?int $addressId = null): Order
    {
        return $this->repository->checkout($user, $addressId);
    }

    public function initializePayment(Order $order, string $buyerIp): PaymentInitializationResult
    {
        if (! in_array($order->payment_status, [PaymentStatus::Pending, PaymentStatus::Failed], true)) {
            throw new RuntimeException('Bu sipariş için ödeme başlatılamaz.');
        }

        Log::info('Payment checkout session starting', [
            'order_id' => $order->id,
            'payment_status' => $order->payment_status->value,
            'total_price' => $order->total_price,
            'buyer_ip' => $buyerIp,
            'mode' => 'checkout_form',
        ]);

        $initialization = $this->paymentGateway->initialize($order, $buyerIp);

        $this->repository->storePaymentSession(
            $order,
            $initialization->token,
            $initialization->conversationId,
        );

        Log::info('Payment checkout session initialized', [
            'order_id' => $order->id,
            'conversation_id' => $initialization->conversationId,
            'token' => $initialization->token,
        ]);

        return $initialization;
    }

    public function chargePaymentDirectly(Order $order, string $buyerIp, int $installment = 1): Order
    {
        if (! in_array($order->payment_status, [PaymentStatus::Pending, PaymentStatus::Failed], true)) {
            throw new RuntimeException('Bu sipariş için ödeme başlatılamaz.');
        }

        Log::info('Payment direct charge starting', [
            'order_id' => $order->id,
            'payment_status' => $order->payment_status->value,
            'total_price' => $order->total_price,
            'buyer_ip' => $buyerIp,
            'installment' => $installment,
            'mode' => 'direct',
        ]);

        $result = $this->paymentGateway->chargeDirectly($order, $buyerIp, $installment);

        if (! $result->successful) {
            Log::warning('Payment direct charge failed', [
                'order_id' => $order->id,
                'error' => $result->errorMessage,
            ]);

            $this->failPayment($order);

            throw new RuntimeException($result->errorMessage ?? 'Ödeme başarısız.');
        }

        Log::info('Payment direct charge succeeded', [
            'order_id' => $order->id,
            'payment_id' => $result->paymentId,
            'installment' => $installment,
        ]);

        return $this->completePayment(
            $order,
            $result->paymentId,
            $result->installment ?? $installment,
            $result->paidPrice,
        );
    }

    /**
     * @return list<InstallmentOption>
     */
    public function getInstallmentOptions(float $totalPrice): array
    {
        $price = number_format($totalPrice, 2, '.', '');
        $cardNumber = (string) config('iyzico.test_card.number');
        $binNumber = substr($cardNumber, 0, 6);

        if ($binNumber === '') {
            throw new RuntimeException('Taksit seçenekleri için kart BIN bilgisi tanımlı değil.');
        }

        return $this->paymentGateway->getInstallmentOptions($price, $binNumber);
    }

    public function completePayment(
        Order $order,
        ?string $paymentId = null,
        ?int $installment = null,
        ?string $paidPrice = null,
    ): Order {
        $completedOrder = $this->repository->completePayment(
            $order,
            $paymentId,
            $installment,
            $paidPrice,
        );

        Log::info('Payment completed', [
            'order_id' => $completedOrder->id,
            'payment_id' => $paymentId,
            'installment' => $completedOrder->installment,
            'paid_price' => $completedOrder->paid_price,
            'payment_status' => $completedOrder->payment_status->value,
            'order_status' => $completedOrder->status->value,
        ]);

        return $completedOrder;
    }

    public function failPayment(Order $order): Order
    {
        $failedOrder = $this->repository->failPayment($order);

        Log::warning('Payment failed', [
            'order_id' => $failedOrder->id,
            'payment_status' => $failedOrder->payment_status->value,
        ]);

        return $failedOrder;
    }
}
