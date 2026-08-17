<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AdminOrderService
{
    public function __construct(
        private OrderMailService $orderMailService,
        private LowStockService $lowStockService,
    ) {}

    /**
     * @return Builder<Order>
     */
    public function ordersQueryFor(User $user): Builder
    {
        $query = Order::query()->latest();

        if ($user->isVendor()) {
            $query->whereHas(
                'items.cartItem.productVariant.product',
                fn (Builder $productQuery) => $productQuery->where('user_id', $user->id),
            );
        }

        return $query;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function itemsForUser(Order $order, User $user): Collection
    {
        $order->loadMissing([
            'items.cartItem.productVariant.product',
            'items.cartItem.productVariant.variantValues.variantValue.variant',
        ]);

        if ($user->isAdmin()) {
            return $order->items;
        }

        return $order->items->filter(
            fn (OrderItem $item): bool => $item->cartItem?->productVariant?->product?->user_id === $user->id,
        )->values();
    }

    public function canViewOrder(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isVendor()) {
            return false;
        }

        return $this->itemsForUser($order, $user)->isNotEmpty();
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        if (! $order->status->canTransitionTo($status)) {
            throw new InvalidArgumentException('Bu sipariş durumu güncellenemez.');
        }

        if (in_array($status, [OrderStatus::Shipped, OrderStatus::Delivered], true)
            && $order->payment_status !== PaymentStatus::Paid) {
            throw new InvalidArgumentException('Ödenmemiş sipariş kargoya verilemez.');
        }

        $previousStatus = $order->status;

        $order->update(['status' => $status]);

        $updatedOrder = $order->fresh();

        if ($previousStatus !== $status) {
            match ($status) {
                OrderStatus::Shipped => $this->orderMailService->queueShipped($updatedOrder),
                OrderStatus::Delivered => $this->orderMailService->queueDelivered($updatedOrder),
                default => null,
            };
        }

        return $updatedOrder;
    }

    public function vendorSubtotal(Collection $items): float
    {
        return (float) $items->sum(fn (OrderItem $item): float => $item->subtotal());
    }

    /**
     * @return array{
     *     products_count: int,
     *     total_stock: int,
     *     low_stock_variants: int,
     *     low_stock_threshold: int,
     *     low_stock_alerts: list<array{
     *         product_id: int,
     *         product_name: string,
     *         variant_id: int,
     *         sku: string,
     *         quantity: int,
     *     }>,
     *     orders_count: int,
     *     items_sold: int,
     *     revenue: float,
     *     pending_cancellation_requests: int,
     *     charts: array{
     *         revenue_trend: list<array{date: string, label: string, revenue: float, orders: int}>,
     *         orders_by_status: list<array{status: string, label: string, count: int}>,
     *         top_products: list<array{name: string, revenue: float, quantity: int}>,
     *     },
     * }
     */
    public function summaryFor(User $user): array
    {
        $productsQuery = Product::query();

        if ($user->isVendor()) {
            $productsQuery->where('user_id', $user->id);
        }

        $products = $productsQuery
            ->with(['variants.stock'])
            ->get();

        $totalStock = 0;

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $totalStock += $variant->stock?->quantity ?? 0;
            }
        }

        $itemsQuery = OrderItem::query()
            ->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('payment_status', PaymentStatus::Paid));

        if ($user->isVendor()) {
            $itemsQuery->whereHas(
                'cartItem.productVariant.product',
                fn (Builder $productQuery) => $productQuery->where('user_id', $user->id),
            );
        }

        $soldItems = $itemsQuery->get(['order_id', 'quantity', 'price']);

        return [
            'products_count' => $products->count(),
            'total_stock' => $totalStock,
            'low_stock_variants' => $this->lowStockService->countFor($user),
            'low_stock_threshold' => $this->lowStockService->threshold(),
            'low_stock_alerts' => $this->lowStockService->alertsFor($user, 5),
            'orders_count' => $soldItems->pluck('order_id')->unique()->count(),
            'items_sold' => (int) $soldItems->sum('quantity'),
            'revenue' => (float) $soldItems->sum(fn (OrderItem $item): float => $item->subtotal()),
            'pending_cancellation_requests' => app(OrderCancellationService::class)->pendingCountFor($user),
            'charts' => $this->chartsFor($user),
        ];
    }

    /**
     * @return array{
     *     revenue_trend: list<array{date: string, label: string, revenue: float, orders: int}>,
     *     orders_by_status: list<array{status: string, label: string, count: int}>,
     *     top_products: list<array{name: string, revenue: float, quantity: int}>,
     * }
     */
    public function chartsFor(User $user): array
    {
        return [
            'revenue_trend' => $this->revenueTrendFor($user),
            'orders_by_status' => $this->ordersByStatusFor($user),
            'top_products' => $this->topProductsFor($user),
        ];
    }

    /**
     * @return list<array{date: string, label: string, revenue: float, orders: int}>
     */
    private function revenueTrendFor(User $user, int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $items = $this->paidOrderItemsQueryFor($user)
            ->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('paid_at', '>=', $start))
            ->with(['order:id,paid_at'])
            ->get(['id', 'order_id', 'quantity', 'price']);

        $trend = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->copy()->addDays($offset)->toDateString();
            $trend[$date] = [
                'date' => $date,
                'label' => $start->copy()->addDays($offset)->format('d.m'),
                'revenue' => 0.0,
                'orders' => 0,
            ];
        }

        $orderIdsByDate = [];

        foreach ($items as $item) {
            $paidAt = $item->order?->paid_at;

            if ($paidAt === null) {
                continue;
            }

            $date = $paidAt->toDateString();

            if (! isset($trend[$date])) {
                continue;
            }

            $trend[$date]['revenue'] += $item->subtotal();
            $orderIdsByDate[$date][$item->order_id] = true;
        }

        foreach ($orderIdsByDate as $date => $orderIds) {
            if (isset($trend[$date])) {
                $trend[$date]['orders'] = count($orderIds);
            }
        }

        return array_values($trend);
    }

    /**
     * @return list<array{status: string, label: string, count: int}>
     */
    private function ordersByStatusFor(User $user): array
    {
        return $this->ordersQueryFor($user)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->reorder()
            ->orderByDesc('total')
            ->get()
            ->map(function ($row): array {
                $status = $row->status instanceof OrderStatus
                    ? $row->status
                    : OrderStatus::from((string) $row->status);

                return [
                    'status' => $status->value,
                    'label' => $status->label(),
                    'count' => (int) $row->total,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, revenue: float, quantity: int}>
     */
    private function topProductsFor(User $user, int $limit = 5): array
    {
        $items = $this->paidOrderItemsQueryFor($user)
            ->with(['cartItem.productVariant.product:id,name'])
            ->get(['id', 'quantity', 'price', 'cart_item_id']);

        /** @var array<string, array{name: string, revenue: float, quantity: int}> $products */
        $products = [];

        foreach ($items as $item) {
            $name = $item->cartItem?->productVariant?->product?->name ?? 'Ürün';

            if (! isset($products[$name])) {
                $products[$name] = [
                    'name' => $name,
                    'revenue' => 0.0,
                    'quantity' => 0,
                ];
            }

            $products[$name]['revenue'] += $item->subtotal();
            $products[$name]['quantity'] += $item->quantity;
        }

        return collect($products)
            ->sortByDesc('revenue')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return Builder<OrderItem>
     */
    private function paidOrderItemsQueryFor(User $user): Builder
    {
        $query = OrderItem::query()->whereHas(
            'order',
            fn (Builder $orderQuery) => $orderQuery
                ->where('payment_status', PaymentStatus::Paid)
                ->whereNotNull('paid_at'),
        );

        if ($user->isVendor()) {
            $query->whereHas(
                'cartItem.productVariant.product',
                fn (Builder $productQuery) => $productQuery->where('user_id', $user->id),
            );
        }

        return $query;
    }
}
