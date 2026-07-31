<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function __construct(
        private StockService $stockService,
    ) {}

    public function checkout(User $user, ?int $addressId = null): Order
    {
        return DB::transaction(function () use ($user, $addressId): Order {
            $cart = $user->cart()
                ->with([
                    'items.productVariant.product',
                    'items.productVariant.stock',
                    'items.productVariant.variantValues.variantValue.variant',
                ])
                ->firstOrFail();

            if ($cart->items->isEmpty()) {
                throw new \RuntimeException('Sepetiniz boş.');
            }

            $address = $this->resolveAddress($user, $addressId);

            foreach ($cart->items as $item) {
                /** @var CartItem $item */
                $this->stockService->assertReservationIsActive($item);

                $this->stockService->assertCanReserve(
                    $item->productVariant,
                    $item->quantity,
                    $item->id,
                );
            }

            $order = Order::query()->create([
                'cart_id' => $cart->id,
                'address_id' => $address?->id,
                'total_price' => $cart->total(),
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Pending,
            ]);

            foreach ($cart->items as $item) {
                /** @var CartItem $item */
                $productVariant = $item->productVariant;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'cart_item_id' => $item->id,
                    'quantity' => $item->quantity,
                    'price' => $productVariant->product->price,
                ]);

                $this->stockService->decrementStock($productVariant, $item->quantity);
            }

            $cart->items()->delete();

            return $order->load(['items.cartItem.productVariant.product', 'address', 'cart.user']);
        });
    }

    private function resolveAddress(User $user, ?int $addressId): ?Address
    {
        if ($addressId !== null) {
            return $user->addresses()->whereKey($addressId)->firstOrFail();
        }

        return $user->defaultAddress()->first()
            ?? $user->addresses()->first();
    }
}
