<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
                throw new RuntimeException('Sepetiniz boş.');
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
            }

            return $order->load([
                'items.cartItem.productVariant.product',
                'items.cartItem.productVariant.variantValues.variantValue.variant',
                'address',
                'cart.user',
            ]);
        });
    }

    public function completePayment(
        Order $order,
        ?string $paymentId = null,
        ?int $installment = null,
        ?string $paidPrice = null,
    ): Order {
        return DB::transaction(function () use ($order, $paymentId, $installment, $paidPrice): Order {
            $order = $order->fresh([
                'items.cartItem.productVariant.product',
                'items.cartItem.productVariant.stock',
                'cart.items',
            ]);

            if ($order === null) {
                throw new RuntimeException('Sipariş bulunamadı.');
            }

            if ($order->payment_status === PaymentStatus::Paid) {
                return $order;
            }

            if ($order->payment_status !== PaymentStatus::Pending && $order->payment_status !== PaymentStatus::Failed) {
                throw new RuntimeException('Bu sipariş için ödeme tamamlanamaz.');
            }

            foreach ($order->items as $item) {
                /** @var CartItem|null $cartItem */
                $cartItem = $item->cartItem;

                if ($cartItem === null) {
                    continue;
                }

                $this->stockService->assertReservationIsActive($cartItem);

                $this->stockService->decrementStock(
                    $cartItem->productVariant,
                    $item->quantity,
                );
            }

            $order->cart?->items()->delete();

            $paymentIdFields = $order->stripe_checkout_session_id !== null
                ? ['stripe_payment_intent_id' => $paymentId]
                : ['iyzico_payment_id' => $paymentId];

            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => OrderStatus::Processing,
                ...$paymentIdFields,
                'installment' => $installment ?? 1,
                'paid_price' => $paidPrice ?? $order->total_price,
                'paid_at' => now(),
            ]);

            return $order->fresh(['items.cartItem.productVariant.product', 'address', 'cart.user']);
        });
    }

    public function failPayment(Order $order): Order
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            throw new RuntimeException('Ödenmiş sipariş başarısız olarak işaretlenemez.');
        }

        $order->update([
            'payment_status' => PaymentStatus::Failed,
        ]);

        return $order->fresh(['items.cartItem.productVariant.product', 'address', 'cart.user']);
    }

    public function storePaymentSession(
        Order $order,
        PaymentProvider $provider,
        string $token,
        string $reference,
    ): Order {
        $data = [
            'payment_status' => PaymentStatus::Pending,
        ];

        if ($provider === PaymentProvider::Iyzico) {
            $data['iyzico_token'] = $token;
            $data['iyzico_conversation_id'] = $reference;
        }

        if ($provider === PaymentProvider::Stripe) {
            $data['stripe_checkout_session_id'] = $token;
        }

        $order->update($data);

        return $order->fresh(['items.cartItem.productVariant.product', 'address', 'cart.user']);
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
