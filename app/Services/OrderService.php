<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DataTransferObjects\PaymentInitializationResult;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
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

        $initialization = $this->paymentGateway->initialize($order, $buyerIp);

        $this->repository->storePaymentSession(
            $order,
            $initialization->token,
            $initialization->conversationId,
        );

        return $initialization;
    }

    public function completePayment(Order $order, ?string $paymentId = null): Order
    {
        return $this->repository->completePayment($order, $paymentId);
    }

    public function failPayment(Order $order): Order
    {
        return $this->repository->failPayment($order);
    }
}
