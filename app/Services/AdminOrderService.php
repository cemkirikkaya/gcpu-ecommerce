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

        $order->update(['status' => $status]);

        return $order->fresh();
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
     *     orders_count: int,
     *     items_sold: int,
     *     revenue: float,
     *     pending_cancellation_requests: int,
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
        $lowStockVariants = 0;

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $quantity = $variant->stock?->quantity ?? 0;
                $totalStock += $quantity;

                if ($quantity <= 5) {
                    $lowStockVariants++;
                }
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
            'low_stock_variants' => $lowStockVariants,
            'orders_count' => $soldItems->pluck('order_id')->unique()->count(),
            'items_sold' => (int) $soldItems->sum('quantity'),
            'revenue' => (float) $soldItems->sum(fn (OrderItem $item): float => $item->subtotal()),
            'pending_cancellation_requests' => app(OrderCancellationService::class)->pendingCountFor($user),
        ];
    }
}
