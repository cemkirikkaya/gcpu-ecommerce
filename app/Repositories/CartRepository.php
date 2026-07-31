<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\StockService;

class CartRepository
{
    public function __construct(
        private StockService $stockService,
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
                'items.productVariant.product',
                'items.productVariant.variantValues.variantValue.variant',
                'items.productVariant.stock',
            ])
            ->where('user_id', $user->id)
            ->firstOrCreate(['user_id' => $user->id]);
    }

    public function addItem(Cart $cart, ProductVariant $productVariant, int $quantity): CartItem
    {
        $productVariant->loadMissing('stock');

        $cartItem = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_variant_id', $productVariant->id)
            ->first();

        $newQuantity = ($cartItem?->quantity ?? 0) + $quantity;

        $this->stockService->assertCanReserve(
            $productVariant,
            $newQuantity,
            $cartItem?->id,
        );

        if ($cartItem) {
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
