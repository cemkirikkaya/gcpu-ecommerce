<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CouponService;
use App\Services\StockService;

class CartRepository
{
    public function __construct(
        private StockService $stockService,
        private CouponService $couponService,
    ) {}

    public function getOrCreate(User $user): Cart
    {
        return Cart::query()->firstOrCreate([
            'user_id' => $user->id,
        ]);
    }

    public function getWithItems(User $user): Cart
    {
        return Cart::query()
            ->with([
                'coupon',
                'items.productVariant.product',
                'items.productVariant.variantValues.variantValue.variant',
                'items.productVariant.stock',
            ])
            ->where('user_id', $user->id)
            ->firstOrCreate(['user_id' => $user->id]);
    }

    public function applyCoupon(User $user, string $code): Cart
    {
        $cart = $this->getWithItems($user);
        $coupon = $this->couponService->findByCode($code);

        if ($coupon === null) {
            throw new \RuntimeException('Kupon kodu geçersiz.');
        }

        $this->couponService->validateForSubtotal($coupon, $cart->subtotal());

        $cart->update(['coupon_id' => $coupon->id]);

        return $this->getWithItems($user);
    }

    public function removeCoupon(User $user): Cart
    {
        $cart = $this->getWithItems($user);
        $cart->update(['coupon_id' => null]);

        return $this->getWithItems($user);
    }

    public function addItem(Cart $cart, ProductVariant $productVariant, int $quantity): CartItem
    {
        $productVariant->loadMissing('stock');

        $cartItem = CartItem::query()
            ->withTrashed()
            ->where('cart_id', $cart->id)
            ->where('product_variant_id', $productVariant->id)
            ->first();

        $wasTrashed = $cartItem?->trashed() ?? false;
        $newQuantity = $wasTrashed
            ? $quantity
            : ($cartItem?->quantity ?? 0) + $quantity;

        $this->stockService->assertCanReserve(
            $productVariant,
            $newQuantity,
            $wasTrashed ? null : $cartItem?->id,
        );

        if ($cartItem) {
            if ($wasTrashed) {
                $cartItem->restore();
            }

            $cartItem->update([
                'quantity' => $newQuantity,
                'reserved_until' => $this->stockService->reservationExpiresAt(),
            ]);

            return $cartItem->fresh(['productVariant.product', 'productVariant.stock']);
        }

        return CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_variant_id' => $productVariant->id,
            'quantity' => $quantity,
            'reserved_until' => $this->stockService->reservationExpiresAt(),
        ]);
    }

    public function updateQuantity(CartItem $cartItem, int $quantity): CartItem
    {
        $cartItem->loadMissing('productVariant.stock');

        $this->stockService->assertCanReserve(
            $cartItem->productVariant,
            $quantity,
            $cartItem->id,
        );

        $cartItem->update([
            'quantity' => $quantity,
            'reserved_until' => $this->stockService->reservationExpiresAt(),
        ]);

        return $cartItem->fresh(['productVariant.product', 'productVariant.stock']);
    }

    public function removeItem(CartItem $cartItem): void
    {
        $cartItem->delete();
    }
}
