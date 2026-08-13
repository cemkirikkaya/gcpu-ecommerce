<?php

namespace App\Services;

use App\Enums\CancellationRequestStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OrderCancellationService
{
    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private StockService $stockService,
        private AdminOrderService $adminOrderService,
    ) {}

    public function request(Order $order, User $customer, string $message): OrderCancellationRequest
    {
        if ($order->cart?->user_id !== $customer->id) {
            throw new InvalidArgumentException('Bu sipariş size ait değil.');
        }

        if ($order->payment_status !== PaymentStatus::Paid) {
            throw new InvalidArgumentException('Yalnızca ödenmiş siparişler için iptal talebi oluşturulabilir.');
        }

        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Processing], true)) {
            throw new InvalidArgumentException('Bu aşamadaki sipariş için iptal talebi oluşturulamaz.');
        }

        if ($order->cancellationRequests()->where('status', CancellationRequestStatus::Pending)->exists()) {
            throw new InvalidArgumentException('Bu sipariş için zaten bekleyen bir iptal talebi var.');
        }

        if ($order->cancellationRequests()->where('status', CancellationRequestStatus::Approved)->exists()) {
            throw new InvalidArgumentException('Bu sipariş zaten iptal edilmiş.');
        }

        return OrderCancellationRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'message' => $message,
            'status' => CancellationRequestStatus::Pending,
        ]);
    }

    /**
     * @return Builder<OrderCancellationRequest>
     */
    public function requestsQueryFor(User $user): Builder
    {
        $query = OrderCancellationRequest::query()
            ->with([
                'order.cart.user',
                'order.items.cartItem.productVariant.product',
                'user',
            ])
            ->latest();

        if ($user->isVendor()) {
            $query->whereHas(
                'order.items.cartItem.productVariant.product',
                fn (Builder $productQuery) => $productQuery->where('user_id', $user->id),
            );
        }

        return $query;
    }

    /**
     * @return Collection<int, OrderCancellationRequest>
     */
    public function pendingRequestsFor(User $user, int $limit = 10): Collection
    {
        return $this->requestsQueryFor($user)
            ->where('status', CancellationRequestStatus::Pending)
            ->limit($limit)
            ->get();
    }

    public function pendingCountFor(User $user): int
    {
        return $this->requestsQueryFor($user)
            ->where('status', CancellationRequestStatus::Pending)
            ->count();
    }

    public function canViewRequest(User $user, OrderCancellationRequest $cancellationRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isVendor()) {
            return $this->adminOrderService->canViewOrder($user, $cancellationRequest->order);
        }

        return $cancellationRequest->user_id === $user->id;
    }

    public function approve(OrderCancellationRequest $cancellationRequest, User $admin, ?string $adminNote = null): OrderCancellationRequest
    {
        if (! $admin->isAdmin()) {
            throw new InvalidArgumentException('Yalnızca yöneticiler iptal talebini onaylayabilir.');
        }

        if (! $cancellationRequest->isPending()) {
            throw new InvalidArgumentException('Bu iptal talebi zaten işlenmiş.');
        }

        return DB::transaction(function () use ($cancellationRequest, $admin, $adminNote): OrderCancellationRequest {
            $order = $cancellationRequest->order()->lockForUpdate()->firstOrFail();

            if ($order->payment_status !== PaymentStatus::Paid) {
                throw new InvalidArgumentException('Yalnızca ödenmiş siparişler iade edilebilir.');
            }

            if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Processing, OrderStatus::Shipped], true)) {
                throw new InvalidArgumentException('Bu sipariş durumu iade için uygun değil.');
            }

            $provider = $this->resolvePaymentProvider($order);
            $refundResult = $this->gatewayFactory->make($provider)->refund($order);

            if (! $refundResult->successful) {
                throw new RuntimeException($refundResult->errorMessage ?? 'İade işlemi başarısız.');
            }

            $order->load([
                'items.cartItem.productVariant.stock',
            ]);

            foreach ($order->items as $item) {
                $variant = $item->cartItem?->productVariant;

                if ($variant === null) {
                    continue;
                }

                $this->stockService->incrementStock($variant, $item->quantity);
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'payment_status' => PaymentStatus::Refunded,
            ]);

            $cancellationRequest->update([
                'status' => CancellationRequestStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_note' => $adminNote,
                'refund_reference' => $refundResult->refundReference,
            ]);

            return $cancellationRequest->fresh([
                'order.cart.user',
                'order.items.cartItem.productVariant.product',
                'user',
                'reviewer',
            ]);
        });
    }

    public function reject(OrderCancellationRequest $cancellationRequest, User $admin, ?string $adminNote = null): OrderCancellationRequest
    {
        if (! $admin->isAdmin()) {
            throw new InvalidArgumentException('Yalnızca yöneticiler iptal talebini reddedebilir.');
        }

        if (! $cancellationRequest->isPending()) {
            throw new InvalidArgumentException('Bu iptal talebi zaten işlenmiş.');
        }

        $cancellationRequest->update([
            'status' => CancellationRequestStatus::Rejected,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_note' => $adminNote,
        ]);

        return $cancellationRequest->fresh([
            'order.cart.user',
            'order.items.cartItem.productVariant.product',
            'user',
            'reviewer',
        ]);
    }

    private function resolvePaymentProvider(Order $order): PaymentProvider
    {
        return match ($order->paymentProvider()) {
            'stripe' => PaymentProvider::Stripe,
            'iyzico' => PaymentProvider::Iyzico,
            default => PaymentProvider::Iyzico,
        };
    }
}
