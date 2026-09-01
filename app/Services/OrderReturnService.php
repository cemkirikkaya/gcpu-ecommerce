<?php

namespace App\Services;

use App\DataTransferObjects\ShipmentCreationResult;
use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\ReturnRequestStatus;
use App\Enums\ReturnRequestType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturnItem;
use App\Models\OrderReturnRequest;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\OrderPaymentLineAmounts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OrderReturnService
{
    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private ShippingGatewayFactory $shippingGatewayFactory,
        private StockService $stockService,
        private AdminOrderService $adminOrderService,
        private OrderMailService $orderMailService,
        private GeliverTrackingUrlResolver $trackingUrlResolver,
        private GeliverTrackingStatusMapper $trackingStatusMapper,
    ) {}

    /**
     * @param  list<array{order_item_id: int, quantity: int, replacement_product_variant_id?: int|null}>  $items
     */
    public function request(Order $order, User $customer, ReturnRequestType $type, string $message, array $items): OrderReturnRequest
    {
        if ($order->cart?->user_id !== $customer->id) {
            throw new InvalidArgumentException('Bu sipariş size ait değil.');
        }

        if (! in_array($order->payment_status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true)) {
            throw new InvalidArgumentException('Yalnızca ödenmiş siparişler için iade veya değişim talebi oluşturulabilir.');
        }

        if ($order->status !== OrderStatus::Delivered) {
            throw new InvalidArgumentException('İade ve değişim yalnızca teslim edilmiş siparişler için yapılabilir.');
        }

        if (! $this->isWithinReturnWindow($order)) {
            throw new InvalidArgumentException(
                'İade süresi dolmuş. Teslimattan sonra '.config('shop.return_window_days').' gün içinde talep oluşturabilirsiniz.',
            );
        }

        $normalizedItems = $this->normalizeRequestedItems($order, $type, $items);

        return DB::transaction(function () use ($order, $customer, $type, $message, $normalizedItems): OrderReturnRequest {
            $returnRequest = OrderReturnRequest::query()->create([
                'order_id' => $order->id,
                'user_id' => $customer->id,
                'type' => $type,
                'message' => $message,
                'status' => ReturnRequestStatus::Pending,
            ]);

            foreach ($normalizedItems as $item) {
                $returnRequest->items()->create($item);
            }

            return $returnRequest->fresh([
                'items.orderItem.cartItem.productVariant.product.variants.variantValues.variantValue.variant',
                'items.replacementProductVariant',
                'user',
            ]);
        });
    }

    /**
     * @return Builder<OrderReturnRequest>
     */
    public function requestsQueryFor(User $user): Builder
    {
        $query = OrderReturnRequest::query()
            ->with([
                'order.cart.user',
                'order.items.cartItem.productVariant.product',
                'items.orderItem.cartItem.productVariant.product',
                'items.replacementProductVariant',
                'user',
            ])
            ->latest();

        if ($user->isVendor()) {
            $query->whereHas(
                'items.orderItem.cartItem.productVariant.product',
                fn (Builder $productQuery) => $productQuery->where('user_id', $user->id),
            );
        }

        return $query;
    }

    /**
     * @return Collection<int, OrderReturnRequest>
     */
    public function pendingRequestsFor(User $user, int $limit = 10): Collection
    {
        return $this->requestsQueryFor($user)
            ->where('status', ReturnRequestStatus::Pending)
            ->limit($limit)
            ->get();
    }

    public function pendingCountFor(User $user): int
    {
        return $this->requestsQueryFor($user)
            ->where('status', ReturnRequestStatus::Pending)
            ->count();
    }

    public function canViewRequest(User $user, OrderReturnRequest $returnRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isVendor()) {
            return $this->adminOrderService->canViewOrder($user, $returnRequest->order);
        }

        return $returnRequest->user_id === $user->id;
    }

    public function approve(OrderReturnRequest $returnRequest, User $admin, ?string $adminNote = null): OrderReturnRequest
    {
        if (! $admin->isAdmin()) {
            throw new InvalidArgumentException('Yalnızca yöneticiler iade talebini onaylayabilir.');
        }

        if (! $returnRequest->isPending()) {
            throw new InvalidArgumentException('Bu iade talebi zaten işlenmiş.');
        }

        $order = $returnRequest->order()->with(['address', 'cart.user'])->firstOrFail();

        if ($order->address === null) {
            throw new InvalidArgumentException('İade kargo etiketi için sipariş teslimat adresi bulunamadı.');
        }

        try {
            $shipment = $this->shippingGatewayFactory->make()->createReturnShipment($order);
        } catch (RuntimeException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), previous: $exception);
        }

        $returnRequest->update([
            'status' => ReturnRequestStatus::Approved,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_note' => $adminNote,
            'geliver_return_shipment_id' => $shipment->shipmentId,
            'return_tracking_number' => $shipment->trackingNumber,
            'return_tracking_url' => $this->trackingUrlResolver->resolve(
                $shipment->trackingUrl,
                $shipment->shipmentId,
            ),
            'return_label_url' => $shipment->labelUrl,
        ]);

        $freshRequest = $this->freshRequest($returnRequest);

        $this->orderMailService->queueReturnApproved($freshRequest);

        return $freshRequest;
    }

    public function reject(OrderReturnRequest $returnRequest, User $admin, ?string $adminNote = null): OrderReturnRequest
    {
        if (! $admin->isAdmin()) {
            throw new InvalidArgumentException('Yalnızca yöneticiler iade talebini reddedebilir.');
        }

        if (! $returnRequest->isPending()) {
            throw new InvalidArgumentException('Bu iade talebi zaten işlenmiş.');
        }

        $returnRequest->update([
            'status' => ReturnRequestStatus::Rejected,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_note' => $adminNote,
        ]);

        $freshRequest = $this->freshRequest($returnRequest);

        $this->orderMailService->queueReturnRejected($freshRequest);

        return $freshRequest;
    }

    public function receive(OrderReturnRequest $returnRequest, User $admin): OrderReturnRequest
    {
        if (! $admin->isAdmin()) {
            throw new InvalidArgumentException('Yalnızca yöneticiler iade teslimini işleyebilir.');
        }

        return $this->complete($returnRequest);
    }

    /**
     * @param  array<string, mixed>  $shipmentData
     */
    public function syncReturnShipmentFromWebhook(OrderReturnRequest $returnRequest, array $shipmentData): OrderReturnRequest
    {
        $trackingNumber = isset($shipmentData['trackingNumber']) ? (string) $shipmentData['trackingNumber'] : null;
        $trackingUrl = isset($shipmentData['trackingUrl']) ? (string) $shipmentData['trackingUrl'] : null;
        $shipmentId = isset($shipmentData['id']) ? (string) $shipmentData['id'] : $returnRequest->geliver_return_shipment_id;

        $attributes = [];

        if (filled($trackingNumber)) {
            $attributes['return_tracking_number'] = $trackingNumber;
        }

        $resolvedTrackingUrl = $this->trackingUrlResolver->resolve($trackingUrl, $shipmentId);

        if (filled($resolvedTrackingUrl)) {
            $attributes['return_tracking_url'] = $resolvedTrackingUrl;
        }

        if ($attributes !== []) {
            $returnRequest->update($attributes);
        }

        $returnRequest = $returnRequest->fresh() ?? $returnRequest;

        if (! $returnRequest->isApproved()) {
            return $returnRequest;
        }

        if ($this->trackingStatusMapper->resolveOrderStatus($shipmentData) !== OrderStatus::Delivered) {
            return $returnRequest;
        }

        return $this->complete($returnRequest);
    }

    public function returnableQuantityFor(Order $order, OrderItem $item): int
    {
        return max(0, $item->quantity - $this->committedQuantityFor($order, $item->id));
    }

    /**
     * @return array<int, int>
     */
    public function committedQuantitiesByOrderItemId(Order $order): array
    {
        if ($order->relationLoaded('returnRequests')) {
            $quantities = [];

            foreach ($order->returnRequests as $returnRequest) {
                if (! $returnRequest->status->locksQuantity()) {
                    continue;
                }

                $items = $returnRequest->relationLoaded('items')
                    ? $returnRequest->items
                    : $returnRequest->items()->get();

                foreach ($items as $returnItem) {
                    $quantities[$returnItem->order_item_id] = ($quantities[$returnItem->order_item_id] ?? 0)
                        + $returnItem->quantity;
                }
            }

            return $quantities;
        }

        return OrderReturnItem::query()
            ->whereHas(
                'returnRequest',
                fn (Builder $query) => $query
                    ->where('order_id', $order->id)
                    ->whereIn('status', [
                        ReturnRequestStatus::Pending,
                        ReturnRequestStatus::Approved,
                        ReturnRequestStatus::Completed,
                    ]),
            )
            ->get(['order_item_id', 'quantity'])
            ->groupBy('order_item_id')
            ->map(fn (Collection $items): int => (int) $items->sum('quantity'))
            ->all();
    }

    private function complete(OrderReturnRequest $returnRequest): OrderReturnRequest
    {
        return DB::transaction(function () use ($returnRequest): OrderReturnRequest {
            /** @var OrderReturnRequest $lockedRequest */
            $lockedRequest = OrderReturnRequest::query()
                ->whereKey($returnRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedRequest->isApproved()) {
                throw new InvalidArgumentException('Yalnızca onaylanmış iade talepleri teslim alınabilir.');
            }

            $order = $lockedRequest->order()->lockForUpdate()->firstOrFail();
            $lockedRequest->load([
                'items.orderItem.cartItem.productVariant.stock',
                'items.orderItem.cartItem.productVariant.product.variants.stock',
                'items.replacementProductVariant.stock',
            ]);

            $this->restockReturnedItems($lockedRequest);

            $attributes = [
                'status' => ReturnRequestStatus::Completed,
                'received_at' => now(),
                'completed_at' => now(),
            ];

            if ($lockedRequest->isReturn()) {
                $refundAmount = $this->refundAmountFor($order, $lockedRequest->items);
                $refundResult = $this->gatewayFactory
                    ->make($this->resolvePaymentProvider($order))
                    ->refund($order, $refundAmount);

                if (! $refundResult->successful) {
                    throw new RuntimeException($refundResult->errorMessage ?? 'İade işlemi başarısız.');
                }

                $attributes['refund_reference'] = $refundResult->refundReference;
                $attributes['refund_amount'] = $refundAmount;

                $this->updateOrderAfterReturn($order, $lockedRequest);
            }

            if ($lockedRequest->isExchange()) {
                $this->decrementReplacementStock($lockedRequest);
                $exchangeShipment = $this->createExchangeShipment($order);

                $attributes['geliver_exchange_shipment_id'] = $exchangeShipment->shipmentId;
                $attributes['exchange_tracking_number'] = $exchangeShipment->trackingNumber;
                $attributes['exchange_tracking_url'] = $this->trackingUrlResolver->resolve(
                    $exchangeShipment->trackingUrl,
                    $exchangeShipment->shipmentId,
                );
            }

            $lockedRequest->update($attributes);

            $freshRequest = $this->freshRequest($lockedRequest);

            $this->orderMailService->queueReturnCompleted($freshRequest);

            return $freshRequest;
        });
    }

    private function restockReturnedItems(OrderReturnRequest $returnRequest): void
    {
        foreach ($returnRequest->items as $returnItem) {
            $variant = $returnItem->orderItem?->cartItem?->productVariant;

            if ($variant === null) {
                continue;
            }

            $this->stockService->incrementStock($variant, $returnItem->quantity);
        }
    }

    private function decrementReplacementStock(OrderReturnRequest $returnRequest): void
    {
        foreach ($returnRequest->items as $returnItem) {
            $replacement = $returnItem->replacementProductVariant
                ?? $returnItem->orderItem?->cartItem?->productVariant;

            if ($replacement === null) {
                throw new InvalidArgumentException('Değişim için ürün varyantı bulunamadı.');
            }

            $this->stockService->assertCanReserve($replacement, $returnItem->quantity);
            $this->stockService->decrementStock($replacement, $returnItem->quantity);
        }
    }

    private function createExchangeShipment(Order $order): ShipmentCreationResult
    {
        $order->loadMissing(['address', 'cart.user']);

        try {
            return $this->shippingGatewayFactory->make()->createShipment($order);
        } catch (RuntimeException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), previous: $exception);
        }
    }

    private function updateOrderAfterReturn(Order $order, OrderReturnRequest $returnRequest): void
    {
        $order->loadMissing(['items', 'returnRequests.items']);

        $fullyReturned = $this->orderIsFullyReturned($order, $returnRequest);

        $order->update([
            'payment_status' => $fullyReturned ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded,
            'status' => $fullyReturned ? OrderStatus::Returned : $order->status,
        ]);
    }

    private function orderIsFullyReturned(Order $order, OrderReturnRequest $currentRequest): bool
    {
        $committed = $this->committedQuantitiesByOrderItemId($order);

        foreach ($currentRequest->items as $returnItem) {
            $committed[$returnItem->order_item_id] = ($committed[$returnItem->order_item_id] ?? 0);
        }

        foreach ($order->items as $item) {
            if (($committed[$item->id] ?? 0) < $item->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, OrderReturnItem>  $returnItems
     */
    private function refundAmountFor(Order $order, Collection $returnItems): float
    {
        $order->loadMissing('items');
        $lineAmounts = OrderPaymentLineAmounts::forOrder($order);
        $itemIndexById = $order->items->values()->pluck('id')->flip();
        $amount = 0.0;

        foreach ($returnItems as $returnItem) {
            $orderItem = $returnItem->orderItem;

            if ($orderItem === null || $orderItem->quantity <= 0) {
                continue;
            }

            $index = $itemIndexById[$orderItem->id] ?? null;
            $lineAmount = $index !== null
                ? ($lineAmounts[$index] ?? $orderItem->subtotal())
                : $orderItem->subtotal();

            $amount += $lineAmount * ($returnItem->quantity / $orderItem->quantity);
        }

        return round($amount, 2);
    }

    /**
     * @param  list<array{order_item_id: int, quantity: int, replacement_product_variant_id?: int|null}>  $items
     * @return list<array{order_item_id: int, quantity: int, replacement_product_variant_id: int|null}>
     */
    private function normalizeRequestedItems(Order $order, ReturnRequestType $type, array $items): array
    {
        $order->loadMissing([
            'items.cartItem.productVariant.product.variants',
            'returnRequests.items',
        ]);

        $itemsById = $order->items->keyBy('id');
        $normalized = [];
        $requestedByItemId = [];

        foreach ($items as $item) {
            $orderItemId = (int) $item['order_item_id'];
            $quantity = (int) $item['quantity'];

            /** @var OrderItem|null $orderItem */
            $orderItem = $itemsById->get($orderItemId);

            if ($orderItem === null) {
                throw new InvalidArgumentException('Seçilen ürün bu siparişe ait değil.');
            }

            $requestedByItemId[$orderItemId] = ($requestedByItemId[$orderItemId] ?? 0) + $quantity;
            $returnable = $this->returnableQuantityFor($order, $orderItem);

            if ($requestedByItemId[$orderItemId] > $returnable) {
                throw new InvalidArgumentException('İade edilebilecek adetten fazla ürün seçildi.');
            }

            $replacementId = $type === ReturnRequestType::Exchange
                ? $this->resolveReplacementVariantId($orderItem, $item['replacement_product_variant_id'] ?? null)
                : null;

            $normalized[] = [
                'order_item_id' => $orderItemId,
                'quantity' => $quantity,
                'replacement_product_variant_id' => $replacementId,
            ];
        }

        if ($normalized === []) {
            throw new InvalidArgumentException('En az bir ürün seçmelisiniz.');
        }

        return $normalized;
    }

    private function resolveReplacementVariantId(OrderItem $orderItem, mixed $replacementId): int
    {
        $original = $orderItem->cartItem?->productVariant;

        if ($original === null) {
            throw new InvalidArgumentException('Değişim için ürün varyantı bulunamadı.');
        }

        if ($replacementId === null || $replacementId === '') {
            return $original->id;
        }

        $replacementId = (int) $replacementId;
        $product = $original->product;
        $product?->loadMissing('variants');

        $belongsToProduct = $product?->variants->contains(
            fn (ProductVariant $variant): bool => $variant->id === $replacementId,
        );

        if (! $belongsToProduct) {
            throw new InvalidArgumentException('Değişim yalnızca aynı ürünün başka bir varyantı ile yapılabilir.');
        }

        return $replacementId;
    }

    private function committedQuantityFor(Order $order, int $orderItemId): int
    {
        return $this->committedQuantitiesByOrderItemId($order)[$orderItemId] ?? 0;
    }

    private function isWithinReturnWindow(Order $order): bool
    {
        $days = max(1, (int) config('shop.return_window_days', 14));
        $deliveredAt = $order->delivered_at ?? $order->updated_at;

        if ($deliveredAt === null) {
            return true;
        }

        return $deliveredAt->gte(now()->subDays($days));
    }

    private function resolvePaymentProvider(Order $order): PaymentProvider
    {
        return match ($order->paymentProvider()) {
            'stripe' => PaymentProvider::Stripe,
            'iyzico' => PaymentProvider::Iyzico,
            default => PaymentProvider::Iyzico,
        };
    }

    private function freshRequest(OrderReturnRequest $returnRequest): OrderReturnRequest
    {
        return $returnRequest->fresh([
            'order.cart.user',
            'order.items.cartItem.productVariant.product',
            'items.orderItem.cartItem.productVariant.product',
            'items.replacementProductVariant',
            'user',
            'reviewer',
        ]);
    }
}
